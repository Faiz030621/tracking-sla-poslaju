<?php
// index.php
require_once 'config.php';

$error_message = '';
$success_message = '';

// Check if user is already logged in
if (isLoggedIn()) {
    header('Location: admin.php');
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $conn = createConnection();
        
        $sql = "SELECT id, username, password, full_name, role 
                FROM admin_users 
                WHERE username = ? AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();

        // FIX: kena simpan result dulu sebelum bind_result
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $db_username, $db_password, $full_name, $role);
            $stmt->fetch();
            
            if (verifyPassword($password, $db_password)) {
                startSecureSession();
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $db_username;
                $_SESSION['full_name'] = $full_name;
                $_SESSION['role'] = $role;
                
                // Update last login
                $updateSql = "UPDATE admin_users SET last_login = NOW() WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("i", $id);
                $updateStmt->execute();
                $updateStmt->close();
                
                header('Location: admin.php');
                exit();
            } else {
                $error_message = 'Username atau password tidak betul';
            }
        } else {
            $error_message = 'Username atau password tidak betul';
        }
        
        $stmt->close();
        $conn->close();
    } else {
        $error_message = 'Sila isi username dan password';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    logout();
}

// Check for registration success
if (isset($_GET['registered'])) {
    $success_message = 'Akaun berjaya didaftarkan! Sila log masuk.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <title>Admin Login - Poslaju Tracking System</title>
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
        
        .login-container {
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group input {
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
            background: linear-gradient(135deg, #d5171f 0%, #b8151c 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #b8151c 0%, #9e1319 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(213, 23, 31, 0.3);
        }
        
        .login-header {
            background: linear-gradient(135deg, #d5171f 0%, #FF1111 100%);
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
        <div class="login-container w-full max-w-md rounded-2xl overflow-hidden">
            <!-- Header -->
            <div class="login-header px-8 py-8 text-center">
                <img
                    alt="Poslaju logo"
                    class="company-logo mx-auto mb-4"
                    height="60"
                    src="images/logo-putih-sasia.png"
                    width="60"
                />
                <h1 class="text-white font-bold text-2xl mb-2">
                    Admin Portal
                </h1>
                <p class="text-white/90 text-sm font-medium">
                    Poslaju Tracking SLA
                </p>
            </div>
            
            <!-- Login Form -->
            <div class="p-8">
                <?php if (!empty($error_message)): ?>
                <div class="alert bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span class="text-sm font-medium"><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                <div class="alert bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span class="text-sm font-medium"><?php echo htmlspecialchars($success_message); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-6">
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
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all duration-200"
                            placeholder="Masukkan username anda"
                            required
                            autocomplete="username"
                        />
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
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all duration-200"
                            placeholder="Masukkan password anda"
                            required
                            autocomplete="current-password"
                        />
                    </div>
                    
                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="btn-primary w-full text-white font-semibold py-3 px-6 rounded-xl focus:outline-none focus:ring-4 focus:ring-red-300"
                    >
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Log Masuk
                    </button>
                </form>
                
                <!-- Register Link -->
                <div class="mt-6 text-center" hidden>
                    <p class="text-sm text-gray-600">
                        Belum ada akaun? 
                        <a href="register.php" class="text-red-600 font-semibold hover:text-red-700 transition-colors duration-200">
                            Daftar di sini
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
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.querySelector('.input-icon').style.color = '#d5171f';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.querySelector('.input-icon').style.color = '#6b7280';
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
        });
    </script>
</body>
</html>