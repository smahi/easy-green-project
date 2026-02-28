import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';

import 'package:easy_green/main.dart';
import 'package:easy_green/providers/app_provider.dart';
import 'package:easy_green/providers/auth_provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  setUp(() {
    SharedPreferences.setMockInitialValues({});
  });

  testWidgets('App starts and routes to Login screen', (WidgetTester tester) async {
    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider(create: (_) => AppProvider()),
          ChangeNotifierProvider(create: (_) => AuthProvider()),
        ],
        child: const EasyGreenApp(),
      ),
    );

    // Initial pump
    await tester.pumpAndSettle();

    // Verify Login Screen is present (we look for the icon and a TextFormField with email icon)
    expect(find.byIcon(Icons.eco), findsOneWidget);
    expect(find.byIcon(Icons.email), findsOneWidget);
    expect(find.byIcon(Icons.lock), findsOneWidget);
  });
}
