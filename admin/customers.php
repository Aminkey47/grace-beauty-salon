
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
        $id = (int)($_POST['id'] ?? 0);

        if ($action === 'update') {
            $name = trim($_POST['full_name'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $phone = trim($_POST['phone'] ?? '');

            if ($id < 1 || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Enter a valid customer name and email address.');
            }

            $stmt = $pdo->prepare(
                "UPDATE users
                 SET full_name=?, email=?, phone=?
                 WHERE id=? AND role='customer'"
            );
            $stmt->execute([$name, $email, $phone, $id]);

            $check = $pdo->prepare(
                "SELECT id FROM users WHERE id=? AND role='customer'"
            );
            $check->execute([$id]);

            if (!$check->fetch()) {
                throw new Exception('Customer account not found.');
            }

            $_SESSION['admin_flash'] = 'Customer details updated successfully.';
            header('Location: customers.php');
            exit;
        }

        if ($action === 'toggle') {
            $stmt = $pdo->prepare(
                "UPDATE users
                 SET is_active=IF(is_active=1, 0, 1)
                 WHERE id=? AND role='customer'"
            );
            $stmt->execute([$id]);

            $_SESSION['admin_flash'] = 'Customer account status updated.';
            header('Location: customers.php');
            exit;
        }

    } catch (Throwable $ex) {
        $error = $ex instanceof PDOException
            ? 'Unable to update customer. The email may already be registered.'
            : $ex->getMessage();
        error_log($ex->getMessage());
    }
}

$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $stmt = $pdo->prepare(
        "SELECT id, full_name, email, phone, is_active, created_at
         FROM users
         WHERE role='customer'
         AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)
         ORDER BY created_at DESC"
    );
    $term = '%' . $search . '%';
    $stmt->execute([$term, $term, $term]);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $customers = $pdo->query(
        "SELECT id, full_name, email, phone, is_active, created_at
         FROM users
         WHERE role='customer'
         ORDER BY created_at DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Customers | Grace Beauty Salon</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-[#FAF8FC] text-gray-800 font-['DM_Sans']">
<main class="max-w-7xl mx-auto p-5 md:p-8">

<div class="flex flex-wrap justify-between items-center gap-4 mb-8">
    <div>
        <p class="text-xs uppercase tracking-widest text-pink-800 font-bold">Administration</p>
        <h1 class="text-3xl font-['Playfair_Display'] font-bold text-purple-900">Customer Management</h1>
        <p class="text-gray-500 mt-1">View and manage registered customers.</p>
    </div>
    <a href="index.php" class="text-purple-800 font-semibold">← Dashboard</a>
</div>

<?php if ($message): ?><div class="bg-green-50 text-green-800 p-4 rounded-xl mb-5"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-50 text-red-700 p-4 rounded-xl mb-5"><?= e($error) ?></div><?php endif; ?>

<form method="GET" class="bg-white rounded-2xl border border-purple-100 p-5 mb-6 flex flex-col sm:flex-row gap-3">
    <input name="search" value="<?= e($search) ?>"
        placeholder="Search by name, email or phone"
        class="border rounded-xl p-3 flex-1">
    <button class="bg-purple-900 text-white rounded-xl px-6 py-3 font-semibold">Search</button>
    <a href="customers.php" class="border rounded-xl px-6 py-3 text-center">Clear</a>
</form>

<section class="bg-white border border-purple-100 rounded-2xl p-6">
<div class="flex justify-between items-center mb-5">
    <h2 class="text-xl font-bold text-purple-900">Registered Customers</h2>
    <p class="text-sm text-gray-500"><?= count($customers) ?> result(s)</p>
</div>

<div class="overflow-x-auto">
<table class="w-full text-sm text-left">
<thead class="bg-purple-50">
<tr>
    <th class="p-3">Customer</th><th class="p-3">Email</th>
    <th class="p-3">Phone</th><th class="p-3">Registered</th>
    <th class="p-3">Status</th><th class="p-3">Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($customers as $customer): ?>
<tr class="border-t align-top">
    <td class="p-3 font-semibold"><?= e($customer['full_name']) ?></td>
    <td class="p-3"><?= e($customer['email']) ?></td>
    <td class="p-3"><?= e($customer['phone']) ?></td>
    <td class="p-3"><?= e(date('d M Y', strtotime($customer['created_at']))) ?></td>
    <td class="p-3"><?= $customer['is_active'] ? 'Active' : 'Inactive' ?></td>
    <td class="p-3">
        <details>
            <summary class="cursor-pointer text-purple-800 font-semibold">Edit</summary>
            <form method="POST" class="mt-3 space-y-2 min-w-56">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= (int)$customer['id'] ?>">

                <input name="full_name" required value="<?= e($customer['full_name']) ?>"
                    class="border rounded-lg p-2 w-full">
                <input name="email" type="email" required value="<?= e($customer['email']) ?>"
                    class="border rounded-lg p-2 w-full">
                <input name="phone" value="<?= e($customer['phone']) ?>"
                    class="border rounded-lg p-2 w-full">

                <button class="bg-purple-900 text-white rounded-lg px-4 py-2">Save changes</button>
            </form>
        </details>

        <form method="POST" class="mt-2" onsubmit="return confirm('Change this customer account status?')">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="id" value="<?= (int)$customer['id'] ?>">
            <button class="text-red-700 font-semibold">
                <?= $customer['is_active'] ? 'Deactivate' : 'Reactivate' ?>
            </button>
        </form>
    </td>
</tr>
<?php endforeach; ?>

<?php if (!$customers): ?>
<tr><td colspan="6" class="p-6 text-center text-gray-500">No customers found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</section>
</main>
</body>
</html>
