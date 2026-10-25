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
      if (res is Map && res.containsKey('user') && res['user'] != null) {
        _user = Map<String, dynamic>.from(res['user'] as Map);
        if (res['subscriptions'] is List) {
          _subscriptions = List<dynamic>.from(res['subscriptions'] as List);
        } else {
          _subscriptions = [];
        }
        await SecureStorage.saveUserProfile(_user!);
        notifyListeners();
        return true;
      }
    } on ApiException catch (e) {
      // If unauthorized or forbidden, token is expired/invalid; force login
      if (e.statusCode == 401 || e.statusCode == 403) {
        await SecureStorage.clearAll();
        _user = null;
        return false;
      }
      // For network errors (status 0), fallback to cached offline profile
      final cached = await SecureStorage.getUserProfile();
      if (cached != null) {
        _user = cached;
        notifyListeners();
        return true;
      }
    } catch (_) {
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

      if (res is Map && res.containsKey('token') && res['token'] != null) {
        final token = res['token'].toString();
        if (res['user'] is Map) {
          _user = Map<String, dynamic>.from(res['user'] as Map);
        } else {
          _user = {'username': username};
        }

        if (res['subscriptions'] is List) {
          _subscriptions = List<dynamic>.from(res['subscriptions'] as List);
        } else {
          _subscriptions = [];
        }

        await SecureStorage.saveToken(token);
        await SecureStorage.saveUserProfile(_user!);

        _isLoading = false;
        notifyListeners();
        return true;
      }

      _errorMessage = 'Invalid response format received from server.';
    } on ApiException catch (e) {
      _errorMessage = e.message;
    } catch (e) {
      debugPrint('Auth login exception: $e');
      final errStr = e.toString().replaceFirst('Exception: ', '');
      if (errStr.contains('ClientException') || errStr.contains('SocketException')) {
        _errorMessage = 'Cannot connect to server. Please check your internet connection.';
      } else if (errStr.contains('HandshakeException')) {
        _errorMessage = 'SSL connection failed. Please check network security settings.';
      } else {
        _errorMessage = errStr;
      }
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

      if (res is Map && res.containsKey('token') && res['token'] != null) {
        final token = res['token'].toString();
        if (res['user'] is Map) {
          _user = Map<String, dynamic>.from(res['user'] as Map);
        } else {
          _user = {'username': username, 'email': email, 'display_name': displayName};
        }

        await SecureStorage.saveToken(token);
        await SecureStorage.saveUserProfile(_user!);

        _isLoading = false;
        notifyListeners();
        return true;
      }

      _errorMessage = 'Registration failed. Please check your details.';
    } on ApiException catch (e) {
      _errorMessage = e.message;
    } catch (e) {
      debugPrint('Auth registration exception: $e');
      final errStr = e.toString().replaceFirst('Exception: ', '');
      if (errStr.contains('ClientException') || errStr.contains('SocketException')) {
        _errorMessage = 'Cannot connect to server. Please check your internet connection.';
      } else {
        _errorMessage = errStr;
      }
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
    ApiClient.clearCache();
    _user = null;
    _subscriptions = [];
    notifyListeners();
  }
}
