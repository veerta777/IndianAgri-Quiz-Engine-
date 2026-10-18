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
    }
  }

  /// Process HTTP Response and throw formatted ApiException on non-200.
  static dynamic _processResponse(http.Response response) {
    dynamic decoded;
    try {
      decoded = jsonDecode(utf8.decode(response.bodyBytes));
    } catch (_) {
      decoded = response.body;
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return decoded;
    }

    String message = 'An unexpected error occurred (${response.statusCode})';
    String? code;

    if (decoded is Map<String, dynamic>) {
      if (decoded.containsKey('message')) {
        message = decoded['message'].toString();
      }
      if (decoded.containsKey('code')) {
        code = decoded['code'].toString();
      }
    }

    throw ApiException(message, statusCode: response.statusCode, errorCode: code);
  }
}
