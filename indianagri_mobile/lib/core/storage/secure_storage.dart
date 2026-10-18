import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';

/// Secure token and session state storage manager.
class SecureStorage {
  static const String _keyToken = 'iaq_auth_token';
  static const String _keyUser = 'iaq_user_profile';
  static const String _keyLanguage = 'iaq_language_code';

  static SharedPreferences? _prefs;

  static Future<SharedPreferences> get _instance async {
    _prefs ??= await SharedPreferences.getInstance();
    return _prefs!;
  }

  // Token Management
  static Future<void> saveToken(String token) async {
    final prefs = await _instance;
    await prefs.setString(_keyToken, token);
  }

  static Future<String?> getToken() async {
    final prefs = await _instance;
    return prefs.getString(_keyToken);
  }

  static Future<void> clearToken() async {
    final prefs = await _instance;
    await prefs.remove(_keyToken);
  }

  // User Profile
  static Future<void> saveUserProfile(Map<String, dynamic> user) async {
    final prefs = await _instance;
    await prefs.setString(_keyUser, jsonEncode(user));
  }

  static Future<Map<String, dynamic>?> getUserProfile() async {
    final prefs = await _instance;
    final raw = prefs.getString(_keyUser);
    if (raw == null) return null;
    try {
      return jsonDecode(raw) as Map<String, dynamic>;
    } catch (_) {
      return null;
    }
  }

  static Future<void> clearAll() async {
    final prefs = await _instance;
    await prefs.remove(_keyToken);
    await prefs.remove(_keyUser);
  }

  // Language Preference
  static Future<void> saveLanguage(String langCode) async {
    final prefs = await _instance;
    await prefs.setString(_keyLanguage, langCode);
  }

  static Future<String?> getLanguage() async {
    final prefs = await _instance;
    return prefs.getString(_keyLanguage);
  }
}
