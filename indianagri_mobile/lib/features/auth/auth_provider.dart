import 'package:flutter/material.dart';
import '../../core/api/api_client.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/storage/secure_storage.dart';

/// Authentication state and user profile management provider.
class AuthProvider extends ChangeNotifier {
  bool _isLoading = false;
  Map<String, dynamic>? _user;
  List<dynamic> _subscriptions = [];
  String? _errorMessage;

  bool get isLoading => _isLoading;
  bool get isAuthenticated => _user != null;
  Map<String, dynamic>? get user => _user;
  List<dynamic> get subscriptions => _subscriptions;
  String? get errorMessage => _errorMessage;

  /// Check persisted token upon app launch.
  Future<bool> checkInitialAuth() async {
    final token = await SecureStorage.getToken();
    if (token == null || token.isEmpty) {
      return false;
    }

    try {
      final res = await ApiClient.get(ApiEndpoints.me);
      if (res is Map<String, dynamic> && res.containsKey('user')) {
        _user = res['user'] as Map<String, dynamic>;
        _subscriptions = (res['subscriptions'] as List<dynamic>?) ?? [];
        await SecureStorage.saveUserProfile(_user!);
        notifyListeners();
        return true;
      }
    } catch (_) {
      // Cached offline profile fallback
      final cached = await SecureStorage.getUserProfile();
      if (cached != null) {
        _user = cached;
        notifyListeners();
        return true;
      }
    }

    await SecureStorage.clearToken();
    return false;
  }

  /// Student Login.
  Future<bool> login(String username, String password) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final res = await ApiClient.post(
        ApiEndpoints.login,
        body: {
          'username': username,
          'password': password,
        },
      );

      if (res is Map<String, dynamic> && res.containsKey('token')) {
        final token = res['token'] as String;
        _user = res['user'] as Map<String, dynamic>;
        _subscriptions = (res['subscriptions'] as List<dynamic>?) ?? [];

        await SecureStorage.saveToken(token);
        await SecureStorage.saveUserProfile(_user!);

        _isLoading = false;
        notifyListeners();
        return true;
      }

      _errorMessage = 'Invalid response from server.';
    } on ApiException catch (e) {
      _errorMessage = e.message;
    } catch (e) {
      _errorMessage = 'An unexpected error occurred. Please try again.';
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  /// Student Registration.
  Future<bool> register(String username, String email, String password, String displayName) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final res = await ApiClient.post(
        ApiEndpoints.register,
        body: {
          'username': username,
          'email': email,
          'password': password,
          'display_name': displayName,
        },
      );

      if (res is Map<String, dynamic> && res.containsKey('token')) {
        final token = res['token'] as String;
        _user = res['user'] as Map<String, dynamic>;

        await SecureStorage.saveToken(token);
        await SecureStorage.saveUserProfile(_user!);

        _isLoading = false;
        notifyListeners();
        return true;
      }

      _errorMessage = 'Registration failed. Please check your details.';
    } on ApiException catch (e) {
      _errorMessage = e.message;
    } catch (_) {
      _errorMessage = 'An error occurred during registration.';
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  /// Forgot Password.
  Future<String> forgotPassword(String loginInput) async {
    try {
      final res = await ApiClient.post(
        ApiEndpoints.forgotPassword,
        body: {'user_login': loginInput},
      );
      if (res is Map<String, dynamic> && res.containsKey('message')) {
        return res['message'].toString();
      }
      return 'Password reset link sent to your registered email.';
    } on ApiException catch (e) {
      throw e.message;
    }
  }

  /// Student Logout.
  Future<void> logout() async {
    try {
      await ApiClient.post(ApiEndpoints.logout);
    } catch (_) {}

    await SecureStorage.clearAll();
    _user = null;
    _subscriptions = [];
    notifyListeners();
  }
}
