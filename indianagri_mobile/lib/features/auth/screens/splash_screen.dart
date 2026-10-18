import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/app_colors.dart';
import '../auth_provider.dart';
import 'login_screen.dart';
import '../../dashboard/screens/dashboard_screen.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  bool _navigated = false;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _startFlow();
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  void _goTo(Widget screen) {
    if (_navigated || !mounted) return;
    _navigated = true;
    _timer?.cancel();
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => screen),
    );
  }

  Future<void> _startFlow() async {
    // Failsafe: No matter what, leave splash screen within 2 seconds
    _timer = Timer(const Duration(milliseconds: 2000), () {
      _goTo(const LoginScreen());
    });

    try {
      await Future.delayed(const Duration(milliseconds: 800));
      if (!mounted || _navigated) return;

      final authProvider = Provider.of<AuthProvider>(context, listen: false);
      final isAuth = await authProvider.checkInitialAuth().timeout(
        const Duration(milliseconds: 1000),
        onTimeout: () => false,
      );

      if (isAuth) {
        _goTo(const DashboardScreen());
      } else {
        _goTo(const LoginScreen());
      }
    } catch (_) {
      _goTo(const LoginScreen());
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.primary,
      body: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: () => _goTo(const LoginScreen()),
        child: Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.15),
                        blurRadius: 20,
                        offset: const Offset(0, 10),
                      ),
                    ],
                  ),
                  child: const Icon(
                    Icons.school,
                    size: 64,
                    color: AppColors.primary,
                  ),
                ),
                const SizedBox(height: 24),
                const Text(
                  'IndianAgriExam',
                  style: TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                    fontSize: 28,
                    letterSpacing: 0.5,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'Excellence in Agricultural Competitive Exams',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color: Colors.white.withOpacity(0.9),
                    fontSize: 14,
                  ),
                ),
                const SizedBox(height: 48),
                const SizedBox(
                  width: 32,
                  height: 32,
                  child: CircularProgressIndicator(
                    strokeWidth: 3,
                    valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                  ),
                ),
                const SizedBox(height: 24),
                TextButton(
                  onPressed: () => _goTo(const LoginScreen()),
                  child: Text(
                    'Get Started →',
                    style: TextStyle(
                      color: Colors.white.withOpacity(0.85),
                      fontWeight: FontWeight.w600,
                      fontSize: 15,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
