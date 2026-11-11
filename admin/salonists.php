
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

$error = '';
$message = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid form submission. Refresh and try again.');
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['full_name'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            $serviceIds = array_unique(array_filter(
                array_map('intval', $_POST['service_ids'] ?? [])
            ));

            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Enter a valid name and email address.');
            }

            if ($id === 0 && strlen($password) < 8) {
                throw new Exception('A password of at least 8 characters is required.');
            }

            if ($password !== '' && strlen($password) < 8) {
                throw new Exception('The password must contain at least 8 characters.');
            }

            $pdo->beginTransaction();

            if ($id === 0) {
                $stmt = $pdo->prepare(
                    "INSERT INTO users
                    (full_name, email, phone, password, role, is_verified, is_active)
                    VALUES (?, ?, ?, ?, 'salonist', 1, 1)"
                );
                $stmt->execute([
                    $name, $email, $phone,
                    password_hash($password, PASSWORD_DEFAULT)
                ]);
                $id = (int)$pdo->lastInsertId();
            } else {
                if ($password !== '') {
                    $stmt = $pdo->prepare(
                        "UPDATE users
                         SET full_name=?, email=?, phone=?, password=?
                         WHERE id=? AND role='salonist'"
                    );
                    $stmt->execute([
                        $name, $email, $phone,
                        password_hash($password, PASSWORD_DEFAULT), $id
                    ]);
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE users
                         SET full_name=?, email=?, phone=?
                         WHERE id=? AND role='salonist'"
                    );
                    $stmt->execute([$name, $email, $phone, $id]);
                }

                if ($stmt->rowCount() === 0) {
                    $check = $pdo->prepare(
                        "SELECT id FROM users WHERE id=? AND role='salonist'"
                    );
                    $check->execute([$id]);
                    if (!$check->fetch()) {
                        throw new Exception('Salonist account not found.');
                    }
                }
            }

            // Replace the salonist's service assignments.
            $stmt = $pdo->prepare(
                "DELETE FROM salonist_services WHERE salonist_id=?"
            );
            $stmt->execute([$id]);

            $checkService = $pdo->prepare(
                "SELECT id FROM services WHERE id=? AND is_active=1"
            );
            $insertService = $pdo->prepare(
                "INSERT INTO salonist_services (salonist_id, service_id) VALUES (?, ?)"
            );

            foreach ($serviceIds as $serviceId) {
                $checkService->execute([$serviceId]);
                if ($checkService->fetch()) {
                    $insertService->execute([$id, $serviceId]);
                }
            }

            $pdo->commit();

            $_SESSION['admin_flash'] =
                'Salonist account and service assignments saved successfully.';
            header('Location: salonists.php');
            exit;
        }

        if ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare(
                "UPDATE users
                 SET is_active = IF(is_active=1, 0, 1)
                 WHERE id=? AND role='salonist'"
            );
            $stmt->execute([$id]);

            $_SESSION['admin_flash'] = 'Salonist account status updated.';
            header('Location: salonists.php');
            exit;
        }

    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $ex instanceof PDOException
            ? 'Unable to save. The email may already be registered.'
            : $ex->getMessage();

        error_log($ex->getMessage());
    }
}

$services = $pdo->query(
    "SELECT id, service_name FROM services
     WHERE is_active=1 ORDER BY service_name"
)->fetchAll(PDO::FETCH_ASSOC);

$salonists = $pdo->query(
    "SELECT id, full_name, email, phone, is_active, created_at
     FROM users WHERE role='salonist'
     ORDER BY created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$assignments = [];
$stmt = $pdo->query(
    "SELECT salonist_id, service_id FROM salonist_services"
);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $assignments[(int)$row['salonist_id']][] = (int)$row['service_id'];
}

$editSalonist = null;
$editServices = [];

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare(
        "SELECT id, full_name, email, phone
         FROM users WHERE id=? AND role='salonist'"
    );
    $stmt->execute([(int)$_GET['edit']]);
    $editSalonist = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($editSalonist) {
        $editServices = $assignments[(int)$editSalonist['id']] ?? [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Salonists | Grace Beauty Salon</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-[#FAF8FC] text-gray-800 font-['DM_Sans']">
<main class="max-w-7xl mx-auto p-5 md:p-8">

<div class="flex justify-between items-center flex-wrap gap-4 mb-8">
    <div>
        <p class="text-xs uppercase tracking-widest text-pink-800 font-bold">Administration</p>
        <h1 class="text-3xl font-['Playfair_Display'] font-bold text-purple-900">Salonist Management</h1>
        <p class="text-gray-500 mt-1">Manage staff accounts and service assignments.</p>
    </div>
    <a href="index.php" class="text-purple-800 font-semibold">← Dashboard</a>
</div>

<?php if ($message): ?>
<div class="bg-green-50 text-green-800 p-4 rounded-xl mb-5"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="bg-red-50 text-red-700 p-4 rounded-xl mb-5"><?= e($error) ?></div>
<?php endif; ?>

<section class="bg-white border border-purple-100 rounded-2xl p-6 mb-8">
<h2 class="text-xl font-bold text-purple-900 mb-5">
    <?= $editSalonist ? 'Edit Salonist' : 'Create Salonist Account' ?>
</h2>

<form method="POST" class="grid md:grid-cols-2 gap-4">
    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int)($editSalonist['id'] ?? 0) ?>">

    <input name="full_name" required maxlength="150" placeholder="Full name"
        value="<?= e($editSalonist['full_name'] ?? '') ?>" class="border rounded-xl p-3">

    <input name="email" type="email" required placeholder="Email address"
        value="<?= e($editSalonist['email'] ?? '') ?>" class="border rounded-xl p-3">

    <input name="phone" placeholder="Phone number"
        value="<?= e($editSalonist['phone'] ?? '') ?>" class="border rounded-xl p-3">

    <input name="password" type="password"
        <?= $editSalonist ? '' : 'required minlength="8"' ?>
        placeholder="<?= $editSalonist ? 'New password (optional)' : 'Password (minimum 8 characters)' ?>"
        class="border rounded-xl p-3">

    <div class="md:col-span-2">
        <p class="font-semibold mb-3">Assign services</p>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php foreach ($services as $service): ?>
            <label class="flex items-center gap-2 bg-purple-50 p-3 rounded-lg">
                <input type="checkbox" name="service_ids[]"
                    value="<?= (int)$service['id'] ?>"
                    <?= in_array((int)$service['id'], $editServices, true) ? 'checked' : '' ?>>
                <?= e($service['service_name']) ?>
            </label>
        <?php endforeach; ?>
        </div>
        <?php if (!$services): ?>
            <p class="text-sm text-gray-500">Add active services before assigning them to salonists.</p>
        <?php endif; ?>
    </div>

    <button class="md:col-span-2 bg-purple-900 text-white rounded-xl p-3 font-semibold">
        <?= $editSalonist ? 'Update Salonist' : 'Create Salonist' ?>
    </button>

    <?php if ($editSalonist): ?>
        <a href="salonists.php" class="md:col-span-2 text-center border rounded-xl p-3">Cancel editing</a>
    <?php endif; ?>
</form>
</section>

<section class="bg-white border border-purple-100 rounded-2xl p-6">
<h2 class="text-xl font-bold text-purple-900 mb-5">Registered Salonists</h2>
<div class="overflow-x-auto">
<table class="w-full text-sm text-left">
<thead class="bg-purple-50">
<tr>
    <th class="p-3">Name</th><th class="p-3">Email</th>
    <th class="p-3">Phone</th><th class="p-3">Services</th>
    <th class="p-3">Status</th><th class="p-3">Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($salonists as $person): ?>
<tr class="border-t align-top">
    <td class="p-3 font-semibold"><?= e($person['full_name']) ?></td>
    <td class="p-3"><?= e($person['email']) ?></td>
    <td class="p-3"><?= e($person['phone']) ?></td>
    <td class="p-3">
        <?php
        $names = [];
        foreach ($services as $service) {
            if (in_array((int)$service['id'], $assignments[(int)$person['id']] ?? [], true)) {
                $names[] = $service['service_name'];
            }
        }
        echo e(implode(', ', $names) ?: 'None assigned');
        ?>
    </td>
    <td class="p-3"><?= $person['is_active'] ? 'Active' : 'Inactive' ?></td>
    <td class="p-3 whitespace-nowrap">
        <a class="text-purple-800 font-semibold" href="salonists.php?edit=<?= (int)$person['id'] ?>">Edit</a>
        <form method="POST" class="inline ml-2" onsubmit="return confirm('Change this account status?')">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="id" value="<?= (int)$person['id'] ?>">
            <button class="text-red-700 font-semibold"><?= $person['is_active'] ? 'Deactivate' : 'Reactivate' ?></button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$salonists): ?>
<tr><td colspan="6" class="p-6 text-center text-gray-500">No salonists registered yet.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</section>
</main>
</body>
</html>
