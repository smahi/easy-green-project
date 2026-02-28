import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:easy_green/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';
import 'package:uuid/uuid.dart';
import '../providers/auth_provider.dart';
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

  @override
  void initState() {
    super.initState();
    _fetchReportTypes();
    _determinePosition();
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

      // Add a timeout to prevent hanging forever
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

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate() || _currentPosition == null || _selectedReportTypeId == null) {
      return;
    }

    setState(() => _isSubmitting = true);
    
    final authProvider = context.read<AuthProvider>();
    final clientUuid = const Uuid().v4();

    try {
      final response = await http.post(
        Uri.parse('${ApiService.apiBaseUrl}/reports'),
        headers: {
          'Authorization': 'Bearer ${authProvider.token}',
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'report_type_id': _selectedReportTypeId,
          'description': _descriptionController.text,
          'latitude': _currentPosition!.latitude,
          'longitude': _currentPosition!.longitude,
          'client_uuid': clientUuid,
          'is_synchronized': true,
        }),
      );

      setState(() => _isSubmitting = false);

      if (response.statusCode == 201 && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(AppLocalizations.of(context)!.success)),
        );
        context.pop();
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Submission failed: ${response.statusCode}')),
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
              const SizedBox(height: 16),
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
