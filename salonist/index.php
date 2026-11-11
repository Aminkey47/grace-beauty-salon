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
$salonistName = $_SESSION['full_name'] ?? 'Salonist';

try {
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed
        FROM appointments
        WHERE salonist_id = ?
    ");
    $stmt->execute([$salonistId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT
            a.id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            a.additional_notes,
            u.full_name AS customer_name,
            u.phone AS customer_phone,
            s.service_name,
            s.duration_minutes
        FROM appointments a
        INNER JOIN users u ON a.customer_id = u.id
        INNER JOIN services s ON a.service_id = s.id
        WHERE a.salonist_id = ?
        ORDER BY
            CASE WHEN a.status = 'pending' THEN 0 ELSE 1 END,
            a.appointment_date ASC,
            a.appointment_time ASC
        LIMIT 8
    ");
    $stmt->execute([$salonistId]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Salonist dashboard error: ' . $e->getMessage());
    $stats = ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'completed' => 0];
    $appointments = [];
    $pageError = 'Unable to load dashboard information.';
}

function statusClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-amber-100 text-amber-700';
        case 'confirmed':
            return 'bg-green-100 text-green-700';
        case 'completed':
            return 'bg-blue-100 text-blue-700';
        case 'cancelled':
            return 'bg-red-100 text-red-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salonist Dashboard | Grace Beauty Salon</title>
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
               class="block rounded-lg bg-purple-100 text-purple-800 px-4 py-3 font-semibold">
                Dashboard
            </a>

            <a href="appointments.php"
               class="block rounded-lg px-4 py-3 text-gray-600 hover:bg-purple-50">
                My Appointments
            </a>

            <a href="profile.php"
               class="block rounded-lg px-4 py-3 text-gray-600 hover:bg-purple-50">
                My Profile
            </a>

            <a href="../auth/logout.php"
               class="block rounded-lg px-4 py-3 text-red-600 hover:bg-red-50">
                Logout
            </a>
        </nav>
    </aside>

    <main class="flex-1 p-6 md:p-10">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <p class="text-sm text-gray-500">Salonist Dashboard</p>
                <h2 class="text-3xl font-bold text-gray-900">
                    Welcome, <?= e($salonistName) ?>
                </h2>
                <p class="text-gray-500 mt-2">
                    Here's an overview of your appointments.
                </p>
            </div>

            <a href="appointments.php"
               class="inline-block bg-purple-800 hover:bg-purple-900 text-white px-5 py-3 rounded-lg font-semibold text-center">
                View Appointments
            </a>
        </div>

        <?php if (!empty($pageError)): ?>
            <div class="mb-6 bg-red-100 text-red-700 p-4 rounded-lg">
                <?= e($pageError) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">

            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <p class="text-gray-500 text-sm">Total Appointments</p>
                <h3 class="text-3xl font-bold mt-3">
                    <?= (int)($stats['total'] ?? 0) ?>
                </h3>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <p class="text-gray-500 text-sm">Pending Requests</p>
                <h3 class="text-3xl font-bold text-amber-600 mt-3">
                    <?= (int)($stats['pending'] ?? 0) ?>
                </h3>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <p class="text-gray-500 text-sm">Confirmed</p>
                <h3 class="text-3xl font-bold text-green-600 mt-3">
                    <?= (int)($stats['confirmed'] ?? 0) ?>
                </h3>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <p class="text-gray-500 text-sm">Completed</p>
                <h3 class="text-3xl font-bold text-blue-600 mt-3">
                    <?= (int)($stats['completed'] ?? 0) ?>
                </h3>
            </div>

        </div>

        <section class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">

            <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                <div>
                    <h3 class="text-xl font-bold">Recent Appointments</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Your latest assigned bookings.
                    </p>
                </div>

                <a href="appointments.php"
                   class="text-purple-800 font-semibold hover:underline">
                    View all
                </a>
            </div>

            <?php if (empty($appointments)): ?>

                <div class="p-10 text-center">
                    <h4 class="font-semibold text-gray-700">No appointments yet</h4>
                    <p class="text-gray-500 text-sm mt-2">
                        Appointments assigned to you will appear here.
                    </p>
                </div>

            <?php else: ?>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-gray-500 text-sm">
                            <tr>
                                <th class="px-6 py-4">Customer</th>
                                <th class="px-6 py-4">Service</th>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Time</th>
                                <th class="px-6 py-4">Status</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">

                            <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td class="px-6 py-4 font-medium">
                                        <?= e($appointment['customer_name']) ?>
                                    </td>

                                    <td class="px-6 py-4">
                                        <?= e($appointment['service_name']) ?>
                                    </td>

                                    <td class="px-6 py-4">
                                        <?= e(date('d M Y', strtotime($appointment['appointment_date']))) ?>
                                    </td>

                                    <td class="px-6 py-4">
                                        <?= e(date('h:i A', strtotime($appointment['appointment_time']))) ?>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?= statusClass($appointment['status']) ?>">
                                            <?= e(ucfirst($appointment['status'])) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </section>

        <p class="text-center text-sm text-gray-400 mt-10">
            &copy; <?= date('Y') ?> Grace Beauty Salon. All rights reserved.
        </p>

    </main>
</div>

</body>
</html>