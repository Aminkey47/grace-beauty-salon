<?php

session_start();

require_once __DIR__ . '/../config/database.php';

// ------------------------------------------
// 1. ADMIN AUTHENTICATION
// ------------------------------------------

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {

    if (($_SESSION['role'] ?? '') === 'customer') {
        header('Location: ../customer/index.php');
        exit;
    }

    if (($_SESSION['role'] ?? '') === 'salonist') {
        header('Location: ../salonist/index.php');
        exit;
    }

    header('Location: ../auth/login.php');
    exit;
}

$adminName = $_SESSION['full_name'] ?? 'Administrator';
$firstName = explode(' ', trim($adminName))[0];

// ------------------------------------------
// 2. HELPER FUNCTION
// ------------------------------------------

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// ------------------------------------------
// 3. DASHBOARD STATISTICS
// ------------------------------------------

$totalCustomers = 0;
$totalSalonists = 0;
$totalServices = 0;
$totalAppointments = 0;

$pendingAppointments = 0;
$confirmedAppointments = 0;
$completedAppointments = 0;

$recentAppointments = [];
$dashboardError = '';

try {

    // Total customers.
    $stmt = $pdo->query(
        "SELECT COUNT(*)
         FROM users
         WHERE role = 'customer'"
    );

    $totalCustomers = (int) $stmt->fetchColumn();

    // Total active salonists.
    $stmt = $pdo->query(
        "SELECT COUNT(*)
         FROM users
         WHERE role = 'salonist'
         AND is_active = 1"
    );

    $totalSalonists = (int) $stmt->fetchColumn();

    // Total active services.
    $stmt = $pdo->query(
        "SELECT COUNT(*)
         FROM services
         WHERE is_active = 1"
    );

    $totalServices = (int) $stmt->fetchColumn();

    // Total appointments.
    $stmt = $pdo->query(
        "SELECT COUNT(*)
         FROM appointments"
    );

    $totalAppointments = (int) $stmt->fetchColumn();

    // Pending appointments.
    $stmt = $pdo->query(
        "SELECT COUNT(*)
         FROM appointments
         WHERE status = 'pending'"
    );

    $pendingAppointments = (int) $stmt->fetchColumn();

    // Confirmed appointments.
    $stmt = $pdo->query(
        "SELECT COUNT(*)
         FROM appointments
         WHERE status = 'confirmed'"
    );

    $confirmedAppointments = (int) $stmt->fetchColumn();

    // Completed appointments.
    $stmt = $pdo->query(
        "SELECT COUNT(*)
         FROM appointments
         WHERE status = 'completed'"
    );

    $completedAppointments = (int) $stmt->fetchColumn();

    // Recent appointments.
    $stmt = $pdo->query(
        "SELECT
            a.id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            c.full_name AS customer_name,
            s.service_name,
            salonist.full_name AS salonist_name

         FROM appointments a

         INNER JOIN users c
            ON a.customer_id = c.id

         INNER JOIN services s
            ON a.service_id = s.id

         LEFT JOIN users salonist
            ON a.salonist_id = salonist.id

         ORDER BY a.created_at DESC

         LIMIT 6"
    );

    $recentAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $exception) {

    error_log(
        'Admin dashboard error: ' . $exception->getMessage()
    );

    $dashboardError =
        'Some dashboard information could not be loaded. Please check the database.';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | Grace Beauty Salon</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
          rel="stylesheet">

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

</head>

<body class="bg-[#FAF8FC] font-sans text-charcoal">

<div class="min-h-screen flex flex-col">

    <!-- HEADER -->

    <header class="bg-white border-b border-purple-100 px-5 lg:px-8 py-4 flex items-center justify-between">

        <div>

            <h1 class="font-display text-2xl font-bold text-violet">
                Grace Beauty Salon
            </h1>

            <p class="text-xs text-gray-500">
                Administrator Portal
            </p>

        </div>

        <div class="flex items-center gap-4">

            <div class="hidden sm:block text-right">

                <p class="font-semibold text-sm">
                    <?= e($adminName) ?>
                </p>

                <p class="text-xs text-gray-500">
                    Administrator
                </p>

            </div>

            <div class="w-10 h-10 rounded-full bg-lavender text-violet flex items-center justify-center font-bold">

                <?= e(strtoupper(substr($firstName, 0, 1))) ?>

            </div>

            <a href="../auth/logout.php"
               class="text-sm font-semibold text-burgundy hover:underline">

                Logout

            </a>

        </div>

    </header>


    <div class="flex flex-1">

        <!-- SIDEBAR -->

        <aside class="hidden md:block w-64 bg-white border-r border-purple-100 p-5">

            <p class="text-xs uppercase tracking-widest text-gray-400 font-bold mb-5">
                Main Menu
            </p>

            <nav class="space-y-2">

                <!-- Dashboard -->

                <a href="index.php"
                   class="flex items-center gap-3 p-3 rounded-xl font-semibold bg-lavender text-violet">

                    <span>Dashboard</span>

                </a>


                <!-- Salonist Management -->

                <a href="salonists.php"
                   class="flex items-center gap-3 p-3 rounded-xl font-semibold text-gray-600 hover:bg-purple-50">

                    <span>Salonist Management</span>

                </a>


                <!-- Service Management -->

                <a href="services.php"
                   class="flex items-center gap-3 p-3 rounded-xl font-semibold text-gray-600 hover:bg-purple-50">

                    <span>Service Management</span>

                </a>


                <!-- Customers -->

                <a href="customers.php"
                   class="flex items-center gap-3 p-3 rounded-xl font-semibold text-gray-600 hover:bg-purple-50">

                    <span>Customers</span>

                </a>


                <!-- Appointments -->

                <a href="appointments.php"
                   class="flex items-center gap-3 p-3 rounded-xl font-semibold text-gray-600 hover:bg-purple-50">

                    <span>Appointments</span>

                </a>

            </nav>


            <div class="mt-10 bg-lavender rounded-2xl p-4">

                <p class="font-bold text-violet text-sm mb-2">
                    Grace Beauty Salon
                </p>

                <p class="text-xs text-gray-600 leading-relaxed">

                    Manage your salon, staff, customers and appointments from one place.

                </p>

            </div>

        </aside>


        <!-- MAIN CONTENT -->

        <main class="flex-1 p-5 lg:p-8 overflow-x-hidden">


            <?php if ($dashboardError): ?>

                <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 mb-6">

                    <?= e($dashboardError) ?>

                </div>

            <?php endif; ?>


            <!-- DASHBOARD INTRODUCTION -->

            <div class="mb-8">

                <p class="text-sm text-burgundy font-bold uppercase tracking-widest mb-2">

                    Administrator overview

                </p>

                <h2 class="font-display text-3xl md:text-4xl font-bold mb-2">

                    Welcome back, <?= e($firstName) ?>!

                </h2>

                <p class="text-gray-500">

                    Here's what's happening at Grace Beauty Salon today.

                </p>

            </div>


            <!-- STATISTICS -->

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">


                <!-- CUSTOMERS -->

                <div class="bg-white p-6 rounded-2xl border border-purple-100 shadow-sm">

                    <p class="text-sm text-gray-500 mb-3">
                        Total Customers
                    </p>

                    <h3 class="text-3xl font-bold text-violet">

                        <?= number_format($totalCustomers) ?>

                    </h3>

                    <p class="text-xs text-gray-400 mt-2">
                        Registered customers
                    </p>

                </div>


                <!-- SALONISTS -->

                <div class="bg-white p-6 rounded-2xl border border-purple-100 shadow-sm">

                    <p class="text-sm text-gray-500 mb-3">
                        Active Salonists
                    </p>

                    <h3 class="text-3xl font-bold text-violet">

                        <?= number_format($totalSalonists) ?>

                    </h3>

                    <p class="text-xs text-gray-400 mt-2">
                        Available staff accounts
                    </p>

                </div>


                <!-- SERVICES -->

                <div class="bg-white p-6 rounded-2xl border border-purple-100 shadow-sm">

                    <p class="text-sm text-gray-500 mb-3">
                        Active Services
                    </p>

                    <h3 class="text-3xl font-bold text-violet">

                        <?= number_format($totalServices) ?>

                    </h3>

                    <p class="text-xs text-gray-400 mt-2">
                        Salon services
                    </p>

                </div>


                <!-- APPOINTMENTS -->

                <div class="bg-white p-6 rounded-2xl border border-purple-100 shadow-sm">

                    <p class="text-sm text-gray-500 mb-3">
                        Total Appointments
                    </p>

                    <h3 class="text-3xl font-bold text-violet">

                        <?= number_format($totalAppointments) ?>

                    </h3>

                    <p class="text-xs text-gray-400 mt-2">
                        All booking requests
                    </p>

                </div>

            </div>


            <!-- APPOINTMENT STATUS -->

            <div class="mb-8">

                <h3 class="font-display text-2xl font-bold mb-5">

                    Appointment Overview

                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">


                    <!-- PENDING -->

                    <div class="bg-white p-6 rounded-2xl border border-yellow-100">

                        <p class="text-sm text-gray-500">
                            Pending
                        </p>

                        <h3 class="text-3xl font-bold text-yellow-600 mt-2">

                            <?= number_format($pendingAppointments) ?>

                        </h3>

                    </div>


                    <!-- CONFIRMED -->

                    <div class="bg-white p-6 rounded-2xl border border-blue-100">

                        <p class="text-sm text-gray-500">
                            Confirmed
                        </p>

                        <h3 class="text-3xl font-bold text-blue-600 mt-2">

                            <?= number_format($confirmedAppointments) ?>

                        </h3>

                    </div>


                    <!-- COMPLETED -->

                    <div class="bg-white p-6 rounded-2xl border border-green-100">

                        <p class="text-sm text-gray-500">
                            Completed
                        </p>

                        <h3 class="text-3xl font-bold text-green-600 mt-2">

                            <?= number_format($completedAppointments) ?>

                        </h3>

                    </div>

                </div>

            </div>


            <!-- RECENT APPOINTMENTS -->

            <section class="bg-white rounded-2xl border border-purple-100 shadow-sm overflow-hidden">


                <div class="p-6 border-b border-purple-50 flex items-center justify-between">

                    <div>

                        <h3 class="font-display text-xl font-bold">

                            Recent Appointments

                        </h3>

                        <p class="text-sm text-gray-500 mt-1">

                            Latest customer booking requests

                        </p>

                    </div>


                    <!-- UPDATED VIEW ALL LINK -->

                    <a href="appointments.php"
                       class="text-sm font-bold text-burgundy hover:underline">

                        View all

                    </a>

                </div>


                <?php if (!empty($recentAppointments)): ?>

                    <div class="overflow-x-auto">

                        <table class="w-full text-left text-sm">

                            <thead class="bg-purple-50 text-gray-600">

                                <tr>

                                    <th class="p-4">Customer</th>

                                    <th class="p-4">Service</th>

                                    <th class="p-4">Date</th>

                                    <th class="p-4">Salonist</th>

                                    <th class="p-4">Status</th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach ($recentAppointments as $appointment): ?>

                                    <tr class="border-t border-purple-50">


                                        <td class="p-4 font-semibold">

                                            <?= e($appointment['customer_name']) ?>

                                        </td>


                                        <td class="p-4">

                                            <?= e($appointment['service_name']) ?>

                                        </td>


                                        <td class="p-4">

                                            <?= e(date(
                                                'd M Y',
                                                strtotime($appointment['appointment_date'])
                                            )) ?>

                                        </td>


                                        <td class="p-4">

                                            <?= e($appointment['salonist_name'] ?? 'Not assigned') ?>

                                        </td>


                                        <td class="p-4">

                                            <?php

                                            $statusColors = [

                                                'pending' => 'bg-yellow-100 text-yellow-800',

                                                'confirmed' => 'bg-blue-100 text-blue-800',

                                                'completed' => 'bg-green-100 text-green-800',

                                                'cancelled' => 'bg-red-100 text-red-800'

                                            ];

                                            $status = $appointment['status'];

                                            ?>

                                            <span class="px-3 py-1 rounded-full text-xs font-bold <?= $statusColors[$status] ?? 'bg-gray-100 text-gray-700' ?>">

                                                <?= e(ucfirst($status)) ?>

                                            </span>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>


                <?php else: ?>

                    <div class="p-10 text-center text-gray-500">

                        No appointments have been submitted yet.

                    </div>

                <?php endif; ?>


            </section>

        </main>

    </div>


    <!-- FOOTER -->

    <footer class="bg-white border-t border-purple-100 p-5 text-center">

        <p class="text-sm text-gray-500">

            &copy; <?= date('Y') ?> Grace Beauty Salon. Administrator Portal.

        </p>

    </footer>

</div>

</body>

</html>