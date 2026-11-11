<?php

session_start();

// Protect the page
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'customer'
) {
    header('Location: ../auth/login.php');
    exit;
}

// Only show this page after a booking submission
if (empty($_SESSION['booking_success'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

$bookingMessage = $_SESSION['booking_success'];
$emailSent = $_SESSION['booking_email_sent'] ?? false;

// Clear temporary messages
unset($_SESSION['booking_success']);
unset($_SESSION['booking_email_sent']);

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Booking Successful | Grace Beauty Salon</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            background: #F8F5FA;
            font-family: Arial, sans-serif;
        }

        .success-icon {
            background: #E8F7ED;
            color: #218344;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-5">

    <div class="bg-white max-w-xl w-full rounded-3xl shadow-xl p-8 md:p-12 text-center">

        <div class="success-icon w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">

            <svg xmlns="http://www.w3.org/2000/svg"
                 width="42"
                 height="42"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2.5"
                 stroke-linecap="round"
                 stroke-linejoin="round">

                <path d="M20 6 9 17l-5-5"></path>

            </svg>

        </div>

        <p class="text-sm uppercase tracking-widest font-bold mb-3"
           style="color: #9B2348;">
            Grace Beauty Salon
        </p>

        <h1 class="text-3xl md:text-4xl font-bold mb-4"
            style="color: #542078;">
            Booking Successful!
        </h1>

        <p class="text-gray-600 leading-relaxed mb-6">
            <?= e($bookingMessage) ?>
        </p>

        <?php if ($emailSent): ?>

            <div class="bg-purple-50 border border-purple-100 rounded-2xl p-6 mb-6">

                <h2 class="font-bold text-lg mb-2"
                    style="color: #542078;">
                    Check Your Email
                </h2>

                <p class="text-gray-600 text-sm leading-relaxed">
                    We have sent your appointment details to your registered
                    email address. Please check your inbox and spam folder.
                </p>

            </div>

        <?php else: ?>

            <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-6 mb-6">

                <h2 class="font-bold text-lg mb-2 text-yellow-800">
                    Email Not Sent
                </h2>

                <p class="text-gray-700 text-sm">
                    Your appointment has been saved, but we could not send
                    the confirmation email. Please contact the salon.
                </p>

            </div>

        <?php endif; ?>

        <p class="text-gray-500 text-sm mb-8">
            Your appointment is currently pending confirmation.
            Our salon team will review your request.
        </p>

        <div class="space-y-3">

            <a href="index.php?page=appointments"
               class="block w-full text-white font-bold py-4 rounded-xl transition"
               style="background: #542078;">
                View My Appointments
            </a>

            <a href="index.php?page=dashboard"
               class="block w-full border border-purple-200 font-semibold py-4 rounded-xl"
               style="color: #542078;">
                Return to Dashboard
            </a>

        </div>

        <p class="text-gray-400 text-xs mt-8">
            Thank you for choosing Grace Beauty Salon.
        </p>

    </div>

</body>
</html>