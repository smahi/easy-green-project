import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

class AppProvider with ChangeNotifier {
  Locale _locale = const Locale('en');
  ThemeMode _themeMode = ThemeMode.system;

  Locale get locale => _locale;
  ThemeMode get themeMode => _themeMode;

  AppProvider() {
    _loadPreferences();
  }

  Future<void> _loadPreferences() async {
    final prefs = await SharedPreferences.getInstance();
    
    // Load Language
    final String? languageCode = prefs.getString('languageCode');
    if (languageCode != null) {
      _locale = Locale(languageCode);
    }
    
    // Load Theme
    final String? themeName = prefs.getString('themeMode');
    if (themeName != null) {
      _themeMode = ThemeMode.values.firstWhere(
        (e) => e.toString() == themeName,
        orElse: () => ThemeMode.system,
      );
    }
    
    notifyListeners();
  }

  Future<void> setLocale(Locale newLocale) async {
    if (_locale != newLocale) {
      _locale = newLocale;
      notifyListeners();
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('languageCode', newLocale.languageCode);
    }
  }

  Future<void> setThemeMode(ThemeMode newThemeMode) async {
    if (_themeMode != newThemeMode) {
      _themeMode = newThemeMode;
      notifyListeners();
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('themeMode', newThemeMode.toString());
    }
  }
}
