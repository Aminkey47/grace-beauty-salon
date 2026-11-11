
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid form submission. Refresh and try again.');
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['service_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
            $duration = filter_var($_POST['duration_minutes'] ?? '', FILTER_VALIDATE_INT);
            $image = trim($_POST['image_url'] ?? '');

            if ($name === '' || strlen($name) > 150) {
                throw new Exception('Enter a valid service name.');
            }

            if ($price === false || $price < 0 || $duration === false || $duration < 1) {
                throw new Exception('Enter a valid price and duration.');
            }

            if ($image !== '' && !filter_var($image, FILTER_VALIDATE_URL)) {
                throw new Exception('Enter a valid image URL.');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    "UPDATE services
                     SET service_name=?, description=?, price=?,
                         duration_minutes=?, image_url=?
                     WHERE id=?"
                );
                $stmt->execute([$name, $description, $price, $duration, $image ?: null, $id]);
                $_SESSION['admin_flash'] = 'Service updated successfully.';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO services
                     (service_name, description, price, duration_minutes, image_url, is_active)
                     VALUES (?, ?, ?, ?, ?, 1)"
                );
                $stmt->execute([$name, $description, $price, $duration, $image ?: null]);
                $_SESSION['admin_flash'] = 'Service added successfully.';
            }

            header('Location: services.php');
            exit;
        }

        if ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare(
                "UPDATE services
                 SET is_active=IF(is_active=1, 0, 1)
                 WHERE id=?"
            );
            $stmt->execute([$id]);

            $_SESSION['admin_flash'] = 'Service status updated.';
            header('Location: services.php');
            exit;
        }

    } catch (Throwable $ex) {
        $error = $ex instanceof PDOException
            ? 'Unable to save this service. Please check the details.'
            : $ex->getMessage();
        error_log($ex->getMessage());
    }
}

$services = $pdo->query(
    "SELECT * FROM services ORDER BY created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$editService = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editService = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Services | Grace Beauty Salon</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-[#FAF8FC] text-gray-800 font-['DM_Sans']">
<main class="max-w-7xl mx-auto p-5 md:p-8">

<div class="flex justify-between items-center flex-wrap gap-4 mb-8">
    <div>
        <p class="text-xs uppercase tracking-widest text-pink-800 font-bold">Administration</p>
        <h1 class="text-3xl font-['Playfair_Display'] font-bold text-purple-900">Service Management</h1>
        <p class="text-gray-500 mt-1">Manage services, prices and durations.</p>
    </div>
    <a href="index.php" class="text-purple-800 font-semibold">← Dashboard</a>
</div>

<?php if ($message): ?><div class="bg-green-50 text-green-800 p-4 rounded-xl mb-5"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-50 text-red-700 p-4 rounded-xl mb-5"><?= e($error) ?></div><?php endif; ?>

<section class="bg-white border border-purple-100 rounded-2xl p-6 mb-8">
<h2 class="text-xl font-bold text-purple-900 mb-5"><?= $editService ? 'Edit Service' : 'Add a Service' ?></h2>

<form method="POST" class="grid md:grid-cols-2 gap-4">
    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int)($editService['id'] ?? 0) ?>">

    <input name="service_name" required maxlength="150" placeholder="Service name"
        value="<?= e($editService['service_name'] ?? '') ?>" class="border rounded-xl p-3">

    <input name="price" type="number" min="0" step="0.01" required placeholder="Price (KSh)"
        value="<?= e($editService['price'] ?? '') ?>" class="border rounded-xl p-3">

    <input name="duration_minutes" type="number" min="1" required placeholder="Duration in minutes"
        value="<?= e($editService['duration_minutes'] ?? '') ?>" class="border rounded-xl p-3">

    <input name="image_url" type="url" placeholder="Image URL (optional)"
        value="<?= e($editService['image_url'] ?? '') ?>" class="border rounded-xl p-3">

    <textarea name="description" rows="3" placeholder="Service description"
        class="border rounded-xl p-3 md:col-span-2"><?= e($editService['description'] ?? '') ?></textarea>

    <button class="bg-purple-900 text-white rounded-xl p-3 font-semibold">
        <?= $editService ? 'Update Service' : 'Add Service' ?>
    </button>

    <?php if ($editService): ?>
        <a href="services.php" class="text-center border rounded-xl p-3">Cancel editing</a>
    <?php endif; ?>
</form>
</section>

<section class="bg-white border border-purple-100 rounded-2xl p-6">
<h2 class="text-xl font-bold text-purple-900 mb-5">All Services</h2>
<div class="overflow-x-auto">
<table class="w-full text-sm text-left">
<thead class="bg-purple-50">
<tr><th class="p-3">Service</th><th class="p-3">Price</th><th class="p-3">Duration</th><th class="p-3">Status</th><th class="p-3">Actions</th></tr>
</thead>
<tbody>
<?php foreach ($services as $service): ?>
<tr class="border-t">
    <td class="p-3">
        <p class="font-semibold"><?= e($service['service_name']) ?></p>
        <p class="text-xs text-gray-500"><?= e($service['description']) ?></p>
    </td>
    <td class="p-3">KSh <?= number_format((float)$service['price'], 2) ?></td>
    <td class="p-3"><?= (int)$service['duration_minutes'] ?> minutes</td>
    <td class="p-3"><?= $service['is_active'] ? 'Active' : 'Inactive' ?></td>
    <td class="p-3 whitespace-nowrap">
        <a class="text-purple-800 font-semibold" href="services.php?edit=<?= (int)$service['id'] ?>">Edit</a>
        <form method="POST" class="inline ml-2" onsubmit="return confirm('Change this service status?')">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="id" value="<?= (int)$service['id'] ?>">
            <button class="text-red-700 font-semibold"><?= $service['is_active'] ? 'Deactivate' : 'Reactivate' ?></button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$services): ?><tr><td colspan="5" class="p-6 text-center text-gray-500">No services have been added yet.</td></tr><?php endif; ?>
</tbody>
</table>
</div>
</section>
</main>
</body>
</html>
