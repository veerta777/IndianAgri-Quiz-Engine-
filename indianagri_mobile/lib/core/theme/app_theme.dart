import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../constants/app_colors.dart';
import '../constants/app_styles.dart';

/// App theme definitions for Light and Dark modes.
class AppTheme {
  static ThemeData get lightTheme {
    TextTheme baseTextTheme;
    try {
      baseTextTheme = GoogleFonts.poppinsTextTheme();
    } catch (_) {
      baseTextTheme = ThemeData.light().textTheme;
    }

    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.light,
      primaryColor: AppColors.primary,
      scaffoldBackgroundColor: AppColors.backgroundLight,
      colorScheme: const ColorScheme.light(
        primary: AppColors.primary,
        secondary: AppColors.primaryLight,
        surface: AppColors.surfaceLight,
        background: AppColors.backgroundLight,
        error: AppColors.incorrectRed,
        onPrimary: Colors.white,
        onSurface: AppColors.textPrimaryLight,
      ),
      textTheme: baseTextTheme.apply(
        bodyColor: AppColors.textPrimaryLight,
        displayColor: AppColors.textPrimaryLight,
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: Colors.white,
        foregroundColor: AppColors.textPrimaryLight,
        elevation: 0,
        centerTitle: false,
        scrolledUnderElevation: 1,
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          foregroundColor: Colors.white,
          elevation: 0,
          padding: AppStyles.paddingButton,
          shape: RoundedRectangleBorder(
            borderRadius: AppStyles.borderRadiusM,
          ),
          textStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.primary,
          side: const BorderSide(color: AppColors.primary, width: 1.5),
          padding: AppStyles.paddingButton,
          shape: RoundedRectangleBorder(
            borderRadius: AppStyles.borderRadiusM,
          ),
          textStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        border: OutlineInputBorder(
          borderRadius: AppStyles.borderRadiusM,
          borderSide: const BorderSide(color: AppColors.cardBorderLight),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: AppStyles.borderRadiusM,
          borderSide: const BorderSide(color: AppColors.cardBorderLight),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: AppStyles.borderRadiusM,
          borderSide: const BorderSide(color: AppColors.primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: AppStyles.borderRadiusM,
          borderSide: const BorderSide(color: AppColors.incorrectRed),
        ),
      ),
    );
  }

  static ThemeData get darkTheme {
    TextTheme baseTextTheme;
    try {
      baseTextTheme = GoogleFonts.poppinsTextTheme(ThemeData.dark().textTheme);
    } catch (_) {
      baseTextTheme = ThemeData.dark().textTheme;
    }

    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      primaryColor: AppColors.primary,
      scaffoldBackgroundColor: AppColors.backgroundDark,
      colorScheme: const ColorScheme.dark(
        primary: AppColors.primaryLight,
        secondary: AppColors.primary,
        surface: AppColors.surfaceDark,
        background: AppColors.backgroundDark,
        error: AppColors.incorrectRed,
        onPrimary: Colors.white,
        onSurface: AppColors.textPrimaryDark,
      ),
      textTheme: baseTextTheme.apply(
        bodyColor: AppColors.textPrimaryDark,
        displayColor: AppColors.textPrimaryDark,
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.surfaceDark,
        foregroundColor: AppColors.textPrimaryDark,
        elevation: 0,
        centerTitle: false,
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          foregroundColor: Colors.white,
          elevation: 0,
          padding: AppStyles.paddingButton,
          shape: RoundedRectangleBorder(
            borderRadius: AppStyles.borderRadiusM,
          ),
          textStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.primaryLight,
          side: const BorderSide(color: AppColors.primaryLight, width: 1.5),
          padding: AppStyles.paddingButton,
          shape: RoundedRectangleBorder(
            borderRadius: AppStyles.borderRadiusM,
          ),
          textStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.surfaceDark,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        border: OutlineInputBorder(
          borderRadius: AppStyles.borderRadiusM,
          borderSide: const BorderSide(color: AppColors.cardBorderDark),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: AppStyles.borderRadiusM,
          borderSide: const BorderSide(color: AppColors.cardBorderDark),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: AppStyles.borderRadiusM,
          borderSide: const BorderSide(color: AppColors.primaryLight, width: 2),
        ),
      ),
    );
  }
}
