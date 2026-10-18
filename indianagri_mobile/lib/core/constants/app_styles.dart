import 'package:flutter/material.dart';
import 'app_colors.dart';

/// Design tokens, elevations, paddings, and border radii.
class AppStyles {
  // Border Radii
  static const double radiusS = 6.0;
  static const double radiusM = 10.0;
  static const double radiusL = 14.0;
  static const double radiusXL = 20.0;

  static final BorderRadius borderRadiusS = BorderRadius.circular(radiusS);
  static final BorderRadius borderRadiusM = BorderRadius.circular(radiusM);
  static final BorderRadius borderRadiusL = BorderRadius.circular(radiusL);
  static final BorderRadius borderRadiusXL = BorderRadius.circular(radiusXL);

  // Paddings
  static const EdgeInsets paddingPage = EdgeInsets.symmetric(horizontal: 16.0, vertical: 12.0);
  static const EdgeInsets paddingCard = EdgeInsets.all(14.0);
  static const EdgeInsets paddingButton = EdgeInsets.symmetric(horizontal: 20.0, vertical: 14.0);

  // Shadows
  static const List<BoxShadow> cardShadow = [
    BoxShadow(
      color: Color(0x0A000000),
      blurRadius: 10,
      offset: Offset(0, 4),
    ),
  ];

  static const List<BoxShadow> floatShadow = [
    BoxShadow(
      color: Color(0x1A000000),
      blurRadius: 16,
      offset: Offset(0, 6),
    ),
  ];

  // Common BoxDecorations
  static BoxDecoration cardDecoration(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return BoxDecoration(
      color: isDark ? AppColors.surfaceDark : AppColors.surfaceLight,
      borderRadius: borderRadiusL,
      border: Border.all(
        color: isDark ? AppColors.cardBorderDark : AppColors.cardBorderLight,
        width: 1.0,
      ),
      boxShadow: cardShadow,
    );
  }
}
