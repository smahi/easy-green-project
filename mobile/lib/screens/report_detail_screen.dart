import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:easy_green/l10n/app_localizations.dart';
import 'package:audioplayers/audioplayers.dart';
import 'package:video_player/video_player.dart';
import 'package:chewie/chewie.dart';
import 'package:google_fonts/google_fonts.dart';
import '../models/report.dart';
import '../theme/app_theme.dart';

class ReportDetailScreen extends StatelessWidget {
  final Report report;

  const ReportDetailScreen({super.key, required this.report});

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'new':
        return const Color(0xFF3498DB);
      case 'processing':
        return const Color(0xFFF39C12);
      case 'resolved':
        return const Color(0xFF27AE60);
      case 'rejected':
        return const Color(0xFFE74C3C);
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final locale = Localizations.localeOf(context).languageCode;
    final theme = Theme.of(context);
    final statusColor = _getStatusColor(report.status);

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 120.0,
            pinned: true,
            backgroundColor: theme.colorScheme.primary,
            foregroundColor: Colors.white,
            flexibleSpace: FlexibleSpaceBar(
              title: Text(
                report.reportType.getName(locale),
                style: GoogleFonts.lexend(fontWeight: FontWeight.bold, color: Colors.white, fontSize: 16),
              ),
              background: Container(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topRight,
                    end: Alignment.bottomLeft,
                    colors: [
                      theme.colorScheme.primary,
                      theme.colorScheme.primary.withOpacity(0.7),
                    ],
                  ),
                ),
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Status & Date Card
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16.0),
                      child: Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                            decoration: BoxDecoration(
                              color: statusColor.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(20),
                              border: Border.all(color: statusColor.withOpacity(0.5)),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(Icons.circle, size: 10, color: statusColor),
                                const SizedBox(width: 8),
                                Text(
                                  report.status.toUpperCase(),
                                  style: TextStyle(color: statusColor, fontWeight: FontWeight.bold, fontSize: 12),
                                ),
                              ],
                            ),
                          ),
                          const Spacer(),
                          Icon(Icons.calendar_today, size: 14, color: theme.hintColor),
                          const SizedBox(width: 4),
                          Text(
                            DateFormat.yMMMd().add_Hm().format(report.createdAt),
                            style: theme.textTheme.bodySmall,
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Description Section
                  _buildSectionHeader(context, l10n.description, Icons.description_outlined),
                  const SizedBox(height: 12),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: theme.colorScheme.surface,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: theme.dividerColor.withOpacity(0.1)),
                    ),
                    child: Text(
                      report.getDescription(locale),
                      style: theme.textTheme.bodyLarge?.copyWith(height: 1.5),
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Location Card
                  _buildSectionHeader(context, l10n.location, Icons.location_on_outlined),
                  const SizedBox(height: 12),
                  Card(
                    child: ListTile(
                      leading: CircleAvatar(
                        backgroundColor: theme.colorScheme.primary.withOpacity(0.1),
                        child: Icon(Icons.map_outlined, color: theme.colorScheme.primary),
                      ),
                      title: const Text('Coordinates'),
                      subtitle: Text('Lat: ${report.latitude}, Long: ${report.longitude}'),
                      trailing: IconButton(
                        icon: const Icon(Icons.open_in_new),
                        onPressed: () {
                          // TODO: Open in Google Maps
                        },
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Feedback Section (if exists)
                  if (report.inspectorFeedback != null && report.getInspectorFeedback(locale).isNotEmpty) ...[
                    _buildSectionHeader(context, 'Inspector Feedback', Icons.feedback_outlined, color: Colors.orange),
                    const SizedBox(height: 12),
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.orange.withOpacity(0.05),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: Colors.orange.withOpacity(0.2)),
                      ),
                      child: Text(
                        report.getInspectorFeedback(locale),
                        style: theme.textTheme.bodyMedium?.copyWith(fontStyle: FontStyle.italic),
                      ),
                    ),
                    const SizedBox(height: 24),
                  ],

                  // Media Section
                  if (report.mediaAttachments.isNotEmpty) ...[
                    _buildSectionHeader(context, 'Media Attachments', Icons.attach_file),
                    const SizedBox(height: 12),
                    ...report.mediaAttachments.map((url) => MediaAttachmentWidget(url: url)),
                  ],
                  const SizedBox(height: 40),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionHeader(BuildContext context, String title, IconData icon, {Color? color}) {
    final theme = Theme.of(context);
    return Row(
      children: [
        Icon(icon, size: 20, color: color ?? theme.colorScheme.primary),
        const SizedBox(width: 8),
        Text(
          title,
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.bold,
            color: color ?? theme.colorScheme.primary,
          ),
        ),
      ],
    );
  }
}

class MediaAttachmentWidget extends StatelessWidget {
  final String url;

  const MediaAttachmentWidget({super.key, required this.url});

  String _getExtension() {
    return url.split('.').last.toLowerCase();
  }

  @override
  Widget build(BuildContext context) {
    final ext = _getExtension();
    final theme = Theme.of(context);

    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].contains(ext)) {
      return Container(
        margin: const EdgeInsets.only(bottom: 16),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, 4))
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            GestureDetector(
              onTap: () {
                Navigator.of(context).push(MaterialPageRoute(
                  builder: (context) => FullScreenImage(url: url),
                ));
              },
              child: ClipRRect(
                borderRadius: BorderRadius.circular(16),
                child: Hero(
                  tag: url,
                  child: Image.network(
                    url,
                    width: double.infinity,
                    height: 220,
                    fit: BoxFit.cover,
                    errorBuilder: (context, error, stackTrace) => Container(
                      height: 200,
                      width: double.infinity,
                      color: theme.colorScheme.surfaceContainerHighest,
                      child: const Icon(Icons.broken_image, size: 50),
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      );
    } else if (['mp4', 'mov', 'avi', 'webm'].contains(ext)) {
      return VideoAttachment(url: url);
    } else if (['mp3', 'wav', 'm4a', 'aac'].contains(ext)) {
      return AudioAttachment(url: url);
    } else {
      return Card(
        margin: const EdgeInsets.only(bottom: 16),
        child: ListTile(
          leading: const Icon(Icons.insert_drive_file),
          title: Text(url.split('/').last),
          subtitle: Text('File: $ext'),
          onTap: () {},
        ),
      );
    }
  }
}

class VideoAttachment extends StatefulWidget {
  final String url;
  const VideoAttachment({super.key, required this.url});

  @override
  State<VideoAttachment> createState() => _VideoAttachmentState();
}

class _VideoAttachmentState extends State<VideoAttachment> {
  late VideoPlayerController _videoPlayerController;
  ChewieController? _chewieController;

  @override
  void initState() {
    super.initState();
    _videoPlayerController = VideoPlayerController.networkUrl(Uri.parse(widget.url));
    _videoPlayerController.initialize().then((_) {
      if (mounted) {
        setState(() {
          _chewieController = ChewieController(
            videoPlayerController: _videoPlayerController,
            aspectRatio: _videoPlayerController.value.aspectRatio,
            autoPlay: false,
            looping: false,
            cupertinoProgressColors: ChewieProgressColors(playedColor: AppTheme.primaryGreen),
            materialProgressColors: ChewieProgressColors(playedColor: AppTheme.primaryGreen),
          );
        });
      }
    });
  }

  @override
  void dispose() {
    _videoPlayerController.dispose();
    _chewieController?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      height: 220,
      width: double.infinity,
      decoration: BoxDecoration(
        color: Colors.black,
        borderRadius: BorderRadius.circular(16),
      ),
      child: _chewieController != null && _chewieController!.videoPlayerController.value.isInitialized
          ? ClipRRect(
              borderRadius: BorderRadius.circular(16),
              child: Chewie(controller: _chewieController!),
            )
          : const Center(child: CircularProgressIndicator()),
    );
  }
}

class AudioAttachment extends StatefulWidget {
  final String url;
  const AudioAttachment({super.key, required this.url});

  @override
  State<AudioAttachment> createState() => _AudioAttachmentState();
}

class _AudioAttachmentState extends State<AudioAttachment> {
  late AudioPlayer _audioPlayer;
  PlayerState _playerState = PlayerState.stopped;
  Duration _duration = Duration.zero;
  Duration _position = Duration.zero;

  @override
  void initState() {
    super.initState();
    _audioPlayer = AudioPlayer();
    _audioPlayer.onPlayerStateChanged.listen((state) {
      if (mounted) setState(() => _playerState = state);
    });
    _audioPlayer.onDurationChanged.listen((d) {
      if (mounted) setState(() => _duration = d);
    });
    _audioPlayer.onPositionChanged.listen((p) {
      if (mounted) setState(() => _position = p);
    });
  }

  @override
  void dispose() {
    _audioPlayer.dispose();
    super.dispose();
  }

  Future<void> _playPause() async {
    if (_playerState == PlayerState.playing) {
      await _audioPlayer.pause();
    } else {
      await _audioPlayer.play(UrlSource(widget.url));
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: theme.colorScheme.primary.withOpacity(0.05),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: theme.colorScheme.primary.withOpacity(0.1)),
      ),
      child: Column(
        children: [
          Row(
            children: [
              IconButton.filledTonal(
                icon: Icon(_playerState == PlayerState.playing ? Icons.pause_rounded : Icons.play_arrow_rounded),
                onPressed: _playPause,
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Voice Recording', style: theme.textTheme.labelMedium?.copyWith(fontWeight: FontWeight.bold)),
                    SliderTheme(
                      data: SliderTheme.of(context).copyWith(
                        trackHeight: 2,
                        thumbShape: const RoundSliderThumbShape(enabledThumbRadius: 6),
                        overlayShape: const RoundSliderOverlayShape(overlayRadius: 12),
                      ),
                      child: Slider(
                        value: _position.inMilliseconds.toDouble(),
                        max: _duration.inMilliseconds.toDouble() > 0 ? _duration.inMilliseconds.toDouble() : 1.0,
                        onChanged: (value) {
                          _audioPlayer.seek(Duration(milliseconds: value.toInt()));
                        },
                      ),
                    ),
                  ],
                ),
              ),
              Text(
                '${_position.inMinutes}:${(_position.inSeconds % 60).toString().padLeft(2, '0')}',
                style: GoogleFonts.lexend(fontSize: 10, color: theme.hintColor),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class FullScreenImage extends StatelessWidget {
  final String url;
  const FullScreenImage({super.key, required this.url});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        iconTheme: const IconThemeData(color: Colors.white),
        elevation: 0,
      ),
      body: Center(
        child: InteractiveViewer(
          child: Hero(
            tag: url,
            child: Image.network(url),
          ),
        ),
      ),
    );
  }
}
