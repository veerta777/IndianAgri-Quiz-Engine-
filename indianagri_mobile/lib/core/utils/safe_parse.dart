/// Robust parsing utility to safely coerce any dynamic JSON value
/// (integers, floats, stringified numbers, booleans, null) into typed Dart primitives
/// without ever throwing TypeErrors or FormatExceptions.
class SafeParse {
  /// Safely parse an integer from dynamic input (int, num, string, null).
  /// Handles string floats like "12.0" by parsing to double and converting to int.
  static int toInt(dynamic val, [int fallback = 0]) {
    if (val == null) return fallback;
    if (val is int) return val;
    if (val is num) return val.toInt();
    if (val is String) {
      final clean = val.trim();
      if (clean.isEmpty) return fallback;
      final parsedInt = int.tryParse(clean);
      if (parsedInt != null) return parsedInt;
      final parsedDouble = double.tryParse(clean);
      if (parsedDouble != null) return parsedDouble.toInt();
    }
    return fallback;
  }

  /// Safely parse a double from dynamic input (double, num, string, null).
  static double toDouble(dynamic val, [double fallback = 0.0]) {
    if (val == null) return fallback;
    if (val is double) return val;
    if (val is num) return val.toDouble();
    if (val is String) {
      final clean = val.trim();
      if (clean.isEmpty) return fallback;
      return double.tryParse(clean) ?? fallback;
    }
    return fallback;
  }

  /// Safely parse a boolean from dynamic input (bool, int, string, null).
  /// Understands 'true', '1', 'yes', 'on' as true.
  static bool toBool(dynamic val, [bool fallback = false]) {
    if (val == null) return fallback;
    if (val is bool) return val;
    if (val is num) return val != 0;
    if (val is String) {
      final clean = val.trim().toLowerCase();
      if (clean == 'true' || clean == '1' || clean == 'yes' || clean == 'on') return true;
      if (clean == 'false' || clean == '0' || clean == 'no' || clean == 'off') return false;
    }
    return fallback;
  }

  /// Safely parse a String from dynamic input with optional fallback.
  static String toStr(dynamic val, [String fallback = '']) {
    if (val == null) return fallback;
    final str = val.toString().trim();
    return str.isEmpty ? fallback : str;
  }
}
