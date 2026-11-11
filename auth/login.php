<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Redirect users who are already logged in.
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: ../admin/index.php');
            exit;

        case 'salonist':
            header('Location: ../salonist/index.php');
            exit;

        case 'customer':
            header('Location: ../customer/index.php');
            exit;
    }
}

$error = '';
$success = '';

// Registration/verification success message.
if (isset($_SESSION['verification_success'])) {
    $success = $_SESSION['verification_success'];
    unset($_SESSION['verification_success']);
}

// Process login.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {

        $error = 'Please enter both your email and password.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } else {

        try {

            $stmt = $pdo->prepare(
                "SELECT * FROM users WHERE email = :email LIMIT 1"
            );

            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {

                $error = 'Incorrect email or password. Please try again.';

            } elseif (!$user['is_active']) {

                $error = 'Your account is currently inactive. Please contact the administrator.';

            } elseif ($user['role'] === 'customer' && !$user['is_verified']) {

                $_SESSION['pending_verification_email'] = $user['email'];

                header('Location: verify-otp.php');
                exit;

            } else {

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                switch ($user['role']) {

                    case 'admin':
                        header('Location: ../admin/index.php');
                        exit;

                    case 'salonist':
                        header('Location: ../salonist/index.php');
                        exit;

                    case 'customer':
                        header('Location: ../customer/index.php');
                        exit;

                    default:
                        session_destroy();
                        $error = 'Invalid account role.';
                }
            }

        } catch (PDOException $e) {

            error_log($e->getMessage());
            $error = 'Something went wrong. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Grace Beauty Salon</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        violet: '#542078',
                        royal: '#7138A8',
                        burgundy: '#9B2348',
                        lavender: '#F1E8F8',
                        charcoal: '#28212D'
                    },
                    fontFamily: {
                        sans: ['DM Sans', 'sans-serif'],
                        display: ['Playfair Display', 'serif']
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'DM Sans', sans-serif;
        }

        .photo-overlay {
            background: linear-gradient(
                to top,
                rgba(40, 20, 50, 0.88),
                rgba(40, 20, 50, 0.05)
            );
        }
    </style>

</head>

<body class="min-h-screen bg-lavender">

<main class="min-h-screen flex items-center justify-center p-4 md:p-8">

    <div class="w-full max-w-6xl bg-white rounded-3xl shadow-2xl overflow-hidden grid md:grid-cols-2">

        <!-- LEFT SIDE: HAIRSTYLING PHOTOGRAPH -->

        <section class="relative min-h-[300px] md:min-h-[680px]">

            <img
                src="https://images.unsplash.com/photo-1675034743339-0b0747047727?auto=format&fit=crop&w=1200&q=85"
                alt="A woman getting her hair done in a beauty salon"
                class="absolute inset-0 w-full h-full object-cover"
                onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=1200&q=85';"
            >

            <div class="photo-overlay absolute inset-0"></div>

            <div class="relative z-10 min-h-[300px] md:min-h-[680px] flex flex-col justify-between p-8 md:p-12 text-white">

                <a href="../index.php" class="text-2xl font-display font-bold tracking-wide">
                    Grace<span class="text-pink-300">.</span>
                </a>

                <div>

                    <p class="uppercase tracking-[4px] text-sm text-purple-200 mb-4">
                        Welcome to Grace Beauty Salon
                    </p>

                    <h1 class="font-display text-4xl md:text-5xl leading-tight mb-5">
                        Beauty begins with feeling good.
                    </h1>

                    <p class="text-white/80 text-base md:text-lg max-w-md leading-relaxed">
                        Take a moment for yourself. Experience professional beauty care in a space designed around you.
                    </p>

                    <div class="mt-8 flex items-center gap-3">

                        <div class="w-12 h-[2px] bg-pink-300"></div>

                        <span class="text-sm tracking-widest uppercase">
                            Your beauty, our passion
                        </span>

                    </div>

                </div>

            </div>

        </section>


        <!-- RIGHT SIDE: LOGIN FORM -->

        <section class="flex items-center justify-center px-6 py-12 md:px-12 lg:px-16">

            <div class="w-full max-w-md">

                <div class="mb-10">

                    <p class="text-burgundy uppercase tracking-[3px] text-xs font-bold mb-3">
                        Your beauty journey
                    </p>

                    <h2 class="font-display text-4xl text-charcoal mb-3">
                        Welcome back
                    </h2>

                    <p class="text-gray-500">
                        Sign in to access your Grace Beauty Salon account.
                    </p>

                </div>


                <!-- SUCCESS MESSAGE -->

                <?php if (!empty($success)): ?>

                    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 mb-6 text-sm">

                        <?= htmlspecialchars($success) ?>

                    </div>

                <?php endif; ?>


                <!-- ERROR MESSAGE -->

                <?php if (!empty($error)): ?>

                    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 mb-6 text-sm">

                        <?= htmlspecialchars($error) ?>

                    </div>

                <?php endif; ?>


                <form method="POST" action="" class="space-y-6">

                    <!-- EMAIL -->

                    <div>

                        <label for="email" class="block text-sm font-semibold text-charcoal mb-2">
                            Email address
                        </label>

                        <input
                            type="email"
                            name="email"
                            id="email"
                            required
                            autocomplete="email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            placeholder="you@example.com"
                            class="w-full px-4 py-3.5 rounded-xl border border-gray-200 focus:border-royal focus:ring-2 focus:ring-purple-100 outline-none transition"
                        >

                    </div>


                    <!-- PASSWORD -->

                    <div>

                        <label for="password" class="block text-sm font-semibold text-charcoal mb-2">
                            Password
                        </label>

                        <div class="relative">

                            <input
                                type="password"
                                name="password"
                                id="password"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                                class="w-full px-4 py-3.5 pr-16 rounded-xl border border-gray-200 focus:border-royal focus:ring-2 focus:ring-purple-100 outline-none transition"
                            >

                            <button
                                type="button"
                                onclick="togglePassword()"
                                id="toggleButton"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-royal font-semibold hover:text-burgundy"
                            >
                                Show
                            </button>

                        </div>

                    </div>


                    <!-- LOGIN BUTTON -->

                    <button
                        type="submit"
                        class="w-full bg-violet hover:bg-burgundy text-white py-4 rounded-xl font-bold transition duration-300 shadow-lg shadow-purple-200"
                    >
                        Sign In
                    </button>

                </form>


                <!-- REGISTRATION LINK -->

                <div class="text-center mt-8 text-sm text-gray-500">

                    Don't have an account?

                    <a href="register.php" class="text-burgundy font-bold hover:underline">
                        Create an account
                    </a>

                </div>


                <!-- HOMEPAGE LINK -->

                <div class="text-center mt-8">

                    <a href="../index.php" class="text-sm text-gray-400 hover:text-royal transition">
                        ← Back to homepage
                    </a>

                </div>

            </div>

        </section>

    </div>

</main>


<script>

    function togglePassword() {

        const password = document.getElementById('password');
        const button = document.getElementById('toggleButton');

        if (password.type === 'password') {

            password.type = 'text';
            button.textContent = 'Hide';

        } else {

            password.type = 'password';
            button.textContent = 'Show';

        }
    }

</script>

</body>
</html>