import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:easy_green/l10n/app_localizations.dart';
import '../providers/app_provider.dart';
import '../providers/auth_provider.dart';

class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final appProvider = context.watch<AppProvider>();
    final theme = Theme.of(context);
    
    return Scaffold(
      backgroundColor: theme.colorScheme.surfaceContainerHighest.withOpacity(0.5),
      appBar: AppBar(
        title: Text(l10n.settings),
        backgroundColor: Colors.transparent,
      ),
      body: SingleChildScrollView(
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildSectionTitle(context, l10n.language),
              Card(
                child: Column(
                  children: [
                    _buildLanguageTile(context, 'English', 'en', appProvider),
                    const Divider(height: 1, indent: 56),
                    _buildLanguageTile(context, 'العربية', 'ar', appProvider),
                  ],
                ),
              ),
              const SizedBox(height: 24),
              _buildSectionTitle(context, l10n.theme),
              Card(
                child: Column(
                  children: [
                    _buildThemeTile(context, l10n.light_mode, Icons.light_mode, ThemeMode.light, appProvider),
                    const Divider(height: 1, indent: 56),
                    _buildThemeTile(context, l10n.dark_mode, Icons.dark_mode, ThemeMode.dark, appProvider),
                    const Divider(height: 1, indent: 56),
                    _buildThemeTile(context, 'System', Icons.settings_brightness, ThemeMode.system, appProvider),
                  ],
                ),
              ),
              const SizedBox(height: 32),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: () => context.read<AuthProvider>().logout(),
                  icon: const Icon(Icons.logout),
                  label: Text(l10n.logout),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.red,
                    side: const BorderSide(color: Colors.red),
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  ),
                ),
              ),
              const SizedBox(height: 24),
              const Center(
                child: Text(
                  'Easy Green v1.0.0',
                  style: TextStyle(color: Colors.grey, fontSize: 12),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSectionTitle(BuildContext context, String title) {
    return Padding(
      padding: const EdgeInsets.only(left: 12, bottom: 8),
      child: Text(
        title.toUpperCase(),
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.bold,
          color: Theme.of(context).colorScheme.primary,
          letterSpacing: 1.2,
        ),
      ),
    );
  }

  Widget _buildLanguageTile(BuildContext context, String title, String code, AppProvider provider) {
    final isSelected = provider.locale.languageCode == code;
    return ListTile(
      leading: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.primary.withOpacity(0.1),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Icon(Icons.language, size: 20, color: Theme.of(context).colorScheme.primary),
      ),
      title: Text(title),
      trailing: isSelected ? Icon(Icons.check_circle, color: Theme.of(context).colorScheme.primary) : null,
      onTap: () => provider.setLocale(Locale(code)),
    );
  }

  Widget _buildThemeTile(BuildContext context, String title, IconData icon, ThemeMode mode, AppProvider provider) {
    final isSelected = provider.themeMode == mode;
    return ListTile(
      leading: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.primary.withOpacity(0.1),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Icon(icon, size: 20, color: Theme.of(context).colorScheme.primary),
      ),
      title: Text(title),
      trailing: isSelected ? Icon(Icons.check_circle, color: Theme.of(context).colorScheme.primary) : null,
      onTap: () => provider.setThemeMode(mode),
    );
  }
}
