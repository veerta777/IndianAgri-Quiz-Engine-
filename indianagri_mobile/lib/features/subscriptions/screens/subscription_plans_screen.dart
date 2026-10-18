import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_styles.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/state_views.dart';

class SubscriptionPlansScreen extends StatefulWidget {
  const SubscriptionPlansScreen({super.key});

  @override
  State<SubscriptionPlansScreen> createState() => _SubscriptionPlansScreenState();
}

class _SubscriptionPlansScreenState extends State<SubscriptionPlansScreen> {
  bool _isLoading = true;
  String? _errorMessage;
  List<dynamic> _plans = [];
  int? _checkingOutPlanId;

  @override
  void initState() {
    super.initState();
    _fetchPlans();
  }

  Future<void> _fetchPlans() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiClient.get(ApiEndpoints.subscriptionPlans);
      if (res is List<dynamic>) {
        setState(() {
          _plans = res;
          _isLoading = false;
        });
        return;
      }
      setState(() {
        _errorMessage = 'Could not load subscription plans.';
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _errorMessage = e.toString();
        _isLoading = false;
      });
    }
  }

  Future<void> _handleCheckout(int planId) async {
    setState(() {
      _checkingOutPlanId = planId;
    });

    try {
      final res = await ApiClient.post(
        ApiEndpoints.checkoutUrl,
        body: {'plan_id': planId},
      );

      if (res is Map<String, dynamic> && res.containsKey('checkout_url')) {
        final urlStr = res['checkout_url'].toString();
        final uri = Uri.parse(urlStr);
        if (await canLaunchUrl(uri)) {
          await launchUrl(uri, mode: LaunchMode.externalApplication);
        } else {
          throw 'Could not launch payment gateway URL: $urlStr';
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Payment initiation failed: $e'), backgroundColor: AppColors.incorrectRed),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _checkingOutPlanId = null;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Membership Plans')),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const LoadingView(message: 'Loading subscription packages...');
    }

    if (_errorMessage != null) {
      return ErrorStateView(message: _errorMessage!, onRetry: _fetchPlans);
    }

    if (_plans.isEmpty) {
      return const EmptyStateView(
        title: 'No Plans Available',
        description: 'New subscription plans are currently being configured by administration.',
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _plans.length,
      itemBuilder: (context, index) {
        final plan = _plans[index] as Map<String, dynamic>;
        final id = (plan['id'] as num).toInt();
        final name = plan['name'] ?? 'Membership Plan';
        final price = (plan['price'] as num?)?.toDouble() ?? 0.0;
        final currency = plan['currency'] ?? 'INR';
        final durationVal = plan['duration_value'] ?? 1;
        final durationUnit = plan['duration_unit'] ?? 'months';
        final accessType = plan['access_type'] ?? 'all_access';
        final description = plan['description'] ?? '';
        final isFeatured = plan['is_featured'] == 1;

        return Container(
          margin: const EdgeInsets.only(bottom: 16),
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: AppStyles.borderRadiusL,
            border: Border.all(
              color: isFeatured ? AppColors.primary : AppColors.cardBorderLight,
              width: isFeatured ? 2.0 : 1.0,
            ),
            boxShadow: AppStyles.cardShadow,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              if (isFeatured) ...[
                Align(
                  alignment: Alignment.centerRight,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                    decoration: BoxDecoration(
                      color: AppColors.primary,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Text(
                      'MOST POPULAR',
                      style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 10),
                    ),
                  ),
                ),
              ],
              Text(
                name,
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
              ),
              const SizedBox(height: 4),
              Text(
                accessType == 'all_access' ? 'Full Access to All Examinations & Mocks' : 'Target Examination Specific Pass',
                style: const TextStyle(fontSize: 12, color: AppColors.textSecondaryLight),
              ),
              const SizedBox(height: 16),
              Row(
                crossAxisAlignment: CrossAxisAlignment.baseline,
                textBaseline: TextBaseline.alphabetic,
                children: [
                  Text(
                    '₹${price.toStringAsFixed(0)}',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 32, color: AppColors.primary),
                  ),
                  const SizedBox(width: 6),
                  Text(
                    '/ $durationVal $durationUnit',
                    style: const TextStyle(fontSize: 13, color: AppColors.textSecondaryLight),
                  ),
                ],
              ),
              if (description.isNotEmpty) ...[
                const SizedBox(height: 12),
                Text(
                  description,
                  style: const TextStyle(fontSize: 13, color: AppColors.textSecondaryLight, height: 1.4),
                ),
              ],
              const SizedBox(height: 20),
              AppButton(
                label: 'Subscribe via Razorpay / WooCommerce',
                icon: Icons.lock_open,
                isLoading: _checkingOutPlanId == id,
                onPressed: () => _handleCheckout(id),
              ),
            ],
          ),
        );
      },
    );
  }
}
