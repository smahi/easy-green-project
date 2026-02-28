class Report {
  final int id;
  final String? clientUuid;
  final ReportType reportType;
  final dynamic description;
  final String latitude;
  final String longitude;
  final String status;
  final bool isSynchronized;
  final List<String> mediaAttachments;
  final DateTime createdAt;
  final dynamic inspectorFeedback;

  Report({
    required this.id,
    this.clientUuid,
    required this.reportType,
    required this.description,
    required this.latitude,
    required this.longitude,
    required this.status,
    required this.isSynchronized,
    required this.mediaAttachments,
    required this.createdAt,
    this.inspectorFeedback,
  });

  factory Report.fromJson(Map<String, dynamic> json) {
    return Report(
      id: json['id'],
      clientUuid: json['client_uuid'],
      reportType: ReportType.fromJson(json['report_type']),
      description: json['description'],
      latitude: json['latitude'],
      longitude: json['longitude'],
      status: json['status'],
      isSynchronized: json['is_synchronized'] ?? true,
      mediaAttachments: List<String>.from(json['media_attachments'] ?? []),
      createdAt: DateTime.parse(json['created_at'] ?? DateTime.now().toIso8601String()),
      inspectorFeedback: json['inspector_feedback'],
    );
  }

  String getDescription(String locale) {
    if (description is String) return description;
    if (description is Map) {
      return description[locale] ?? description['en'] ?? description.values.first.toString();
    }
    return '';
  }

  String getInspectorFeedback(String locale) {
    if (inspectorFeedback == null) return '';
    if (inspectorFeedback is String) return inspectorFeedback;
    if (inspectorFeedback is Map) {
      return inspectorFeedback[locale] ?? inspectorFeedback['en'] ?? inspectorFeedback.values.first.toString();
    }
    return '';
  }
}

class ReportType {
  final int id;
  final dynamic name;
  final dynamic description;
  final String? icon;
  final String? color;
  final int severityLevel;

  ReportType({
    required this.id,
    required this.name,
    this.description,
    this.icon,
    this.color,
    required this.severityLevel,
  });

  factory ReportType.fromJson(Map<String, dynamic> json) {
    return ReportType(
      id: json['id'],
      name: json['name'],
      description: json['description'],
      icon: json['icon'],
      color: json['color'],
      severityLevel: json['severity_level'] ?? 1,
    );
  }

  String getName(String locale) {
    if (name is String) return name;
    if (name is Map) {
      return name[locale] ?? name['en'] ?? name.values.first.toString();
    }
    return '';
  }
}
