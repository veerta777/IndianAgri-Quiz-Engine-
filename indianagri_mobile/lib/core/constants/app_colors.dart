import 'package:flutter/material.dart';

/// App color palette matching IndianAgriExam branding.
class AppColors {
  // Brand colors
  static const Color primary = Color(0xFF117E3A); // IndianAgri Forest Green
  static const Color primaryLight = Color(0xFF16A34A);
  static const Color primaryDark = Color(0xFF0E632E);
  static const Color primarySubtle = Color(0xFFE8F5E9);
  static const Color accent = Color(0xFFD97706); // Warm Amber Accent
  static const Color accentSubtle = Color(0xFFFFFBEB);

  // Status & Feedback colors
  static const Color correctGreen = Color(0xFF059669); // Emerald correct
  static const Color correctGreenBg = Color(0xFFECFDF5);
  static const Color incorrectRed = Color(0xFFDC2626); // Ruby incorrect
  static const Color incorrectRedBg = Color(0xFFFEF2F2);
  static const Color reviewPurple = Color(0xFF7C3AED); // Review purple
  static const Color reviewPurpleBg = Color(0xFFF5F3FF);
  static const Color warningAmber = Color(0xFFD97706);
  static const Color warningAmberBg = Color(0xFFFFFBEB);

  // Backgrounds & Surfaces (Light)
  static const Color backgroundLight = Color(0xFFF8FAF9);
  static const Color surfaceLight = Color(0xFFFFFFFF);
  static const Color cardBorderLight = Color(0xFFE2E8F0);
  static const Color dividerLight = Color(0xFFE5E7EB);

  // Backgrounds & Surfaces (Dark)
  static const Color backgroundDark = Color(0xFF0F172A);
  static const Color surfaceDark = Color(0xFF1E293B);
  static const Color cardBorderDark = Color(0xFF334155);
  static const Color dividerDark = Color(0xFF334155);

  // Text Colors
  static const Color textPrimaryLight = Color(0xFF0F172A);
  static const Color textSecondaryLight = Color(0xFF64748B);
  static const Color textMutedLight = Color(0xFF94A3B8);

  static const Color textPrimaryDark = Color(0xFFF8FAFC);
  static const Color textSecondaryDark = Color(0xFF94A3B8);
  static const Color textMutedDark = Color(0xFF64748B);

  // Premium Glassmorphism & Specular Colors
  static const Color glassWhite = Color(0xE6FFFFFF);
  static const Color glassBorder = Color(0x66FFFFFF);
  static const Color glassBorderSubtle = Color(0x1F000000);
  static const Color glassDark = Color(0xD91E293B);
  static const Color glassDarkBorder = Color(0x33FFFFFF);

  // Brand Gradients
  static const LinearGradient primaryGradient = LinearGradient(
    colors: [Color(0xFF117E3A), Color(0xFF0E632E)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient heroEmeraldGradient = LinearGradient(
    colors: [Color(0xFF0A4D24), Color(0xFF117E3A), Color(0xFF16A34A)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient goldGradient = LinearGradient(
    colors: [Color(0xFFF59E0B), Color(0xFFD97706)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient glassGradient = LinearGradient(
    colors: [Color(0xF0FFFFFF), Color(0xE0F8FAF9)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient darkGlassGradient = LinearGradient(
    colors: [Color(0xF01E293B), Color(0xE00F172A)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
}
