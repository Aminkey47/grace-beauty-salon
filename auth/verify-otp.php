<?php

session_start();

require_once __DIR__ . '/../config/database.php';

// Ensure the customer has started registration
if (!isset($_SESSION['pending_verification_email'])) {
    header('Location: register.php');
    exit;
}

$email = $_SESSION['pending_verification_email'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $otp = trim($_POST['otp'] ?? '');

    // Validate OTP format
    if (!preg_match('/^[0-9]{6}$/', $otp)) {

        $error = 'Please enter a valid six-digit verification code.';

    } else {

        try {

            // Find the customer
            $userQuery = $pdo->prepare(
                "SELECT id FROM users WHERE email = ? AND is_verified = 0"
            );

            $userQuery->execute([$email]);

            $user = $userQuery->fetch();

            if (!$user) {

                $error = 'Account not found or already verified.';

            } else {

                // Retrieve the latest unused OTP
                $otpQuery = $pdo->prepare(
                    "SELECT id, otp_hash, expires_at, attempts
                     FROM otp_verifications
                     WHERE user_id = ? AND consumed_at IS NULL
                     ORDER BY id DESC
                     LIMIT 1"
                );

                $otpQuery->execute([$user['id']]);

                $otpRecord = $otpQuery->fetch();

                if (!$otpRecord) {

                    $error = 'No active verification code found. Please register again.';

                } elseif ((int)$otpRecord['attempts'] >= 3) {

                    $error = 'You have exceeded the maximum number of attempts. Please register again.';

                } elseif (strtotime($otpRecord['expires_at']) < time()) {

                    // Invalidate expired OTP
                    $expireOtp = $pdo->prepare(
                        "UPDATE otp_verifications
                         SET consumed_at = NOW()
                         WHERE id = ?"
                    );

                    $expireOtp->execute([$otpRecord['id']]);

                    $error = 'Your verification code has expired. Please register again.';

                } elseif (password_verify($otp, $otpRecord['otp_hash'])) {

                    // Mark the OTP as used
                    $pdo->beginTransaction();

                    $consumeOtp = $pdo->prepare(
                        "UPDATE otp_verifications
                         SET consumed_at = NOW()
                         WHERE id = ?"
                    );

                    $consumeOtp->execute([$otpRecord['id']]);

                    // Verify the customer's account
                    $verifyUser = $pdo->prepare(
                        "UPDATE users
                         SET is_verified = 1
                         WHERE id = ?"
                    );

                    $verifyUser->execute([$user['id']]);

                    $pdo->commit();

                    // Remove pending verification session
                    unset($_SESSION['pending_verification_email']);

                    // Redirect to login
                    $_SESSION['verification_success'] =
                        'Your account has been verified successfully. You can now log in.';

                    header('Location: login.php');
                    exit;

                } else {

                    // Increase failed attempt count
                    $newAttempts = (int)$otpRecord['attempts'] + 1;

                    if ($newAttempts >= 3) {

                        $updateAttempts = $pdo->prepare(
                            "UPDATE otp_verifications
                             SET attempts = ?, consumed_at = NOW()
                             WHERE id = ?"
                        );

                        $updateAttempts->execute([
                            $newAttempts,
                            $otpRecord['id']
                        ]);

                        $error = 'Incorrect code. You have used all three attempts. Please register again.';

                    } else {

                        $updateAttempts = $pdo->prepare(
                            "UPDATE otp_verifications
                             SET attempts = ?
                             WHERE id = ?"
                        );

                        $updateAttempts->execute([
                            $newAttempts,
                            $otpRecord['id']
                        ]);

                        $remaining = 3 - $newAttempts;

                        $error = "Incorrect verification code. You have $remaining attempt(s) remaining.";
                    }
                }
            }

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $error = 'Something went wrong. Please try again.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Verify Account | Grace Beauty Salon</title>

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


<body class="min-h-screen bg-violet-light flex items-center justify-center p-5 font-sans">

    <div class="bg-white w-full max-w-md rounded-2xl shadow-xl p-8 md:p-10">

        <div class="text-center mb-8">

            <h1 class="font-serif text-3xl font-bold text-violet-dark">
                Grace <span class="text-burgundy">Beauty</span>
            </h1>

            <div class="text-5xl mt-7 mb-5">
                ✉️
            </div>

            <h2 class="font-serif text-3xl text-violet-dark">
                Verify Your Email
            </h2>

            <p class="text-gray-500 mt-3 leading-6">
                We've sent a six-digit verification code to:
            </p>

            <p class="font-semibold text-violet-dark mt-2">
                <?= htmlspecialchars($email) ?>
            </p>

        </div>


        <!-- Display error messages -->
        <?php if (!empty($error)): ?>

            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6 text-sm">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <form method="POST" action="" class="space-y-6">

            <div>

                <label class="block text-sm font-semibold mb-2 text-gray-700">
                    Verification Code
                </label>

                <input
                    type="text"
                    name="otp"
                    maxlength="6"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    placeholder="Enter your 6-digit code"
                    required
                    autofocus
                    class="w-full border border-gray-300 rounded-lg px-4 py-4 text-center text-2xl tracking-[10px] font-bold focus:outline-none focus:ring-2 focus:ring-violet"
                >

            </div>


            <button
                type="submit"
                class="w-full bg-violet-dark hover:bg-burgundy text-white font-semibold py-4 rounded-lg transition">

                Verify Account

            </button>

        </form>


        <p class="text-center text-sm text-gray-500 mt-7">
            Didn't receive the code? Check your spam folder.
        </p>


        <p class="text-center mt-5">

            <a href="../index.php"
               class="text-sm text-violet-dark hover:text-burgundy font-semibold">

                ← Back to Homepage

            </a>

        </p>

    </div>

</body>

</html>