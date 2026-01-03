# Flutter Mobile App Guide - Sistem Absensi Mahasiswa

Guide implementasi mobile app menggunakan Flutter dengan arsitektur MVVM + Provider untuk consume API Sistem Absensi Mahasiswa.

## Tech Stack

- Flutter 3.x
- Provider (State Management)
- Dio (HTTP Client)
- Geolocator (GPS)
- SharedPreferences (Local Storage)

## API Base URL

```
Development: http://YOUR_LOCAL_IP:8000/api/v1
Production: https://your-domain.com/api/v1
```

> Gunakan IP komputer (bukan localhost) agar bisa diakses dari emulator/device.

## Arsitektur MVVM

```
┌─────────────────────────────────────────────────────┐
│                      VIEW                           │
│              (Screens/Widgets)                      │
│         UI Layer - Hanya tampilan                   │
└─────────────────────┬───────────────────────────────┘
                      │ observes (Consumer/Provider.of)
                      ▼
┌─────────────────────────────────────────────────────┐
│                   VIEWMODEL                         │
│           (ChangeNotifier/Provider)                 │
│      State + Business Logic + notifyListeners()     │
└─────────────────────┬───────────────────────────────┘
                      │ calls
                      ▼
┌─────────────────────────────────────────────────────┐
│                    MODEL                            │
│           (Repositories + Services)                 │
│         Data Layer - API calls, Local DB            │
└─────────────────────────────────────────────────────┘
```


## Project Structure

```
lib/
├── main.dart                      # Entry point + Provider setup
│
├── core/                          # Shared utilities
│   ├── config/
│   │   └── api_config.dart        # Base URL, endpoints
│   ├── network/
│   │   └── dio_client.dart        # HTTP client dengan interceptor
│   └── utils/
│       └── result.dart            # Success/Error wrapper
│
├── data/                          # MODEL LAYER
│   ├── models/
│   │   ├── user_model.dart
│   │   ├── schedule_model.dart
│   │   ├── attendance_model.dart
│   │   └── location_model.dart
│   ├── repositories/
│   │   ├── auth_repository.dart
│   │   └── attendance_repository.dart
│   └── services/
│       └── location_service.dart  # GPS service
│
├── viewmodels/                    # VIEWMODEL LAYER
│   ├── auth_viewmodel.dart
│   ├── home_viewmodel.dart
│   └── attendance_viewmodel.dart
│
└── views/                         # VIEW LAYER
    ├── screens/
    │   ├── splash_screen.dart
    │   ├── login_screen.dart
    │   ├── home_screen.dart
    │   ├── attendance_screen.dart
    │   └── history_screen.dart
    └── widgets/
        ├── schedule_card.dart
        └── loading_button.dart
```

## Dependencies

```yaml
# pubspec.yaml
dependencies:
  flutter:
    sdk: flutter
  
  # State Management
  provider: ^6.1.1
  
  # Networking
  dio: ^5.4.0
  
  # Local Storage
  shared_preferences: ^2.2.2
  
  # Location/GPS
  geolocator: ^11.0.0
  permission_handler: ^11.1.0
  
  # Utils
  intl: ^0.18.1              # Date formatting
  flutter_secure_storage: ^9.0.0  # Secure token storage
```


## Platform Setup

### Android (android/app/src/main/AndroidManifest.xml)

```xml
<manifest xmlns:android="http://schemas.android.com/apk/res/android">
    
    <!-- Permissions -->
    <uses-permission android:name="android.permission.INTERNET"/>
    <uses-permission android:name="android.permission.ACCESS_FINE_LOCATION"/>
    <uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION"/>
    
    <application
        android:usesCleartextTraffic="true"  <!-- Untuk HTTP development -->
        ...
    >
    </application>
</manifest>
```

### iOS (ios/Runner/Info.plist)

```xml
<key>NSLocationWhenInUseUsageDescription</key>
<string>Aplikasi membutuhkan akses lokasi untuk absensi</string>
<key>NSLocationAlwaysUsageDescription</key>
<string>Aplikasi membutuhkan akses lokasi untuk absensi</string>
```

---

## Implementation Code

### 1. Core - API Config

```dart
// lib/core/config/api_config.dart

class ApiConfig {
  // Ganti dengan IP komputer kamu
  static const String baseUrl = 'http://192.168.1.100:8000/api/v1';
  
  // Auth
  static const String login = '/login';
  static const String logout = '/logout';
  
  // Attendance
  static const String todaySchedules = '/schedules/today';
  static const String attendance = '/attendance';
  static const String history = '/attendance/history';
}
```


### 2. Core - Dio Client

```dart
// lib/core/network/dio_client.dart

import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';

class DioClient {
  late Dio _dio;
  
  DioClient() {
    _dio = Dio(BaseOptions(
      baseUrl: ApiConfig.baseUrl,
      connectTimeout: const Duration(seconds: 30),
      receiveTimeout: const Duration(seconds: 30),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    ));
    
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        // Add token to header
        final prefs = await SharedPreferences.getInstance();
        final token = prefs.getString('token');
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        return handler.next(options);
      },
      onError: (error, handler) {
        // Handle 401 Unauthorized
        if (error.response?.statusCode == 401) {
          // Redirect to login
        }
        return handler.next(error);
      },
    ));
  }
  
  Dio get dio => _dio;
}
```

### 3. Core - Result Wrapper

```dart
// lib/core/utils/result.dart

class Result<T> {
  final T? data;
  final String? error;
  final bool isSuccess;
  
  Result.success(this.data) : error = null, isSuccess = true;
  Result.error(this.error) : data = null, isSuccess = false;
}
```


### 4. Models

```dart
// lib/data/models/user_model.dart

class UserModel {
  final int id;
  final String name;
  final String email;
  final String nim;
  final String role;
  
  UserModel({
    required this.id,
    required this.name,
    required this.email,
    required this.nim,
    required this.role,
  });
  
  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'],
      name: json['name'],
      email: json['email'],
      nim: json['nim'] ?? '',
      role: json['role'],
    );
  }
}
```

```dart
// lib/data/models/schedule_model.dart

class ScheduleModel {
  final int id;
  final String courseName;
  final String locationName;
  final String startTime;
  final String endTime;
  final double latitude;
  final double longitude;
  final double radius;
  
  ScheduleModel({
    required this.id,
    required this.courseName,
    required this.locationName,
    required this.startTime,
    required this.endTime,
    required this.latitude,
    required this.longitude,
    required this.radius,
  });
  
  factory ScheduleModel.fromJson(Map<String, dynamic> json) {
    return ScheduleModel(
      id: json['id'],
      courseName: json['course']['name'],
      locationName: json['location']['name'],
      startTime: json['start_time'],
      endTime: json['end_time'],
      latitude: double.parse(json['location']['latitude'].toString()),
      longitude: double.parse(json['location']['longitude'].toString()),
      radius: double.parse(json['location']['radius'].toString()),
    );
  }
}
```

```dart
// lib/data/models/attendance_model.dart

class AttendanceModel {
  final int id;
  final String courseName;
  final String status;
  final double distance;
  final String createdAt;
  
  AttendanceModel({
    required this.id,
    required this.courseName,
    required this.status,
    required this.distance,
    required this.createdAt,
  });
  
  factory AttendanceModel.fromJson(Map<String, dynamic> json) {
    return AttendanceModel(
      id: json['id'],
      courseName: json['schedule']['course']['name'],
      status: json['status'],
      distance: double.parse(json['distance'].toString()),
      createdAt: json['created_at'],
    );
  }
}
```


### 5. Repositories

```dart
// lib/data/repositories/auth_repository.dart

import 'package:shared_preferences/shared_preferences.dart';
import '../../core/network/dio_client.dart';
import '../../core/config/api_config.dart';
import '../../core/utils/result.dart';
import '../models/user_model.dart';

class AuthRepository {
  final DioClient _dioClient;
  
  AuthRepository(this._dioClient);
  
  Future<Result<UserModel>> login(String email, String password) async {
    try {
      final response = await _dioClient.dio.post(
        ApiConfig.login,
        data: {'email': email, 'password': password},
      );
      
      // Save token
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('token', response.data['token']);
      
      final user = UserModel.fromJson(response.data['user']);
      return Result.success(user);
    } catch (e) {
      return Result.error('Login gagal. Periksa email dan password.');
    }
  }
  
  Future<Result<void>> logout() async {
    try {
      await _dioClient.dio.post(ApiConfig.logout);
      
      // Clear token
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('token');
      
      return Result.success(null);
    } catch (e) {
      return Result.error('Logout gagal');
    }
  }
  
  Future<bool> isLoggedIn() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('token') != null;
  }
}
```

```dart
// lib/data/repositories/attendance_repository.dart

import '../../core/network/dio_client.dart';
import '../../core/config/api_config.dart';
import '../../core/utils/result.dart';
import '../models/schedule_model.dart';
import '../models/attendance_model.dart';

class AttendanceRepository {
  final DioClient _dioClient;
  
  AttendanceRepository(this._dioClient);
  
  Future<Result<List<ScheduleModel>>> getTodaySchedules() async {
    try {
      final response = await _dioClient.dio.get(ApiConfig.todaySchedules);
      
      final schedules = (response.data['data'] as List)
          .map((json) => ScheduleModel.fromJson(json))
          .toList();
      
      return Result.success(schedules);
    } catch (e) {
      return Result.error('Gagal memuat jadwal');
    }
  }
  
  Future<Result<Map<String, dynamic>>> submitAttendance({
    required int scheduleId,
    required double latitude,
    required double longitude,
  }) async {
    try {
      final response = await _dioClient.dio.post(
        ApiConfig.attendance,
        data: {
          'schedule_id': scheduleId,
          'latitude': latitude,
          'longitude': longitude,
        },
      );
      
      return Result.success(response.data);
    } catch (e) {
      return Result.error('Gagal submit absensi');
    }
  }
  
  Future<Result<List<AttendanceModel>>> getHistory({int page = 1}) async {
    try {
      final response = await _dioClient.dio.get(
        ApiConfig.history,
        queryParameters: {'page': page},
      );
      
      final history = (response.data['data'] as List)
          .map((json) => AttendanceModel.fromJson(json))
          .toList();
      
      return Result.success(history);
    } catch (e) {
      return Result.error('Gagal memuat riwayat');
    }
  }
}
```


### 6. Services - Location/GPS

```dart
// lib/data/services/location_service.dart

import 'package:geolocator/geolocator.dart';
import '../../core/utils/result.dart';

class LocationService {
  
  Future<Result<Position>> getCurrentLocation() async {
    try {
      // Check if location service enabled
      bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        return Result.error('Layanan lokasi tidak aktif');
      }
      
      // Check permission
      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) {
          return Result.error('Izin lokasi ditolak');
        }
      }
      
      if (permission == LocationPermission.deniedForever) {
        return Result.error('Izin lokasi ditolak permanen. Aktifkan di Settings.');
      }
      
      // Get current position
      Position position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );
      
      return Result.success(position);
    } catch (e) {
      return Result.error('Gagal mendapatkan lokasi');
    }
  }
}
```


### 7. ViewModels

```dart
// lib/viewmodels/auth_viewmodel.dart

import 'package:flutter/material.dart';
import '../data/repositories/auth_repository.dart';
import '../data/models/user_model.dart';

class AuthViewModel extends ChangeNotifier {
  final AuthRepository _authRepository;
  
  AuthViewModel(this._authRepository);
  
  bool _isLoading = false;
  UserModel? _user;
  String? _errorMessage;
  
  bool get isLoading => _isLoading;
  UserModel? get user => _user;
  String? get errorMessage => _errorMessage;
  bool get isLoggedIn => _user != null;
  
  Future<bool> login(String email, String password) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();
    
    final result = await _authRepository.login(email, password);
    
    _isLoading = false;
    
    if (result.isSuccess) {
      _user = result.data;
      notifyListeners();
      return true;
    } else {
      _errorMessage = result.error;
      notifyListeners();
      return false;
    }
  }
  
  Future<void> logout() async {
    _isLoading = true;
    notifyListeners();
    
    await _authRepository.logout();
    
    _user = null;
    _isLoading = false;
    notifyListeners();
  }
  
  Future<bool> checkLoginStatus() async {
    return await _authRepository.isLoggedIn();
  }
}
```

```dart
// lib/viewmodels/home_viewmodel.dart

import 'package:flutter/material.dart';
import '../data/repositories/attendance_repository.dart';
import '../data/models/schedule_model.dart';

class HomeViewModel extends ChangeNotifier {
  final AttendanceRepository _attendanceRepository;
  
  HomeViewModel(this._attendanceRepository);
  
  bool _isLoading = false;
  List<ScheduleModel> _schedules = [];
  String? _errorMessage;
  
  bool get isLoading => _isLoading;
  List<ScheduleModel> get schedules => _schedules;
  String? get errorMessage => _errorMessage;
  
  Future<void> loadTodaySchedules() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();
    
    final result = await _attendanceRepository.getTodaySchedules();
    
    _isLoading = false;
    
    if (result.isSuccess) {
      _schedules = result.data!;
    } else {
      _errorMessage = result.error;
    }
    
    notifyListeners();
  }
}
```

```dart
// lib/viewmodels/attendance_viewmodel.dart

import 'package:flutter/material.dart';
import '../data/repositories/attendance_repository.dart';
import '../data/services/location_service.dart';
import '../data/models/attendance_model.dart';

class AttendanceViewModel extends ChangeNotifier {
  final AttendanceRepository _attendanceRepository;
  final LocationService _locationService;
  
  AttendanceViewModel(this._attendanceRepository, this._locationService);
  
  bool _isLoading = false;
  bool _isSubmitting = false;
  List<AttendanceModel> _history = [];
  String? _errorMessage;
  String? _successMessage;
  
  bool get isLoading => _isLoading;
  bool get isSubmitting => _isSubmitting;
  List<AttendanceModel> get history => _history;
  String? get errorMessage => _errorMessage;
  String? get successMessage => _successMessage;
  
  Future<bool> submitAttendance(int scheduleId) async {
    _isSubmitting = true;
    _errorMessage = null;
    _successMessage = null;
    notifyListeners();
    
    // 1. Get current location
    final locationResult = await _locationService.getCurrentLocation();
    
    if (!locationResult.isSuccess) {
      _isSubmitting = false;
      _errorMessage = locationResult.error;
      notifyListeners();
      return false;
    }
    
    final position = locationResult.data!;
    
    // 2. Submit attendance
    final result = await _attendanceRepository.submitAttendance(
      scheduleId: scheduleId,
      latitude: position.latitude,
      longitude: position.longitude,
    );
    
    _isSubmitting = false;
    
    if (result.isSuccess) {
      _successMessage = result.data!['message'];
      notifyListeners();
      return result.data!['status'] == 'hadir';
    } else {
      _errorMessage = result.error;
      notifyListeners();
      return false;
    }
  }
  
  Future<void> loadHistory() async {
    _isLoading = true;
    notifyListeners();
    
    final result = await _attendanceRepository.getHistory();
    
    _isLoading = false;
    
    if (result.isSuccess) {
      _history = result.data!;
    } else {
      _errorMessage = result.error;
    }
    
    notifyListeners();
  }
  
  void clearMessages() {
    _errorMessage = null;
    _successMessage = null;
    notifyListeners();
  }
}
```


### 8. Main.dart - Provider Setup

```dart
// lib/main.dart

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'core/network/dio_client.dart';
import 'data/repositories/auth_repository.dart';
import 'data/repositories/attendance_repository.dart';
import 'data/services/location_service.dart';
import 'viewmodels/auth_viewmodel.dart';
import 'viewmodels/home_viewmodel.dart';
import 'viewmodels/attendance_viewmodel.dart';
import 'views/screens/splash_screen.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    // Initialize dependencies
    final dioClient = DioClient();
    final authRepository = AuthRepository(dioClient);
    final attendanceRepository = AttendanceRepository(dioClient);
    final locationService = LocationService();
    
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(
          create: (_) => AuthViewModel(authRepository),
        ),
        ChangeNotifierProvider(
          create: (_) => HomeViewModel(attendanceRepository),
        ),
        ChangeNotifierProvider(
          create: (_) => AttendanceViewModel(attendanceRepository, locationService),
        ),
      ],
      child: MaterialApp(
        title: 'Absensi Mahasiswa',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          colorScheme: ColorScheme.fromSeed(seedColor: Colors.blue),
          useMaterial3: true,
        ),
        home: const SplashScreen(),
      ),
    );
  }
}
```


### 9. Views - Screens

```dart
// lib/views/screens/splash_screen.dart

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../viewmodels/auth_viewmodel.dart';
import 'login_screen.dart';
import 'home_screen.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _checkAuth();
  }

  Future<void> _checkAuth() async {
    await Future.delayed(const Duration(seconds: 2));
    
    if (!mounted) return;
    
    final authViewModel = Provider.of<AuthViewModel>(context, listen: false);
    final isLoggedIn = await authViewModel.checkLoginStatus();
    
    if (!mounted) return;
    
    Navigator.pushReplacement(
      context,
      MaterialPageRoute(
        builder: (_) => isLoggedIn ? const HomeScreen() : const LoginScreen(),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.location_on, size: 80, color: Colors.blue),
            SizedBox(height: 16),
            Text(
              'Absensi Mahasiswa',
              style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
            ),
            SizedBox(height: 24),
            CircularProgressIndicator(),
          ],
        ),
      ),
    );
  }
}
```

```dart
// lib/views/screens/login_screen.dart

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../viewmodels/auth_viewmodel.dart';
import 'home_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _login() async {
    if (!_formKey.currentState!.validate()) return;
    
    final authViewModel = Provider.of<AuthViewModel>(context, listen: false);
    
    final success = await authViewModel.login(
      _emailController.text.trim(),
      _passwordController.text,
    );
    
    if (!mounted) return;
    
    if (success) {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => const HomeScreen()),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(authViewModel.errorMessage ?? 'Login gagal'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.school, size: 80, color: Colors.blue),
                const SizedBox(height: 16),
                const Text(
                  'Login Mahasiswa',
                  style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 32),
                
                TextFormField(
                  controller: _emailController,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(
                    labelText: 'Email',
                    prefixIcon: Icon(Icons.email),
                    border: OutlineInputBorder(),
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Email wajib diisi';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                
                TextFormField(
                  controller: _passwordController,
                  obscureText: true,
                  decoration: const InputDecoration(
                    labelText: 'Password',
                    prefixIcon: Icon(Icons.lock),
                    border: OutlineInputBorder(),
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Password wajib diisi';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 24),
                
                Consumer<AuthViewModel>(
                  builder: (context, viewModel, child) {
                    return SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton(
                        onPressed: viewModel.isLoading ? null : _login,
                        child: viewModel.isLoading
                            ? const SizedBox(
                                height: 20,
                                width: 20,
                                child: CircularProgressIndicator(strokeWidth: 2),
                              )
                            : const Text('Login'),
                      ),
                    );
                  },
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
```


```dart
// lib/views/screens/home_screen.dart

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../viewmodels/auth_viewmodel.dart';
import '../../viewmodels/home_viewmodel.dart';
import '../../viewmodels/attendance_viewmodel.dart';
import '../widgets/schedule_card.dart';
import 'login_screen.dart';
import 'history_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  @override
  void initState() {
    super.initState();
    // Load schedules when screen opens
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<HomeViewModel>(context, listen: false).loadTodaySchedules();
    });
  }

  Future<void> _logout() async {
    final authViewModel = Provider.of<AuthViewModel>(context, listen: false);
    await authViewModel.logout();
    
    if (!mounted) return;
    
    Navigator.pushReplacement(
      context,
      MaterialPageRoute(builder: (_) => const LoginScreen()),
    );
  }

  Future<void> _submitAttendance(int scheduleId) async {
    final attendanceViewModel = Provider.of<AttendanceViewModel>(context, listen: false);
    
    final success = await attendanceViewModel.submitAttendance(scheduleId);
    
    if (!mounted) return;
    
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          attendanceViewModel.successMessage ?? 
          attendanceViewModel.errorMessage ?? 
          'Unknown error',
        ),
        backgroundColor: success ? Colors.green : Colors.orange,
      ),
    );
    
    attendanceViewModel.clearMessages();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Jadwal Hari Ini'),
        actions: [
          IconButton(
            icon: const Icon(Icons.history),
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const HistoryScreen()),
              );
            },
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: _logout,
          ),
        ],
      ),
      body: Consumer<HomeViewModel>(
        builder: (context, viewModel, child) {
          if (viewModel.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }
          
          if (viewModel.errorMessage != null) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(viewModel.errorMessage!),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () => viewModel.loadTodaySchedules(),
                    child: const Text('Coba Lagi'),
                  ),
                ],
              ),
            );
          }
          
          if (viewModel.schedules.isEmpty) {
            return const Center(
              child: Text('Tidak ada jadwal hari ini'),
            );
          }
          
          return RefreshIndicator(
            onRefresh: () => viewModel.loadTodaySchedules(),
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: viewModel.schedules.length,
              itemBuilder: (context, index) {
                final schedule = viewModel.schedules[index];
                return Consumer<AttendanceViewModel>(
                  builder: (context, attendanceViewModel, child) {
                    return ScheduleCard(
                      schedule: schedule,
                      isSubmitting: attendanceViewModel.isSubmitting,
                      onAttendance: () => _submitAttendance(schedule.id),
                    );
                  },
                );
              },
            ),
          );
        },
      ),
    );
  }
}
```

```dart
// lib/views/screens/history_screen.dart

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../viewmodels/attendance_viewmodel.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<AttendanceViewModel>(context, listen: false).loadHistory();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Riwayat Absensi'),
      ),
      body: Consumer<AttendanceViewModel>(
        builder: (context, viewModel, child) {
          if (viewModel.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }
          
          if (viewModel.history.isEmpty) {
            return const Center(
              child: Text('Belum ada riwayat absensi'),
            );
          }
          
          return ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: viewModel.history.length,
            itemBuilder: (context, index) {
              final attendance = viewModel.history[index];
              return Card(
                child: ListTile(
                  leading: Icon(
                    attendance.status == 'hadir' 
                        ? Icons.check_circle 
                        : Icons.cancel,
                    color: attendance.status == 'hadir' 
                        ? Colors.green 
                        : Colors.red,
                  ),
                  title: Text(attendance.courseName),
                  subtitle: Text('Jarak: ${attendance.distance.toStringAsFixed(2)} m'),
                  trailing: Chip(
                    label: Text(
                      attendance.status.toUpperCase(),
                      style: const TextStyle(color: Colors.white, fontSize: 12),
                    ),
                    backgroundColor: attendance.status == 'hadir' 
                        ? Colors.green 
                        : Colors.red,
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }
}
```


### 10. Widgets

```dart
// lib/views/widgets/schedule_card.dart

import 'package:flutter/material.dart';
import '../../data/models/schedule_model.dart';

class ScheduleCard extends StatelessWidget {
  final ScheduleModel schedule;
  final bool isSubmitting;
  final VoidCallback onAttendance;

  const ScheduleCard({
    super.key,
    required this.schedule,
    required this.isSubmitting,
    required this.onAttendance,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              schedule.courseName,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            
            Row(
              children: [
                const Icon(Icons.location_on, size: 16, color: Colors.grey),
                const SizedBox(width: 4),
                Text(schedule.locationName),
              ],
            ),
            const SizedBox(height: 4),
            
            Row(
              children: [
                const Icon(Icons.access_time, size: 16, color: Colors.grey),
                const SizedBox(width: 4),
                Text('${schedule.startTime} - ${schedule.endTime}'),
              ],
            ),
            const SizedBox(height: 4),
            
            Row(
              children: [
                const Icon(Icons.radar, size: 16, color: Colors.grey),
                const SizedBox(width: 4),
                Text('Radius: ${schedule.radius.toStringAsFixed(0)} m'),
              ],
            ),
            const SizedBox(height: 16),
            
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: isSubmitting ? null : onAttendance,
                icon: isSubmitting 
                    ? const SizedBox(
                        height: 16,
                        width: 16,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.check),
                label: Text(isSubmitting ? 'Memproses...' : 'Absen Sekarang'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
```

---

## App Flow

```
┌──────────────┐
│    Splash    │
│    Screen    │
└──────┬───────┘
       │ check token
       ▼
   ┌───────┐
   │ Token │──── No ────▶ Login Screen
   │ Exist?│                   │
   └───┬───┘                   │ POST /login
       │ Yes                   ▼
       │              ┌────────────────┐
       └─────────────▶│  Home Screen   │
                      │ (Jadwal Hari   │
                      │     Ini)       │
                      └───────┬────────┘
                              │
              ┌───────────────┼───────────────┐
              ▼               ▼               ▼
        ┌──────────┐   ┌──────────┐   ┌──────────┐
        │  Absen   │   │ History  │   │  Logout  │
        │  Button  │   │  Screen  │   │          │
        └────┬─────┘   └──────────┘   └──────────┘
             │
             ▼
      ┌─────────────┐
      │  Get GPS    │
      │  Location   │
      └──────┬──────┘
             │
             ▼
      ┌─────────────┐
      │ POST        │
      │ /attendance │
      └──────┬──────┘
             │
             ▼
      ┌─────────────┐
      │  Show       │
      │  Result     │
      └─────────────┘
```


---

## Testing Tips

### 1. Test API dulu pakai Postman/Swagger
Sebelum coding Flutter, pastikan API jalan dengan benar.

### 2. Gunakan IP komputer, bukan localhost
```dart
// ❌ Salah
static const String baseUrl = 'http://localhost:8000/api/v1';

// ✅ Benar
static const String baseUrl = 'http://192.168.1.100:8000/api/v1';
```

Cek IP dengan command:
- Windows: `ipconfig`
- Mac/Linux: `ifconfig`

### 3. Test di Real Device untuk GPS
Emulator GPS kurang akurat. Gunakan device asli untuk testing lokasi.

### 4. Mock Location untuk Development
Gunakan app "Fake GPS" untuk testing berbagai lokasi.

---

## Common Errors & Solutions

| Error | Penyebab | Solusi |
|-------|----------|--------|
| `SocketException` | Server tidak bisa diakses | Cek IP address, pastikan server running |
| `401 Unauthorized` | Token expired/invalid | Re-login, cek token di SharedPreferences |
| `Location permission denied` | User tolak permission | Show dialog untuk request ulang |
| `XMLHttpRequest error` | CORS (web) | Jalankan di mobile, bukan web |

---

## Next Steps

1. **Setup project Flutter baru**
   ```bash
   flutter create absensi_mahasiswa
   cd absensi_mahasiswa
   ```

2. **Install dependencies**
   ```bash
   flutter pub add provider dio shared_preferences geolocator permission_handler intl
   ```

3. **Copy code dari guide ini**

4. **Ganti IP di `api_config.dart`**

5. **Run app**
   ```bash
   flutter run
   ```

---

## Resources

- [Provider Documentation](https://pub.dev/packages/provider)
- [Dio HTTP Client](https://pub.dev/packages/dio)
- [Geolocator](https://pub.dev/packages/geolocator)
- [Flutter MVVM Tutorial](https://www.filledstacks.com/post/flutter-architecture-my-provider-implementation-guide/)

---

*Guide ini dibuat untuk consume API Sistem Absensi Mahasiswa Geolocation.*
