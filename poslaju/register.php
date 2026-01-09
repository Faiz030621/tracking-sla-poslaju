<?php
// register.php
require_once 'config.php';

$error_message = '';
$success_message = '';

// Check if user is already logged in
if (isLoggedIn()) {
    header('Location: admin.php');
    exit();
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = sanitizeInput($_POST['role'] ?? 'staff');

    if (!empty($username) && !empty($email) && !empty($full_name) && !empty($password) && !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            $error_message = 'Password dan pengesahan password tidak sama';
        } elseif (strlen($password) < 6) {
            $error_message = 'Password mesti sekurang-kurangnya 6 aksara';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Email tidak sah';
        } else {
            $conn = createConnection();

            // Check if username or email already exists
            $checkSql = "SELECT id FROM admin_users WHERE username = ? OR email = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("ss", $username, $email);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                $error_message = 'Username atau email sudah wujud';
            } else {
                // Insert new user
                $hashed_password = hashPassword($password);
                $insertSql = "INSERT INTO admin_users (username, email, password, full_name, role, status) VALUES (?, ?, ?, ?, ?, 'active')";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $role);

                if ($insertStmt->execute()) {
                    header('Location: index.php?registered=1');
                    exit();
                } else {
                    $error_message = 'Ralat semasa mendaftar akaun. Sila cuba lagi.';
                }

                $insertStmt->close();
            }

            $checkStmt->close();
            $conn->close();
        }
    } else {
        $error_message = 'Sila isi semua medan yang diperlukan';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <title>Admin Registration - Poslaju Tracking System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="images/sasia-logo.png"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-image: url('images/bg-login.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
        }

        .register-container {
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }

        .input-group {
            position: relative;
        }

        .input-group input, .input-group select {
            padding-left: 3rem;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0033A0 0%, #0055D4 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0055D4 0%, #0077FF 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 51, 160, 0.3);
        }

        .register-header {
            background: linear-gradient(135deg, #0033A0 0%, #0055D4 100%);
        }

        .floating-shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        .floating-shape:nth-child(1) {
            top: 20%;
            left: 10%;
            width: 80px;
            height: 80px;
            animation-delay: 0s;
        }

        .floating-shape:nth-child(2) {
            top: 60%;
            right: 15%;
            width: 60px;
            height: 60px;
            animation-delay: 2s;
        }

        .floating-shape:nth-child(3) {
            bottom: 20%;
            left: 20%;
            width: 40px;
            height: 40px;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .alert {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .company-logo {
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
        }
    </style>
</head>

<body>
    <!-- Floating Background Shapes -->
    <div class="floating-shape"></div>
    <div class="floating-shape"></div>
    <div class="floating-shape"></div>

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="register-container w-full max-w-md rounded-2xl overflow-hidden">
            <!-- Header -->
            <div class="register-header px-8 py-8 text-center">
                <img
                    alt="Poslaju logo"
                    class="company-logo mx-auto mb-4"
                    height="60"
                    src="images/logo-putih-sasia.png"
                    width="60"
                />
                <h1 class="text-white font-bold text-2xl mb-2">
                    Create Account
                </h1>
                <p class="text-white/90 text-sm font-medium">
                    Poslaju Tracking System
                </p>
            </div>

            <!-- Registration Form -->
            <div class="p-8">
                <?php if (!empty($error_message)): ?>
                <div class="alert bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span class="text-sm font-medium"><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <!-- Username -->
                    <div class="input-group">
                        <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">
                            Username
                        </label>
                        <i class="input-icon fas fa-user"></i>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            placeholder="Masukkan username"
                            required
                            autocomplete="username"
                        />
                    </div>

                    <!-- Email -->
                    <div class="input-group">
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                            Email
                        </label>
                        <i class="input-icon fas fa-envelope"></i>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            placeholder="Masukkan email"
                            required
                            autocomplete="email"
                        />
                    </div>

                    <!-- Full Name -->
                    <div class="input-group">
                        <label for="full_name" class="block text-sm font-semibold text-gray-700 mb-2">
                            Nama Penuh
                        </label>
                        <i class="input-icon fas fa-user-circle"></i>
                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            placeholder="Masukkan nama penuh"
                            required
                            autocomplete="name"
                        />
                    </div>

                    <!-- Role -->
                    <div class="input-group">
                        <label for="role" class="block text-sm font-semibold text-gray-700 mb-2">
                            Peranan
                        </label>
                        <i class="input-icon fas fa-user-tag"></i>
                        <select
                            id="role"
                            name="role"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            required
                        >
                            <option value="staff">Staff</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <!-- Password -->
                    <div class="input-group">
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                            Password
                        </label>
                        <i class="input-icon fas fa-lock"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            placeholder="Masukkan password"
                            required
                            autocomplete="new-password"
                            minlength="6"
                        />
                    </div>

                    <!-- Confirm Password -->
                    <div class="input-group">
                        <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-2">
                            Sahkan Password
                        </label>
                        <i class="input-icon fas fa-lock"></i>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            placeholder="Sahkan password"
                            required
                            autocomplete="new-password"
                            minlength="6"
                        />
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="btn-primary w-full text-white font-semibold py-3 px-6 rounded-xl focus:outline-none focus:ring-4 focus:ring-blue-300"
                    >
                        <i class="fas fa-user-plus mr-2"></i>
                        Daftar Akaun
                    </button>
                </form>

                <!-- Login Link -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Sudah ada akaun?
                        <a href="index.php" class="text-blue-600 font-semibold hover:text-blue-700 transition-colors duration-200">
                            Log masuk di sini
                        </a>
                    </p>
                </div>

                <!-- Footer -->
                <div class="mt-8 text-center border-t pt-6">
                    <p class="text-xs text-gray-500">
                        © <?php echo date('Y'); ?> <?php echo getAppConfig('company_name'); ?>. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add focus effects to inputs
            const inputs = document.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    const icon = this.parentElement.querySelector('.input-icon');
                    if (icon) icon.style.color = '#0033A0';
                });

                input.addEventListener('blur', function() {
                    const icon = this.parentElement.querySelector('.input-icon');
                    if (icon) icon.style.color = '#6b7280';
                });
            });

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });

            // Password confirmation validation
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');

            function validatePassword() {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Password tidak sama');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }

            password.addEventListener('change', validatePassword);
            confirmPassword.addEventListener('keyup', validatePassword);
        });
    </script>
</body>
</html>
