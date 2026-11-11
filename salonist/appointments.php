<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';

// Check salonist authentication.
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'salonist') {
    header('Location: ../auth/login.php');
    exit;
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$salonistId = (int) $_SESSION['user_id'];
$salonistName = $_SESSION['full_name'] ?? 'Your Salonist';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$messageType = 'success';

// Handle appointment actions.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';
    $appointmentId = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {

        $message = 'Your session token is invalid. Please refresh the page.';
        $messageType = 'error';

    } elseif (!$appointmentId || !in_array($action, ['accept', 'decline'], true)) {

        $message = 'Invalid appointment action.';
        $messageType = 'error';

    } else {

        $newStatus = $action === 'accept' ? 'confirmed' : 'cancelled';

        try {

            // Retrieve appointment and customer details.
            $detailsStmt = $pdo->prepare(
                "SELECT
                    a.id,
                    a.status,
                    a.appointment_date,
                    a.appointment_time,
                    u.full_name AS customer_name,
                    u.email AS customer_email,
                    s.service_name
                 FROM appointments a
                 INNER JOIN users u ON u.id = a.customer_id
                 INNER JOIN services s ON s.id = a.service_id
                 WHERE a.id = ?
                   AND a.salonist_id = ?
                 LIMIT 1"
            );

            $detailsStmt->execute([$appointmentId, $salonistId]);

            $appointment = $detailsStmt->fetch(PDO::FETCH_ASSOC);

            if (!$appointment || $appointment['status'] !== 'pending') {

                $message = 'This appointment could not be updated. It may no longer be pending or may not belong to you.';
                $messageType = 'error';

            } else {

                // Update appointment status.
                $updateStmt = $pdo->prepare(
                    "UPDATE appointments
                     SET status = ?
                     WHERE id = ?
                       AND salonist_id = ?
                       AND status = 'pending'"
                );

                $updateStmt->execute([
                    $newStatus,
                    $appointmentId,
                    $salonistId
                ]);

                if ($updateStmt->rowCount() === 1) {

                    // Notify customer about the decision.
                    $emailSent = false;

                    try {

                        $emailSent = sendCustomerAppointmentDecision(
                            $appointment['customer_email'],
                            $appointment['customer_name'],
                            $appointment['service_name'],
                            $appointment['appointment_date'],
                            $appointment['appointment_time'],
                            $newStatus,
                            $salonistName
                        );

                    } catch (Throwable $emailError) {

                        error_log(
                            'Customer appointment decision email error: ' .
                            $emailError->getMessage()
                        );

                    }

                    if ($newStatus === 'confirmed') {

                        $message = 'Appointment accepted successfully.';

                    } else {

                        $message = 'Appointment declined successfully.';

                    }

                    if (!$emailSent) {

                        $message .= ' However, the customer notification email could not be sent.';

                        $messageType = 'error';

                    }

                } else {

                    $message = 'This appointment could not be updated. It may have already been processed.';
                    $messageType = 'error';

                }

            }

        } catch (PDOException $e) {

            error_log('Salonist appointment update error: ' . $e->getMessage());

            $message = 'Something went wrong while updating the appointment.';
            $messageType = 'error';

        }

    }

}

// Retrieve appointments assigned to this salonist.
try {

    $stmt = $pdo->prepare(
        "SELECT
            a.id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            a.style_preferences,
            a.additional_notes,
            u.full_name AS customer_name,
            u.email AS customer_email,
            u.phone AS customer_phone,
            s.service_name,
            s.price,
            s.duration_minutes
         FROM appointments a
         INNER JOIN users u ON a.customer_id = u.id
         INNER JOIN services s ON a.service_id = s.id
         WHERE a.salonist_id = ?
         ORDER BY
            CASE
                WHEN a.status = 'pending' THEN 0
                WHEN a.status = 'confirmed' THEN 1
                ELSE 2
            END,
            a.appointment_date ASC,
            a.appointment_time ASC"
    );

    $stmt->execute([$salonistId]);

    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log('Salonist appointments loading error: ' . $e->getMessage());

    $appointments = [];

    $message = 'Unable to load your appointments.';
    $messageType = 'error';

}

// Appointment status styling.
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

    <title>My Appointments | Grace Beauty Salon</title>

    <script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-gray-50 text-gray-800">

<div class="min-h-screen md:flex">

    <aside class="w-full md:w-64 bg-white border-r border-gray-200 md:min-h-screen">

        <div class="p-6">

            <h1 class="text-2xl font-bold text-purple-800">
                Grace Beauty
            </h1>

            <p class="text-sm text-gray-500 mt-1">
                Salonist Portal
            </p>

        </div>

        <nav class="px-4 space-y-2">

            <a href="index.php"
               class="block rounded-lg px-4 py-3 text-gray-600 hover:bg-purple-50">
                Dashboard
            </a>

            <a href="appointments.php"
               class="block rounded-lg bg-purple-100 text-purple-800 px-4 py-3 font-semibold">
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

        <div class="mb-8">

            <p class="text-sm text-gray-500">
                Salonist Portal
            </p>

            <h2 class="text-3xl font-bold">
                My Appointments
            </h2>

            <p class="text-gray-500 mt-2">
                Review and manage appointment requests assigned to you.
            </p>

        </div>

        <?php if ($message): ?>

            <div class="mb-6 p-4 rounded-lg <?= $messageType === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
                <?= e($message) ?>
            </div>

        <?php endif; ?>

        <?php if (empty($appointments)): ?>

            <div class="bg-white rounded-xl p-10 text-center shadow-sm">

                <h3 class="text-xl font-semibold">
                    No appointments assigned
                </h3>

                <p class="text-gray-500 mt-2">
                    New appointment requests assigned to you will appear here.
                </p>

            </div>

        <?php else: ?>

            <div class="space-y-5">

                <?php foreach ($appointments as $appointment): ?>

                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">

                        <div class="flex flex-col lg:flex-row lg:justify-between gap-4">

                            <div>

                                <div class="flex flex-wrap items-center gap-3">

                                    <h3 class="text-xl font-bold">
                                        <?= e($appointment['service_name']) ?>
                                    </h3>

                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?= statusClass($appointment['status']) ?>">
                                        <?= e(ucfirst($appointment['status'])) ?>
                                    </span>

                                </div>

                                <p class="text-gray-500 mt-2">
                                    Appointment #<?= (int) $appointment['id'] ?>
                                </p>

                            </div>

                            <div class="text-left lg:text-right">

                                <p class="text-purple-800 font-bold text-lg">
                                    KSh <?= number_format((float) $appointment['price'], 2) ?>
                                </p>

                                <p class="text-sm text-gray-500">
                                    <?= (int) $appointment['duration_minutes'] ?> minutes
                                </p>

                            </div>

                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-6">

                            <div>

                                <p class="text-sm text-gray-500">
                                    Customer
                                </p>

                                <p class="font-semibold">
                                    <?= e($appointment['customer_name']) ?>
                                </p>

                            </div>

                            <div>

                                <p class="text-sm text-gray-500">
                                    Phone
                                </p>

                                <p class="font-semibold">
                                    <?= e($appointment['customer_phone']) ?>
                                </p>

                            </div>

                            <div>

                                <p class="text-sm text-gray-500">
                                    Appointment Date
                                </p>

                                <p class="font-semibold">
                                    <?= e(date('d F Y', strtotime($appointment['appointment_date']))) ?>
                                </p>

                            </div>

                            <div>

                                <p class="text-sm text-gray-500">
                                    Appointment Time
                                </p>

                                <p class="font-semibold">
                                    <?= e(date('h:i A', strtotime($appointment['appointment_time']))) ?>
                                </p>

                            </div>

                            <?php if (!empty($appointment['style_preferences'])): ?>

                                <div class="md:col-span-2">

                                    <p class="text-sm text-gray-500">
                                        Style Preferences
                                    </p>

                                    <p class="mt-1">
                                        <?= nl2br(e($appointment['style_preferences'])) ?>
                                    </p>

                                </div>

                            <?php endif; ?>

                            <?php if (!empty($appointment['additional_notes'])): ?>

                                <div class="md:col-span-2">

                                    <p class="text-sm text-gray-500">
                                        Additional Notes
                                    </p>

                                    <p class="mt-1">
                                        <?= nl2br(e($appointment['additional_notes'])) ?>
                                    </p>

                                </div>

                            <?php endif; ?>

                        </div>

                        <?php if ($appointment['status'] === 'pending'): ?>

                            <div class="border-t border-gray-100 mt-6 pt-5 flex flex-wrap gap-3">

                                <form
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to accept this appointment?');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($_SESSION['csrf_token']) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="appointment_id"
                                        value="<?= (int) $appointment['id'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="accept"
                                    >

                                    <button
                                        type="submit"
                                        class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg font-semibold"
                                    >
                                        Accept Appointment
                                    </button>

                                </form>

                                <form
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to decline this appointment?');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($_SESSION['csrf_token']) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="appointment_id"
                                        value="<?= (int) $appointment['id'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="decline"
                                    >

                                    <button
                                        type="submit"
                                        class="bg-red-100 hover:bg-red-200 text-red-700 px-5 py-2.5 rounded-lg font-semibold"
                                    >
                                        Decline Appointment
                                    </button>

                                </form>

                            </div>

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <p class="text-center text-sm text-gray-400 mt-10">
            &copy; <?= date('Y') ?> Grace Beauty Salon. All rights reserved.
        </p>

    </main>

</div>

</body>
</html>