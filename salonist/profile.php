<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'salonist') {
    header('Location: ../auth/login.php');
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$salonistId = (int) $_SESSION['user_id'];
$message = '';
$messageType = 'success';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $message = 'Your session token is invalid. Please refresh the page.';
        $messageType = 'error';

    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($fullName === '' || $email === '' || $phone === '') {
            $message = 'Please complete all the required fields.';
            $messageType = 'error';

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $messageType = 'error';

        } else {
            try {
                $stmt = $pdo->prepare("
                    SELECT id
                    FROM users
                    WHERE email = ?
                      AND id != ?
                    LIMIT 1
                ");
                $stmt->execute([$email, $salonistId]);

                if ($stmt->fetch()) {
                    $message = 'This email address is already being used by another account.';
                    $messageType = 'error';

                } else {
                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET full_name = ?,
                            email = ?,
                            phone = ?
                        WHERE id = ?
                          AND role = 'salonist'
                    ");

                    $stmt->execute([
                        $fullName,
                        $email,
                        $phone,
                        $salonistId
                    ]);

                    $_SESSION['full_name'] = $fullName;

                    $message = 'Your profile has been updated successfully.';
                }

            } catch (PDOException $e) {
                error_log('Salonist profile update error: ' . $e->getMessage());
                $message = 'Unable to update your profile. Please try again.';
                $messageType = 'error';
            }
        }
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT id, full_name, email, phone, created_at
        FROM users
        WHERE id = ?
          AND role = 'salonist'
        LIMIT 1
    ");

    $stmt->execute([$salonistId]);
    $salonist = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$salonist) {
        session_destroy();
        header('Location: ../auth/login.php');
        exit;
    }

} catch (PDOException $e) {
    error_log('Salonist profile loading error: ' . $e->getMessage());
    die('Unable to load your profile. Please try again later.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Grace Beauty Salon</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-gray-800">

<div class="min-h-screen md:flex">

    <aside class="w-full md:w-64 bg-white border-r border-gray-200 md:min-h-screen">
        <div class="p-6">
            <h1 class="text-2xl font-bold text-purple-800">Grace Beauty</h1>
            <p class="text-sm text-gray-500 mt-1">Salonist Portal</p>
        </div>

        <nav class="px-4 space-y-2">
            <a href="index.php"
               class="block rounded-lg px-4 py-3 text-gray-600 hover:bg-purple-50">
                Dashboard
            </a>

            <a href="appointments.php"
               class="block rounded-lg px-4 py-3 text-gray-600 hover:bg-purple-50">
                My Appointments
            </a>

            <a href="profile.php"
               class="block rounded-lg bg-purple-100 text-purple-800 px-4 py-3 font-semibold">
                My Profile
            </a>

            <a href="../auth/logout.php"
               class="block rounded-lg px-4 py-3 text-red-600 hover:bg-red-50">
                Logout
            </a>
        </nav>
    </aside>

    <main class="flex-1 p-6 md:p-10">

        <div class="mb-8">
            <p class="text-sm text-gray-500">Salonist Portal</p>
            <h2 class="text-3xl font-bold">My Profile</h2>
            <p class="text-gray-500 mt-2">
                Manage your personal account information.
            </p>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $messageType === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 max-w-3xl">

            <div class="p-6 border-b border-gray-100">
                <h3 class="text-xl font-bold">Personal Information</h3>
                <p class="text-sm text-gray-500 mt-1">
                    Keep your contact information up to date.
                </p>
            </div>

            <form method="POST" class="p-6 space-y-5">

                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                <div>
                    <label for="full_name" class="block text-sm font-semibold mb-2">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="<?= e($salonist['full_name']) ?>"
                        required
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-500"
                    >
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold mb-2">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= e($salonist['email']) ?>"
                        required
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-500"
                    >
                </div>

                <div>
                    <label for="phone" class="block text-sm font-semibold mb-2">
                        Phone Number
                    </label>

                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        value="<?= e($salonist['phone']) ?>"
                        required
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-500"
                    >
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">
                        Account Created
                    </label>

                    <input
                        type="text"
                        value="<?= e(date('d F Y', strtotime($salonist['created_at']))) ?>"
                        readonly
                        class="w-full border border-gray-200 bg-gray-50 rounded-lg px-4 py-3 text-gray-500"
                    >
                </div>

                <button
                    type="submit"
                    class="bg-purple-800 hover:bg-purple-900 text-white px-6 py-3 rounded-lg font-semibold"
                >
                    Save Changes
                </button>

            </form>
        </div>

        <p class="text-center text-sm text-gray-400 mt-10">
            &copy; <?= date('Y') ?> Grace Beauty Salon. All rights reserved.
        </p>

    </main>
</div>

</body>
</html>