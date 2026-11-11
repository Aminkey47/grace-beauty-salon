<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';

$errors = [];

$full_name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect and clean form data
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate full name
    if (empty($full_name)) {
        $errors[] = 'Please enter your full name.';
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Validate phone
    if (empty($phone)) {
        $errors[] = 'Please enter your phone number.';
    }

    // Validate password
    if (strlen($password) < 8) {
        $errors[] = 'Your password must contain at least 8 characters.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Your passwords do not match.';
    }

    if (empty($errors)) {
        try {
            // Check whether the email is already registered
            $checkEmail = $pdo->prepare(
                "SELECT id FROM users WHERE email = ? LIMIT 1"
            );

            $checkEmail->execute([$email]);

            if ($checkEmail->fetch()) {
                $errors[] = 'This email address is already registered.';
            } else {

                // Hash the password before saving it
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Generate a six-digit OTP
                $otp = (string) random_int(100000, 999999);

                // Hash the OTP before storing it
                $hashedOtp = password_hash($otp, PASSWORD_DEFAULT);

                // OTP expires after 10 minutes
                $expiresAt = date(
                    'Y-m-d H:i:s',
                    time() + 600
                );

                // Begin database transaction
                $pdo->beginTransaction();

                // Create customer account
                $insertUser = $pdo->prepare(
                    "INSERT INTO users
                    (full_name, email, phone, password, role, is_verified)
                    VALUES (?, ?, ?, ?, 'customer', 0)"
                );

                $insertUser->execute([
                    $full_name,
                    $email,
                    $phone,
                    $hashedPassword
                ]);

                $userId = $pdo->lastInsertId();

                // Save hashed OTP
                $insertOtp = $pdo->prepare(
                    "INSERT INTO otp_verifications
                    (user_id, otp_hash, expires_at)
                    VALUES (?, ?, ?)"
                );

                $insertOtp->execute([
                    $userId,
                    $hashedOtp,
                    $expiresAt
                ]);

                // Send OTP email
                sendOtpEmail($email, $full_name, $otp);

                // Commit transaction
                $pdo->commit();

                // Remember the email for the verification page
                $_SESSION['pending_verification_email'] = $email;

                header('Location: verify-otp.php');
                exit;
            }

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $errors[] = 'Registration could not be completed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account | Grace Beauty Salon</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        violet: {
                            dark: '#542078',
                            DEFAULT: '#7138A8',
                            light: '#F1E8F8'
                        },
                        burgundy: '#9B2348'
                    },
                    fontFamily: {
                        sans: ['DM Sans', 'sans-serif'],
                        serif: ['Playfair Display', 'serif']
                    }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen bg-violet-light font-sans flex items-center justify-center p-5">

    <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl p-8 md:p-10">

        <div class="text-center mb-8">

            <a href="../index.php"
               class="font-serif text-3xl font-bold text-violet-dark">
                Grace <span class="text-burgundy">Beauty</span>
            </a>

            <h1 class="font-serif text-3xl text-violet-dark mt-7">
                Create Your Account
            </h1>

            <p class="text-gray-500 mt-2">
                Join us and book your next beauty appointment.
            </p>

        </div>

        <!-- Display validation errors -->
        <?php if (!empty($errors)): ?>

            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6">

                <ul class="list-disc pl-5 space-y-1 text-sm">

                    <?php foreach ($errors as $error): ?>

                        <li>
                            <?= htmlspecialchars($error) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <!-- Registration Form -->
        <form method="POST" action="" class="space-y-5">

            <div>
                <label class="block text-sm font-semibold mb-2">
                    Full Name
                </label>

                <input
                    type="text"
                    name="full_name"
                    value="<?= htmlspecialchars($full_name) ?>"
                    placeholder="Enter your full name"
                    required
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-violet"
                >
            </div>


            <div>
                <label class="block text-sm font-semibold mb-2">
                    Email Address
                </label>

                <input
                    type="email"
                    name="email"
                    value="<?= htmlspecialchars($email) ?>"
                    placeholder="example@email.com"
                    required
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-violet"
                >
            </div>


            <div>
                <label class="block text-sm font-semibold mb-2">
                    Phone Number
                </label>

                <input
                    type="tel"
                    name="phone"
                    value="<?= htmlspecialchars($phone) ?>"
                    placeholder="e.g. 0712345678"
                    required
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-violet"
                >
            </div>


            <div>
                <label class="block text-sm font-semibold mb-2">
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="At least 8 characters"
                    required
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-violet"
                >
            </div>


            <div>
                <label class="block text-sm font-semibold mb-2">
                    Confirm Password
                </label>

                <input
                    type="password"
                    name="confirm_password"
                    placeholder="Enter your password again"
                    required
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-violet"
                >
            </div>


            <button
                type="submit"
                class="w-full bg-violet-dark hover:bg-burgundy text-white font-semibold py-3.5 rounded-lg transition">
                Create Account
            </button>

        </form>


        <p class="text-center text-sm text-gray-600 mt-7">

            Already have an account?

            <a href="login.php"
               class="text-burgundy font-bold hover:underline">
                Login
            </a>

        </p>

        <p class="text-center mt-5">

            <a href="../index.php"
               class="text-sm text-gray-500 hover:text-violet-dark">
                ← Back to Homepage
            </a>

        </p>

    </div>

</body>
</html>