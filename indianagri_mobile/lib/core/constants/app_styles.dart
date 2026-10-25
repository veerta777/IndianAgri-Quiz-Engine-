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

  /// Ultra-premium Glassmorphic card decoration with subtle specular highlight
  static BoxDecoration glassCardDecoration(BuildContext context, {bool isSelected = false, bool isHighlighted = false}) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    Color borderClr;
    if (isHighlighted) {
      borderClr = AppColors.accent;
    } else if (isSelected) {
      borderClr = AppColors.primary;
    } else {
      borderClr = isDark ? const Color(0x2EFFFFFF) : const Color(0x38000000);
    }

    return BoxDecoration(
      color: isDark ? const Color(0xE61E293B) : const Color(0xF7FFFFFF),
      borderRadius: borderRadiusL,
      border: Border.all(
        color: borderClr,
        width: (isSelected || isHighlighted) ? 1.8 : 1.0,
      ),
      boxShadow: [
        BoxShadow(
          color: isDark ? const Color(0x40000000) : const Color(0x0F000000),
          blurRadius: 14,
          offset: const Offset(0, 4),
        ),
        if (isSelected)
          BoxShadow(
            color: AppColors.primary.withOpacity(0.18),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
      ],
    );
  }

  /// Hero gradient container decoration
  static BoxDecoration heroGradientDecoration({BorderRadius? radius}) {
    return BoxDecoration(
      gradient: AppColors.heroEmeraldGradient,
      borderRadius: radius ?? borderRadiusL,
      boxShadow: [
        BoxShadow(
          color: AppColors.primaryDark.withOpacity(0.35),
          blurRadius: 16,
          offset: const Offset(0, 6),
        ),
      ],
    );
  }
}
