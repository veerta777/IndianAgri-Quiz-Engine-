import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import '../storage/secure_storage.dart';

/// Exception thrown for standard REST API communication failures.
class ApiException implements Exception {
  final String message;
  final int statusCode;
  final String? errorCode;

  ApiException(this.message, {this.statusCode = 500, this.errorCode});

  @override
  String toString() => message;
}

/// Centralized REST API client for IndianAgriExam.
class ApiClient {
  static const Duration timeoutDuration = Duration(seconds: 15);

  /// Build request headers with Bearer token authentication.
  static Future<Map<String, String>> _getHeaders({bool isJson = true}) async {
    final headers = <String, String>{
      'Accept': 'application/json',
      'User-Agent': 'IndianAgriExam-Mobile/1.1.0 (Android; Flutter)',
    };
    if (isJson) {
      headers['Content-Type'] = 'application/json; charset=UTF-8';
    }

    final token = await SecureStorage.getToken();
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }

    return headers;
  }

  /// HTTP GET
  static Future<dynamic> get(String url) async {
    try {
      final headers = await _getHeaders();
      final response = await http
          .get(Uri.parse(url), headers: headers)
          .timeout(timeoutDuration);

      return _processResponse(response);
    } on SocketException {
      throw ApiException('Network connection failed. Please check your internet connection.', statusCode: 0);
    } on TimeoutException {
      throw ApiException('Request timed out. Please try again.', statusCode: 408);
    } on http.ClientException catch (e) {
      throw ApiException('Unable to connect to server. Please check your internet connection (${e.message}).', statusCode: 0);
    } on HandshakeException catch (e) {
      throw ApiException('Secure connection failed (${e.message}). Please verify network or SSL settings.', statusCode: 0);
    } catch (e) {
      if (e is ApiException) rethrow;
      throw ApiException('Network error: ${e.toString().replaceFirst("Exception: ", "")}', statusCode: 0);
    }
  }

  /// HTTP POST
  static Future<dynamic> post(String url, {Map<String, dynamic>? body}) async {
    try {
      final headers = await _getHeaders(isJson: true);
      final response = await http
          .post(
            Uri.parse(url),
            headers: headers,
            body: body != null ? jsonEncode(body) : null,
          )
          .timeout(timeoutDuration);

      return _processResponse(response);
    } on SocketException {
      throw ApiException('Network connection failed. Please check your internet connection.', statusCode: 0);
    } on TimeoutException {
      throw ApiException('Request timed out. Please try again.', statusCode: 408);
    } on http.ClientException catch (e) {
      throw ApiException('Unable to connect to server. Please check your internet connection (${e.message}).', statusCode: 0);
    } on HandshakeException catch (e) {
      throw ApiException('Secure connection failed (${e.message}). Please verify network or SSL settings.', statusCode: 0);
    } catch (e) {
      if (e is ApiException) rethrow;
      throw ApiException('Network error: ${e.toString().replaceFirst("Exception: ", "")}', statusCode: 0);
    }
  }

  /// Process HTTP Response and throw formatted ApiException on non-200.
  static dynamic _processResponse(http.Response response) {
    dynamic decoded;
    try {
      decoded = jsonDecode(utf8.decode(response.bodyBytes));
    } catch (_) {
      try {
        decoded = jsonDecode(response.body);
      } catch (_) {
        decoded = response.body;
      }
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      if (decoded is Map) {
        return Map<String, dynamic>.from(decoded);
      }
      return decoded;
    }

    String message = 'An error occurred (${response.statusCode})';
    String? code;

    if (decoded is Map) {
      if (decoded.containsKey('message') && decoded['message'] != null) {
        message = decoded['message'].toString();
      } else if (decoded.containsKey('error') && decoded['error'] != null) {
        message = decoded['error'].toString();
      }
      if (decoded.containsKey('code') && decoded['code'] != null) {
        code = decoded['code'].toString();
      }
    } else if (decoded is String && decoded.trim().isNotEmpty) {
      final cleanText = decoded.trim();
      if (!cleanText.contains('<html') && cleanText.length < 200) {
        message = cleanText;
      } else if (response.statusCode == 404) {
        message = 'Server endpoint not found (404).';
      } else if (response.statusCode == 401 || response.statusCode == 403) {
        message = 'Invalid credentials or access denied (${response.statusCode}).';
      } else if (response.statusCode >= 500) {
        message = 'Server error (${response.statusCode}). Please try again later.';
      }
    }

    throw ApiException(message, statusCode: response.statusCode, errorCode: code);
  }
}
