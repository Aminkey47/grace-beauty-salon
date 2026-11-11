<?php

$profileError = '';
$profileSuccess = '';

$profileStmt = $pdo->prepare(
    "SELECT id, full_name, email, phone, created_at
     FROM users
     WHERE id = ? AND role = 'customer'
     LIMIT 1"
);

$profileStmt->execute([$userId]);

$customer = $profileStmt->fetch();

if (!$customer) {
    echo '<p class="text-red-600">Customer profile could not be found.</p>';
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_profile'])) {

    $token = $_POST['csrf_token'] ?? '';

    $newName = trim($_POST['full_name'] ?? '');
    $newPhone = trim($_POST['phone'] ?? '');

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $profileError = 'Invalid submission. Refresh and try again.';

    } elseif ($newName === '' || strlen($newName) > 150) {
        $profileError = 'Please enter a valid full name.';

    } elseif ($newPhone === '' || strlen($newPhone) > 20) {
        $profileError = 'Please enter a valid phone number.';

    } else {

        try {

            $updateStmt = $pdo->prepare(
                "UPDATE users
                 SET full_name = ?, phone = ?
                 WHERE id = ? AND role = 'customer'"
            );

            $updateStmt->execute([
                $newName,
                $newPhone,
                $userId
            ]);

            $_SESSION['full_name'] = $newName;

            $customer['full_name'] = $newName;
            $customer['phone'] = $newPhone;

            $fullName = $newName;
            $firstName = explode(' ', trim($newName))[0];

            $profileSuccess = 'Your profile has been updated successfully.';

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $profileError = 'We could not update your profile. Please try again.';
        }
    }
}

?>

<section class="max-w-3xl">

    <div class="mb-8">
        <h2 class="font-display text-3xl text-charcoal mb-2">
            My Profile
        </h2>

        <p class="text-gray-500">
            Manage your personal account information.
        </p>
    </div>

    <?php if ($profileError): ?>
        <div class="bg-red-50 text-red-700 border border-red-200 rounded-xl p-4 mb-6">
            <?= e($profileError) ?>
        </div>
    <?php endif; ?>

    <?php if ($profileSuccess): ?>
        <div class="bg-green-50 text-green-700 border border-green-200 rounded-xl p-4 mb-6">
            <?= e($profileSuccess) ?>
        </div>
    <?php endif; ?>

    <form method="POST"
          action="index.php?page=profile"
          class="bg-white rounded-3xl p-6 md:p-8 border border-purple-100 space-y-6">

        <input type="hidden" name="csrf_token"
               value="<?= e($_SESSION['csrf_token']) ?>">

        <div>
            <label class="block font-semibold text-charcoal mb-2">
                Full Name
            </label>

            <input type="text" name="full_name"
                   value="<?= e($customer['full_name']) ?>"
                   maxlength="150" required
                   class="w-full border border-gray-200 rounded-xl p-3">
        </div>

        <div>
            <label class="block font-semibold text-charcoal mb-2">
                Email Address
            </label>

            <input type="email"
                   value="<?= e($customer['email']) ?>"
                   disabled
                   class="w-full border border-gray-200 rounded-xl p-3 bg-gray-100">

            <p class="text-xs text-gray-400 mt-2">
                Your registered email address cannot be changed here.
            </p>
        </div>

        <div>
            <label class="block font-semibold text-charcoal mb-2">
                Phone Number
            </label>

            <input type="tel" name="phone"
                   value="<?= e($customer['phone']) ?>"
                   maxlength="20" required
                   class="w-full border border-gray-200 rounded-xl p-3">
        </div>

        <div>
            <label class="block font-semibold text-charcoal mb-2">
                Account Created
            </label>

            <input type="text"
                   value="<?= e(date('d M Y', strtotime($customer['created_at']))) ?>"
                   disabled
                   class="w-full border border-gray-200 rounded-xl p-3 bg-gray-100">
        </div>

        <button type="submit" name="update_profile"
                class="w-full bg-violet hover:bg-burgundy text-white font-bold py-4 rounded-xl transition">
            Save Changes
        </button>

    </form>

</section>
