<?php
include 'db.php'; // Ensure this path is correct, assuming login.php is in the root directory.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = ''; // Initialize error variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? ''); // Trim whitespace
    $pass = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($email) || empty($pass)) {
        $error = "Email dan password tidak boleh kosong.";
    } else {
        // Use prepared statement for security
        $stmt = $conn->prepare("SELECT User_ID, Email, Password, Role FROM User WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // Verify password
            if (password_verify($pass, $row['Password'])) {
                $_SESSION['user_id'] = $row['User_ID'];
                $_SESSION['role'] = $row['Role'];
                $_SESSION['nama_user'] = $row['Nama_User'] ?? null; // Store Nama_User if available in user table

                // Redirect based on role
                if ($row['Role'] == 'peserta') {
                    header("Location: peserta/dashboard.php");
                    exit;
                } elseif ($row['Role'] == 'mentor') {
                    header("Location: mentor/dashboard.php");
                    exit;
                } elseif ($row['Role'] == 'admin') {
                    header("Location: admin/dashboard.php");
                    exit;
                } else {
                    $error = "Role pengguna tidak dikenali.";
                }
            } else {
                $error = "Password yang Anda masukkan salah.";
            }
        } else {
            $error = "Email tidak terdaftar.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EduHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #3498db, #2980b9); /* Pleasant blue gradient */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px; /* Padding for small screens */
        }

        .login-card {
            background-color: #ffffff;
            border-radius: 1.25rem; /* More rounded corners */
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2); /* More prominent shadow */
            padding: 3rem; /* Increased padding */
            width: 100%;
            max-width: 450px; /* Slightly wider for better form layout */
            animation: fadeInScale 0.5s ease-out; /* Animation on load */
        }

        .login-card h2 {
            font-weight: 700;
            color: #34495e;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem; /* Larger heading */
        }

        .login-card h2 .fas {
            margin-right: 15px;
            color: #3498db; /* Blue icon color */
        }

        .form-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 0.5rem;
            display: flex; /* For label icons */
            align-items: center;
        }

        .form-label .fas {
            margin-right: 8px;
            color: #7f8c8d; /* Muted icon color */
        }

        .form-control {
            border-radius: 0.75rem; /* More rounded input fields */
            padding: 0.85rem 1.25rem; /* More padding */
            border: 1px solid #ced4da;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            border-color: #3498db; /* Primary blue border on focus */
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25); /* Blue shadow on focus */
        }

        .btn-primary-custom {
            background-color: #3498db; /* Consistent primary blue */
            border-color: #3498db;
            color: #fff;
            border-radius: 0.75rem; /* More rounded button */
            padding: 0.9rem 1.5rem; /* More padding */
            font-weight: 600;
            transition: background-color 0.2s ease, transform 0.1s ease;
            width: 100%; /* Full width button */
            font-size: 1.1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-primary-custom:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-3px); /* Lift effect */
        }

        .alert-danger-custom {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-danger-custom .fas {
            font-size: 1.5rem;
            color: #dc3545; /* Red icon */
        }

        .register-link {
            font-size: 0.95rem;
            color: #6c757d;
            margin-top: 1.5rem;
        }

        .register-link a {
            color: #3498db;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .register-link a:hover {
            color: #2980b9;
            text-decoration: underline;
        }

        /* Animations */
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body class="bg-light d-flex justify-content-center align-items-center">

    <div class="login-card">
        <h2 class="text-center mb-4">
            <i class="fas fa-lock"></i> Login EduHub
        </h2>

        <?php if(isset($error) && !empty($error)): ?>
            <div class="alert alert-danger-custom">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i> Email
                </label>
                <input name="email" type="email" class="form-control" id="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="fas fa-key"></i> Password
                </label>
                <input name="password" type="password" class="form-control" id="password" required>
            </div>
            <button type="submit" class="btn btn-primary-custom">
                <i class="fas fa-sign-in-alt me-2"></i> Login
            </button>
        </form>

        <p class="text-center register-link">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>