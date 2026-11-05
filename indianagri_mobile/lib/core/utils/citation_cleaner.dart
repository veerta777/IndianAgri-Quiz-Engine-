/// Robust utility class to sanitize MCQ options, questions, and explanations
/// by stripping unwanted citation reference markers like [cite: 5], [cite: 1, 2, 3], [citation: 5].
class CitationCleaner {
  /// Matches citation references such as [cite: 5], [cite: 6], [cite: 1, 2, 3], [citation: 5]
  /// with case-insensitivity and captures any surrounding unnecessary whitespace.
  static final RegExp _citationRegex = RegExp(
    r'\s*\[\s*cit(?:e|ation)s?\s*:\s*[^\]]+\]',
    caseSensitive: false,
  );

  static final RegExp _multipleSpaces = RegExp(r' {2,}');

  /// Clean citation markers from any given string.
  /// Preserves scientific formulas, legitimate brackets like [10-20%], [ZnSO4],
  /// and language content in English and Hindi.
  static String clean(dynamic text) {
    if (text == null) return '';
    final str = text.toString();
    if (str.isEmpty) return '';

    return str
        .replaceAll(_citationRegex, '')
        .replaceAll(_multipleSpaces, ' ')
        .trim();
  }

  /// Deeply clean a Question Map object before processing or rendering.
  static Map<String, dynamic> cleanQuestionMap(Map<String, dynamic> q) {
    final Map<String, dynamic> cleaned = Map<String, dynamic>.from(q);

    // Clean question texts
    if (cleaned.containsKey('question_text')) {
      cleaned['question_text'] = clean(cleaned['question_text']);
    }
    if (cleaned.containsKey('question_text_hi')) {
      cleaned['question_text_hi'] = clean(cleaned['question_text_hi']);
    }
    if (cleaned.containsKey('question')) {
      cleaned['question'] = clean(cleaned['question']);
    }
    if (cleaned.containsKey('title')) {
      cleaned['title'] = clean(cleaned['title']);
    }

    // Clean individual options if flat
    for (final key in [
      'option_a',
      'option_b',
      'option_c',
      'option_d',
      'option_a_hi',
      'option_b_hi',
      'option_c_hi',
      'option_d_hi',
    ]) {
      if (cleaned.containsKey(key)) {
        cleaned[key] = clean(cleaned[key]);
      }
    }

    // Clean options list
    if (cleaned['options'] is List) {
      final List<dynamic> opts = cleaned['options'] as List;
      cleaned['options'] = opts.map((opt) {
        if (opt is Map) {
          final optMap = Map<String, dynamic>.from(opt);
          if (optMap.containsKey('text')) {
            optMap['text'] = clean(optMap['text']);
          }
          if (optMap.containsKey('text_hi')) {
            optMap['text_hi'] = clean(optMap['text_hi']);
          }
          if (optMap.containsKey('option_text')) {
            optMap['option_text'] = clean(optMap['option_text']);
          }
          if (optMap.containsKey('content')) {
            optMap['content'] = clean(optMap['content']);
          }
          return optMap;
        }
        return opt;
      }).toList();
    }

    // Clean explanations
    for (final expKey in ['explanation', 'explanation_hi', 'demo_explanation']) {
      if (cleaned.containsKey(expKey)) {
        cleaned[expKey] = clean(cleaned[expKey]);
      }
    }

    // Clean immediate feedback
    if (cleaned['immediate_feedback'] is Map) {
      final fbMap = Map<String, dynamic>.from(cleaned['immediate_feedback'] as Map);
      if (fbMap.containsKey('explanation')) {
        fbMap['explanation'] = clean(fbMap['explanation']);
      }
      if (fbMap.containsKey('feedback_message')) {
        fbMap['feedback_message'] = clean(fbMap['feedback_message']);
      }
      cleaned['immediate_feedback'] = fbMap;
    }

    return cleaned;
  }
}

