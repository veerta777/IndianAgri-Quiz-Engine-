import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/app_colors.dart';
import '../constants/app_styles.dart';
import '../localization/app_localizations.dart';

/// Language toggle widget (English <-> हिन्दी) for top bar and exam interface.
class LanguageToggle extends StatelessWidget {
  const LanguageToggle({super.key});

  @override
  Widget build(BuildContext context) {
    final langProvider = Provider.of<LanguageProvider>(context);
    final isHindi = langProvider.isHindi;

    return InkWell(
      onTap: () {
        langProvider.toggleLanguage();
      },
      borderRadius: AppStyles.borderRadiusM,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: AppColors.primarySubtle,
          borderRadius: AppStyles.borderRadiusM,
          border: Border.all(color: AppColors.primary.withOpacity(0.3)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.translate, size: 16, color: AppColors.primary),
            const SizedBox(width: 6),
            Text(
              isHindi ? 'हिन्दी' : 'English',
              style: const TextStyle(
                color: AppColors.primaryDark,
                fontWeight: FontWeight.bold,
                fontSize: 13,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
