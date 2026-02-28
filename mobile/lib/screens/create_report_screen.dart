import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:easy_green/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';
import 'package:uuid/uuid.dart';
import 'package:image_picker/image_picker.dart';
import 'package:record/record.dart';
import 'package:path_provider/path_provider.dart';
import 'package:path/path.dart' as path;
import 'package:permission_handler/permission_handler.dart';

import '../providers/auth_provider.dart';
import '../providers/report_provider.dart';
import '../models/report.dart';
import '../services/api_service.dart';

class CreateReportScreen extends StatefulWidget {
  const CreateReportScreen({super.key});

  @override
  State<CreateReportScreen> createState() => _CreateReportScreenState();
}

class _CreateReportScreenState extends State<CreateReportScreen> {
  final _formKey = GlobalKey<FormState>();
  final _descriptionController = TextEditingController();
  int? _selectedReportTypeId;
  Position? _currentPosition;
  bool _isFetchingLocation = false;
  bool _isSubmitting = false;
  bool _isLoadingTypes = true;
  String? _typesError;
  List<Map<String, dynamic>> _reportTypes = [];

  // Media related
  final ImagePicker _picker = ImagePicker();
  final List<File> _mediaFiles = [];
  
  // Audio recording
  late AudioRecorder _audioRecorder;
  bool _isRecording = false;
  String? _audioPath;

  @override
  void initState() {
    super.initState();
    _audioRecorder = AudioRecorder();
    _fetchReportTypes();
    _determinePosition();
  }

  @override
  void dispose() {
    _audioRecorder.dispose();
    super.dispose();
  }

  Future<void> _fetchReportTypes() async {
    setState(() {
      _isLoadingTypes = true;
      _typesError = null;
    });
    
    final authProvider = context.read<AuthProvider>();
    try {
      final response = await http.get(
        Uri.parse('${ApiService.apiBaseUrl}/report-types'),
        headers: {
          'Authorization': 'Bearer ${authProvider.token}',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final List<dynamic> data = jsonDecode(response.body)['data'];
        setState(() {
          _reportTypes = data.map((e) => {
            'id': e['id'],
            'name': e['name'], 
          }).toList();
          _isLoadingTypes = false;
        });
      } else if (response.statusCode == 401) {
        setState(() {
          _typesError = 'Session expired. Please log in again.';
          _isLoadingTypes = false;
        });
      } else {
        setState(() {
          _typesError = 'Failed to load report types (${response.statusCode})';
          _isLoadingTypes = false;
        });
      }
    } catch (e) {
      debugPrint('Error fetching report types: $e');
      if (mounted) {
        setState(() {
          _typesError = 'Check your internet connection';
          _isLoadingTypes = false;
        });
      }
    }
  }

  Future<void> _determinePosition() async {
    setState(() => _isFetchingLocation = true);
    
    try {
      bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        setState(() => _isFetchingLocation = false);
        return;
      }

      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) {
          setState(() => _isFetchingLocation = false);
          return;
        }
      }
      
      if (permission == LocationPermission.deniedForever) {
        setState(() => _isFetchingLocation = false);
        return;
      }

      final position = await Geolocator.getCurrentPosition(
        timeLimit: const Duration(seconds: 10),
      );
      
      setState(() {
        _currentPosition = position;
        _isFetchingLocation = false;
      });
    } catch (e) {
      debugPrint('Error getting location: $e');
      if (mounted) {
        setState(() => _isFetchingLocation = false);
      }
    }
  }

  Future<void> _pickImage(ImageSource source) async {
    final XFile? image = await _picker.pickImage(source: source);
    if (image != null) {
      setState(() {
        _mediaFiles.add(File(image.path));
      });
    }
  }

  Future<void> _pickVideo() async {
    final XFile? video = await _picker.pickVideo(source: ImageSource.camera);
    if (video != null) {
      setState(() {
        _mediaFiles.add(File(video.path));
      });
    }
  }

  Future<void> _toggleRecording() async {
    if (_isRecording) {
      final path = await _audioRecorder.stop();
      setState(() {
        _isRecording = false;
        if (path != null) {
          _mediaFiles.add(File(path));
        }
      });
    } else {
      if (await _audioRecorder.hasPermission()) {
        final directory = await getApplicationDocumentsDirectory();
        final fileName = 'voice_${DateTime.now().millisecondsSinceEpoch}.m4a';
        final filePath = path.join(directory.path, fileName);
        
        const config = RecordConfig();
        await _audioRecorder.start(config, path: filePath);
        setState(() {
          _isRecording = true;
        });
      }
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate() || _currentPosition == null || _selectedReportTypeId == null) {
      return;
    }

    setState(() => _isSubmitting = true);
    
    final authProvider = context.read<AuthProvider>();
    final clientUuid = const Uuid().v4();

    try {
      final uri = Uri.parse('${ApiService.apiBaseUrl}/reports');
      final request = http.MultipartRequest('POST', uri)
        ..headers.addAll({
          'Authorization': 'Bearer ${authProvider.token}',
          'Accept': 'application/json',
        })
        ..fields['report_type_id'] = _selectedReportTypeId.toString()
        ..fields['description'] = _descriptionController.text
        ..fields['latitude'] = _currentPosition!.latitude.toString()
        ..fields['longitude'] = _currentPosition!.longitude.toString()
        ..fields['client_uuid'] = clientUuid
        ..fields['is_synchronized'] = '1';

      for (var file in _mediaFiles) {
        if (await file.exists()) {
          final length = await file.length();
          debugPrint('Uploading file: ${file.path}, size: $length');
          if (length > 0) {
            request.files.add(await http.MultipartFile.fromPath(
              'media_attachments[]',
              file.path,
            ));
          } else {
            debugPrint('Warning: Empty file skipped: ${file.path}');
          }
        }
      }

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);

      setState(() => _isSubmitting = false);

      if (response.statusCode == 201 && mounted) {
        final data = jsonDecode(response.body);
        if (data['report'] != null) {
          final newReport = Report.fromJson(data['report']);
          context.read<ReportProvider>().addReport(newReport);
        }
        
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(AppLocalizations.of(context)!.success)),
        );
        context.pop();
      } else {
        debugPrint('Submit Error: ${response.body}');
        String errorMessage = 'Submission failed: ${response.statusCode}';
        try {
          final errorData = jsonDecode(response.body);
          if (errorData['errors'] != null) {
            errorMessage = (errorData['errors'] as Map).values.first[0].toString();
          } else if (errorData['message'] != null) {
            errorMessage = errorData['message'];
          }
        } catch (_) {}
        
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(errorMessage),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      debugPrint('Submission error: $e');
      setState(() => _isSubmitting = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(AppLocalizations.of(context)!.error)),
        );
      }
    }
  }

  String _getReportTypeName(dynamic name) {
    if (name is String) return name;
    if (name is Map) {
      final locale = Localizations.localeOf(context).languageCode;
      return name[locale] ?? name['en'] ?? name.values.first.toString();
    }
    return name.toString();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.create_report),
      ),
      body: _isLoadingTypes 
        ? const Center(child: CircularProgressIndicator())
        : _typesError != null
          ? Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.error_outline, size: 64, color: Colors.red),
                  const SizedBox(height: 16),
                  Text(_typesError!, style: const TextStyle(fontSize: 16)),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: _typesError!.contains('Session') 
                      ? () {
                          context.read<AuthProvider>().logout();
                          context.go('/login');
                        }
                      : _fetchReportTypes,
                    child: Text(_typesError!.contains('Session') ? 'Go to Login' : 'Try Again'),
                  ),
                ],
              ),
            )
          : SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              DropdownButtonFormField<int>(
                value: _selectedReportTypeId,
                decoration: InputDecoration(
                  labelText: l10n.report_type,
                  border: const OutlineInputBorder(),
                ),
                items: _reportTypes.map((type) {
                  return DropdownMenuItem<int>(
                    value: type['id'],
                    child: Text(_getReportTypeName(type['name'])),
                  );
                }).toList(),
                onChanged: (value) => setState(() => _selectedReportTypeId = value),
                validator: (value) => value == null ? l10n.required : null,
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _descriptionController,
                maxLines: 5,
                decoration: InputDecoration(
                  labelText: l10n.description,
                  border: const OutlineInputBorder(),
                ),
                validator: (value) => value!.isEmpty ? l10n.required : null,
              ),
              const SizedBox(height: 24),
              Text('Media Attachments', style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: 8),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  IconButton.filledTonal(
                    onPressed: () => _pickImage(ImageSource.camera),
                    icon: const Icon(Icons.camera_alt),
                    tooltip: 'Take Photo',
                  ),
                  IconButton.filledTonal(
                    onPressed: () => _pickImage(ImageSource.gallery),
                    icon: const Icon(Icons.photo_library),
                    tooltip: 'Gallery',
                  ),
                  IconButton.filledTonal(
                    onPressed: _pickVideo,
                    icon: const Icon(Icons.videocam),
                    tooltip: 'Record Video',
                  ),
                  IconButton.filled(
                    onPressed: _toggleRecording,
                    icon: Icon(_isRecording ? Icons.stop : Icons.mic),
                    style: IconButton.styleFrom(
                      backgroundColor: _isRecording ? Colors.red : null,
                    ),
                    tooltip: 'Voice Record',
                  ),
                ],
              ),
              if (_mediaFiles.isNotEmpty) ...[
                const SizedBox(height: 16),
                SizedBox(
                  height: 80,
                  child: ListView.builder(
                    scrollDirection: Axis.horizontal,
                    itemCount: _mediaFiles.length,
                    itemBuilder: (context, index) {
                      final file = _mediaFiles[index];
                      final isAudio = file.path.endsWith('.m4a');
                      return Container(
                        width: 80,
                        margin: const EdgeInsets.only(right: 8),
                        decoration: BoxDecoration(
                          border: Border.all(color: Colors.grey),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Stack(
                          children: [
                            Center(
                              child: isAudio 
                                ? const Icon(Icons.audiotrack)
                                : file.path.contains('video') || file.path.endsWith('.mp4')
                                  ? const Icon(Icons.video_file)
                                  : Image.file(file, fit: BoxFit.cover),
                            ),
                            Positioned(
                              top: 0,
                              right: 0,
                              child: GestureDetector(
                                onTap: () => setState(() => _mediaFiles.removeAt(index)),
                                child: Container(
                                  color: Colors.black54,
                                  child: const Icon(Icons.close, size: 16, color: Colors.white),
                                ),
                              ),
                            ),
                          ],
                        ),
                      );
                    },
                  ),
                ),
              ],
              const SizedBox(height: 24),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        l10n.location,
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      const SizedBox(height: 8),
                      if (_isFetchingLocation)
                        Row(
                          children: [
                            const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            ),
                            const SizedBox(width: 12),
                            Text(l10n.fetching_location),
                          ],
                        )
                      else if (_currentPosition != null)
                        Text(
                          'Lat: ${_currentPosition!.latitude.toStringAsFixed(6)}\nLong: ${_currentPosition!.longitude.toStringAsFixed(6)}',
                        )
                      else
                        const Text('Location not available'),
                      const SizedBox(height: 8),
                      TextButton.icon(
                        onPressed: _isFetchingLocation ? null : _determinePosition,
                        icon: const Icon(Icons.my_location),
                        label: const Text('Refresh Location'),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 32),
              ElevatedButton(
                onPressed: _isSubmitting || _currentPosition == null ? null : _submit,
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 16),
                ),
                child: _isSubmitting
                    ? const CircularProgressIndicator()
                    : Text(l10n.submit_report),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
