
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$message = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);
$error = '';

$validStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid form submission. Refresh and try again.');
        }

        $appointmentId = (int)($_POST['appointment_id'] ?? 0);
        $salonistId = (int)($_POST['salonist_id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if ($appointmentId < 1 || !in_array($status, $validStatuses, true)) {
            throw new Exception('Invalid appointment information.');
        }

        // Confirm that the appointment exists and retrieve its service.
        $stmt = $pdo->prepare(
            "SELECT id, service_id
             FROM appointments
             WHERE id=?"
        );
        $stmt->execute([$appointmentId]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appointment) {
            throw new Exception('Appointment not found.');
        }

        // Validate the salonist against the service assignment table.
        if ($salonistId > 0) {
            $stmt = $pdo->prepare(
                "SELECT u.id
                 FROM users u
                 INNER JOIN salonist_services ss
                    ON ss.salonist_id=u.id
                 WHERE u.id=?
                 AND u.role='salonist'
                 AND u.is_active=1
                 AND ss.service_id=?"
            );
            $stmt->execute([$salonistId, $appointment['service_id']]);

            if (!$stmt->fetch()) {
                throw new Exception(
                    'This salonist is inactive or is not assigned to this service.'
                );
            }
        } else {
            $salonistId = null;
        }

        $stmt = $pdo->prepare(
            "UPDATE appointments
             SET salonist_id=?, status=?
             WHERE id=?"
        );
        $stmt->execute([$salonistId, $status, $appointmentId]);

        $_SESSION['admin_flash'] = 'Appointment updated successfully.';
        header('Location: appointments.php');
        exit;

    } catch (Throwable $ex) {
        $error = $ex instanceof PDOException
            ? 'Unable to update the appointment. Please check the database.'
            : $ex->getMessage();
        error_log($ex->getMessage());
    }
}

$appointments = $pdo->query(
    "SELECT
        a.id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.style_preferences,
        a.additional_notes,
        a.salonist_id,
        a.service_id,
        c.full_name AS customer_name,
        c.email AS customer_email,
        c.phone AS customer_phone,
        s.service_name,
        s.price,
        salonist.full_name AS salonist_name
     FROM appointments a
     INNER JOIN users c ON a.customer_id=c.id
     INNER JOIN services s ON a.service_id=s.id
     LEFT JOIN users salonist ON a.salonist_id=salonist.id
     ORDER BY a.appointment_date DESC, a.appointment_time DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$salonists = $pdo->query(
    "SELECT id, full_name
     FROM users
     WHERE role='salonist' AND is_active=1
     ORDER BY full_name"
)->fetchAll(PDO::FETCH_ASSOC);

// Build a map of which salonists provide which services.
$salonistServices = [];
$stmt = $pdo->query(
    "SELECT salonist_id, service_id FROM salonist_services"
);

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $salonistServices[(int)$row['service_id']][] = (int)$row['salonist_id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Appointments | Grace Beauty Salon</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-[#FAF8FC] text-gray-800 font-['DM_Sans']">
<main class="max-w-7xl mx-auto p-5 md:p-8">

<div class="flex flex-wrap justify-between items-center gap-4 mb-8">
    <div>
        <p class="text-xs uppercase tracking-widest text-pink-800 font-bold">Administration</p>
        <h1 class="text-3xl font-['Playfair_Display'] font-bold text-purple-900">Appointment Management</h1>
        <p class="text-gray-500 mt-1">Review bookings, assign salonists and update statuses.</p>
    </div>
    <a href="index.php" class="text-purple-800 font-semibold">← Dashboard</a>
</div>

<?php if ($message): ?><div class="bg-green-50 text-green-800 p-4 rounded-xl mb-5"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-50 text-red-700 p-4 rounded-xl mb-5"><?= e($error) ?></div><?php endif; ?>

<section class="bg-white border border-purple-100 rounded-2xl p-6">
<h2 class="text-xl font-bold text-purple-900 mb-5">
    All Appointments (<?= count($appointments) ?>)
</h2>

<div class="space-y-5">
<?php foreach ($appointments as $appointment): ?>
<article class="border border-purple-100 rounded-xl p-5">

    <div class="flex flex-wrap justify-between gap-3 mb-4">
        <div>
            <p class="font-bold text-lg"><?= e($appointment['customer_name']) ?></p>
            <p class="text-sm text-gray-500">
                <?= e($appointment['customer_email']) ?> ·
                <?= e($appointment['customer_phone']) ?>
            </p>
        </div>
        <span class="text-sm font-semibold px-3 py-1 rounded-full bg-purple-50 text-purple-900 h-fit">
            <?= e(ucfirst($appointment['status'])) ?>
        </span>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm mb-4">
        <div>
            <p class="text-gray-500">Service</p>
            <p class="font-semibold"><?= e($appointment['service_name']) ?></p>
        </div>
        <div>
            <p class="text-gray-500">Date</p>
            <p class="font-semibold"><?= e(date('d M Y', strtotime($appointment['appointment_date']))) ?></p>
        </div>
        <div>
            <p class="text-gray-500">Time</p>
            <p class="font-semibold"><?= e(date('h:i A', strtotime($appointment['appointment_time']))) ?></p>
        </div>
        <div>
            <p class="text-gray-500">Price</p>
            <p class="font-semibold">KSh <?= number_format((float)$appointment['price'], 2) ?></p>
        </div>
    </div>

    <?php if (!empty($appointment['style_preferences'])): ?>
        <p class="text-sm mb-2">
            <strong>Style preferences:</strong>
            <?= e($appointment['style_preferences']) ?>
        </p>
    <?php endif; ?>

    <?php if (!empty($appointment['additional_notes'])): ?>
        <p class="text-sm mb-4">
            <strong>Additional notes:</strong>
            <?= e($appointment['additional_notes']) ?>
        </p>
    <?php endif; ?>

    <p class="text-sm text-gray-600 mb-3">
        Currently assigned to:
        <strong><?= e($appointment['salonist_name'] ?? 'Not assigned') ?></strong>
    </p>

    <form method="POST" class="grid sm:grid-cols-3 gap-3">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="appointment_id" value="<?= (int)$appointment['id'] ?>">

        <select name="salonist_id" class="border rounded-xl p-3">
            <option value="0">Not assigned</option>

            <?php foreach ($salonists as $salonist): ?>
                <?php if (in_array(
                    (int)$salonist['id'],
                    $salonistServices[(int)$appointment['service_id']] ?? [],
                    true
                )): ?>
                    <option value="<?= (int)$salonist['id'] ?>"
                        <?= (int)$appointment['salonist_id'] === (int)$salonist['id'] ? 'selected' : '' ?>>
                        <?= e($salonist['full_name']) ?>
                    </option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>

        <select name="status" required class="border rounded-xl p-3">
            <?php foreach ($validStatuses as $status): ?>
                <option value="<?= e($status) ?>"
                    <?= $appointment['status'] === $status ? 'selected' : '' ?>>
                    <?= e(ucfirst($status)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button class="bg-purple-900 text-white rounded-xl p-3 font-semibold hover:bg-purple-800">
            Save Appointment
        </button>
    </form>

</article>
<?php endforeach; ?>

<?php if (!$appointments): ?>
<p class="text-center text-gray-500 p-8">There are no appointments yet.</p>
<?php endif; ?>
</div>
</section>
</main>
</body>
</html>
