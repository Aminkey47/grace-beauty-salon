<?php

session_start();

require_once __DIR__ . '/../config/database.php';

// ============================================
// 1. CUSTOMER AUTHENTICATION
// ============================================

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'customer') {

    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: ../admin/index.php');
        exit;
    }

    if (($_SESSION['role'] ?? '') === 'salonist') {
        header('Location: ../salonist/index.php');
        exit;
    }

    header('Location: ../auth/login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Customer';
$firstName = explode(' ', trim($fullName))[0];

// ============================================
// 2. PAGE ROUTING
// ============================================

$activePage = $_GET['page'] ?? 'dashboard';

$allowedPages = [
    'dashboard',
    'book',
    'appointments',
    'profile'
];

if (!in_array($activePage, $allowedPages, true)) {
    $activePage = 'dashboard';
}

// ============================================
// 3. HELPER FUNCTION
// ============================================

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// ============================================
// 4. CSRF TOKEN
// ============================================

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ============================================
// 5. DASHBOARD VARIABLES
// ============================================

$totalAppointments = 0;
$upcomingAppointments = 0;
$completedAppointments = 0;

$nextAppointment = null;
$services = [];
$serviceImages = [];

$dashboardError = '';

$pageTitles = [
    'dashboard' => 'My Dashboard',
    'book' => 'Book an Appointment',
    'appointments' => 'My Appointments',
    'profile' => 'My Profile'
];

// ============================================
// 6. LOAD DASHBOARD INFORMATION
// ============================================

try {

    // Total appointments

    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM appointments
         WHERE customer_id = ?"
    );

    $stmt->execute([$userId]);

    $totalAppointments = (int) $stmt->fetchColumn();


    // Upcoming appointments

    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM appointments
         WHERE customer_id = ?
         AND appointment_date >= CURDATE()
         AND status IN ('pending', 'confirmed')"
    );

    $stmt->execute([$userId]);

    $upcomingAppointments = (int) $stmt->fetchColumn();


    // Completed appointments

    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM appointments
         WHERE customer_id = ?
         AND status = 'completed'"
    );

    $stmt->execute([$userId]);

    $completedAppointments = (int) $stmt->fetchColumn();


    // Next appointment

    $stmt = $pdo->prepare(
        "SELECT
            a.*,
            s.service_name,
            s.price,
            u.full_name AS salonist_name

         FROM appointments a

         LEFT JOIN services s
            ON a.service_id = s.id

         LEFT JOIN users u
            ON a.salonist_id = u.id

         WHERE a.customer_id = ?
         AND a.appointment_date >= CURDATE()
         AND a.status IN ('pending', 'confirmed')

         ORDER BY
            a.appointment_date ASC,
            a.appointment_time ASC

         LIMIT 1"
    );

    $stmt->execute([$userId]);

    $nextAppointment = $stmt->fetch();


    // Active services

    $stmt = $pdo->query(
        "SELECT *
         FROM services
         WHERE is_active = 1
         ORDER BY service_name ASC"
    );

    $services = $stmt->fetchAll();


    // Service photographs

    $serviceImages = [
        'hair styling' =>
        'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=900&q=85',

        'hair braiding' =>
        'https://images.unsplash.com/photo-1595476108010-b4d1f102b1b1?auto=format&fit=crop&w=900&q=85',

        'hair treatment' =>
        'https://images.unsplash.com/photo-1527799820374-dcf8d9d4a388?auto=format&fit=crop&w=900&q=85',

        'manicure' =>
        'https://images.unsplash.com/photo-1604654894610-df63bc536371?auto=format&fit=crop&w=900&q=85',

        'pedicure' =>
        'https://images.unsplash.com/photo-1519014816548-bf5fe059798b?auto=format&fit=crop&w=900&q=85',

        'makeup' =>
        'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=900&q=85',

        'bridal makeup' =>
        'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=900&q=85'
    ];

} catch (PDOException $e) {

    error_log($e->getMessage());

    $dashboardError =
        'We could not load some dashboard information. Please refresh the page.';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= e($pageTitles[$activePage]) ?> | Grace Beauty Salon
    </title>

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

    <style>

        body {
            font-family: 'DM Sans', sans-serif;
            background: #FAF8FC;
        }

        .sidebar-link {
            transition: all 0.25s ease;
        }

        .sidebar-link:hover {
            background: #F1E8F8;
            color: #542078;
        }

        .sidebar-link.active {
            background: #542078;
            color: white;
            box-shadow: 0 5px 15px rgba(84, 32, 120, 0.15);
        }

        .dashboard-card {
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(40, 20, 50, 0.07);
        }

    </style>

</head>

<body>

<div class="min-h-screen flex">

    <!-- MOBILE SIDEBAR OVERLAY -->

    <div
        id="sidebarOverlay"
        onclick="toggleSidebar()"
        class="fixed inset-0 bg-black/40 z-40 hidden lg:hidden">
    </div>


    <!-- SIDEBAR -->

    <aside
        id="sidebar"
        class="fixed lg:sticky top-0 left-0 z-50 w-72 h-screen bg-white border-r border-purple-100 flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-300">

        <!-- LOGO -->

        <div class="px-7 py-7 border-b border-purple-50">

            <a href="index.php"
               class="text-3xl font-display font-bold text-violet">

                Grace<span class="text-burgundy">.</span>

            </a>

            <p class="text-xs text-gray-400 mt-1 tracking-widest uppercase">
                Beauty Salon
            </p>

        </div>


        <!-- CUSTOMER PROFILE -->

        <div class="px-6 py-6">

            <div class="flex items-center gap-3">

                <div class="w-12 h-12 rounded-full bg-lavender flex items-center justify-center text-violet font-bold text-lg">

                    <?= strtoupper(substr($firstName, 0, 1)) ?>

                </div>

                <div class="min-w-0">

                    <h3 class="font-semibold text-charcoal truncate">
                        <?= e($fullName) ?>
                    </h3>

                    <p class="text-xs text-gray-400">
                        Customer Account
                    </p>

                </div>

            </div>

        </div>


        <!-- NAVIGATION -->

        <nav class="flex-1 px-5 space-y-2">

            <p class="text-xs uppercase tracking-widest text-gray-400 font-bold px-3 mb-4">
                Main Menu
            </p>

            <a
                href="index.php?page=dashboard"
                class="sidebar-link <?= $activePage === 'dashboard' ? 'active' : 'text-gray-600' ?> flex items-center gap-3 px-4 py-3 rounded-xl font-medium">

                <span>⌂</span>
                Dashboard

            </a>

            <a
                href="index.php?page=book"
                class="sidebar-link <?= $activePage === 'book' ? 'active' : 'text-gray-600' ?> flex items-center gap-3 px-4 py-3 rounded-xl font-medium">

                <span>＋</span>
                Book Appointment

            </a>

            <a
                href="index.php?page=appointments"
                class="sidebar-link <?= $activePage === 'appointments' ? 'active' : 'text-gray-600' ?> flex items-center gap-3 px-4 py-3 rounded-xl font-medium">

                <span>▣</span>
                My Appointments

            </a>

            <a
                href="index.php?page=profile"
                class="sidebar-link <?= $activePage === 'profile' ? 'active' : 'text-gray-600' ?> flex items-center gap-3 px-4 py-3 rounded-xl font-medium">

                <span>◉</span>
                My Profile

            </a>

        </nav>


        <!-- SIDEBAR BOTTOM -->

        <div class="p-5 border-t border-purple-50">

            <div class="bg-lavender rounded-2xl p-4 mb-4">

                <p class="font-display text-violet text-lg mb-1">
                    A little self-care?
                </p>

                <p class="text-xs text-gray-500 mb-3">
                    You deserve a moment to feel beautiful.
                </p>

                <a
                    href="index.php?page=book"
                    class="text-xs font-bold text-burgundy hover:underline">

                    Book your next visit →

                </a>

            </div>

            <a
                href="../auth/logout.php"
                class="flex items-center gap-3 px-4 py-3 rounded-xl text-red-600 hover:bg-red-50 transition font-medium">

                <span>↪</span>
                Sign Out

            </a>

        </div>

    </aside>


    <!-- MAIN CONTENT AREA -->

    <div class="flex-1 min-w-0">


        <!-- HEADER -->

        <header class="h-20 bg-white border-b border-purple-100 flex items-center justify-between px-5 md:px-8 sticky top-0 z-30">

            <div class="flex items-center gap-4">

                <button
                    onclick="toggleSidebar()"
                    class="lg:hidden text-2xl text-violet"
                    aria-label="Toggle navigation">

                    ☰

                </button>

                <div>

                    <h1 class="font-display text-xl md:text-2xl text-charcoal">

                        <?= e($pageTitles[$activePage]) ?>

                    </h1>

                    <p class="text-xs text-gray-400 hidden sm:block">
                        Your personal beauty space.
                    </p>

                </div>

            </div>

            <div class="flex items-center gap-3">

                <div class="hidden sm:block text-right">

                    <p class="text-sm font-semibold text-charcoal">
                        <?= e($firstName) ?>
                    </p>

                    <p class="text-xs text-gray-400">
                        Customer
                    </p>

                </div>

                <div class="w-10 h-10 rounded-full bg-violet text-white flex items-center justify-center font-bold">

                    <?= strtoupper(substr($firstName, 0, 1)) ?>

                </div>

            </div>

        </header>


        <!-- PAGE CONTENT -->

        <main class="p-5 md:p-8 lg:p-10">

            <?php if (!empty($dashboardError)): ?>

                <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl mb-6">
                    <?= e($dashboardError) ?>
                </div>

            <?php endif; ?>


            <!-- DASHBOARD PAGE -->

            <?php if ($activePage === 'dashboard'): ?>

                <!-- WELCOME SECTION -->

                <section class="bg-gradient-to-r from-violet to-royal rounded-3xl p-7 md:p-10 text-white relative overflow-hidden mb-8">

                    <div class="relative z-10 max-w-2xl">

                        <p class="text-purple-200 uppercase tracking-widest text-xs font-bold mb-3">
                            Your beauty journey starts here
                        </p>

                        <h2 class="font-display text-3xl md:text-4xl mb-4">
                            Hello, <?= e($firstName) ?>!
                        </h2>

                        <p class="text-purple-100 leading-relaxed mb-7">
                            Welcome to your personal beauty space.
                            Ready for some well-deserved self-care?
                            Explore our services and book your next appointment.
                        </p>

                        <a
                            href="index.php?page=book"
                            class="inline-block bg-white text-violet px-7 py-3 rounded-xl font-bold hover:bg-lavender transition">

                            Book an Appointment →

                        </a>

                    </div>

                    <div class="absolute -right-10 -bottom-20 w-72 h-72 rounded-full border-[35px] border-white/10"></div>

                    <div class="absolute right-20 -top-20 w-52 h-52 rounded-full border-[25px] border-white/10"></div>

                </section>


                <!-- STATISTICS -->

                <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 mb-10">

                    <div class="dashboard-card bg-white rounded-2xl p-6 border border-purple-50">

                        <p class="text-gray-500 text-sm font-medium mb-5">
                            Total Appointments
                        </p>

                        <h3 class="text-3xl font-bold text-charcoal">
                            <?= $totalAppointments ?>
                        </h3>

                        <p class="text-xs text-gray-400 mt-2">
                            All your bookings
                        </p>

                    </div>


                    <div class="dashboard-card bg-white rounded-2xl p-6 border border-purple-50">

                        <p class="text-gray-500 text-sm font-medium mb-5">
                            Upcoming Appointments
                        </p>

                        <h3 class="text-3xl font-bold text-charcoal">
                            <?= $upcomingAppointments ?>
                        </h3>

                        <p class="text-xs text-gray-400 mt-2">
                            Pending and confirmed bookings
                        </p>

                    </div>


                    <div class="dashboard-card bg-white rounded-2xl p-6 border border-purple-50">

                        <p class="text-gray-500 text-sm font-medium mb-5">
                            Completed Appointments
                        </p>

                        <h3 class="text-3xl font-bold text-charcoal">
                            <?= $completedAppointments ?>
                        </h3>

                        <p class="text-xs text-gray-400 mt-2">
                            Your completed salon visits
                        </p>

                    </div>

                </section>


                <!-- NEXT APPOINTMENT -->

                <section class="mb-10">

                    <div class="flex items-center justify-between mb-5">

                        <h2 class="font-display text-2xl text-charcoal">
                            Your Next Appointment
                        </h2>

                        <a
                            href="index.php?page=appointments"
                            class="text-sm text-burgundy font-semibold hover:underline">

                            View all →

                        </a>

                    </div>


                    <?php if ($nextAppointment): ?>

                        <div class="bg-white border border-purple-100 rounded-2xl p-6 md:p-8">

                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">

                                <div>

                                    <h3 class="font-bold text-charcoal text-lg">

                                        <?= e($nextAppointment['service_name'] ?? 'Salon Service') ?>

                                    </h3>

                                    <p class="text-sm text-gray-500 mt-2">

                                        <?= date('l, d F Y', strtotime($nextAppointment['appointment_date'])) ?>

                                    </p>

                                    <p class="text-sm text-gray-500">

                                        <?= date('h:i A', strtotime($nextAppointment['appointment_time'])) ?>

                                    </p>

                                </div>

                                <div>

                                    <span class="inline-block px-4 py-2 rounded-full text-xs font-bold <?= $nextAppointment['status'] === 'confirmed' ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700' ?>">

                                        <?= e(ucfirst($nextAppointment['status'])) ?>

                                    </span>

                                    <p class="text-xs text-gray-400 mt-3">

                                        Stylist:
                                        <?= e($nextAppointment['salonist_name'] ?? 'To be assigned') ?>

                                    </p>

                                </div>

                            </div>

                        </div>

                    <?php else: ?>

                        <div class="bg-white border border-purple-100 rounded-2xl p-8 text-center">

                            <h3 class="font-display text-xl text-charcoal mb-2">
                                No upcoming appointments
                            </h3>

                            <p class="text-sm text-gray-500 mb-6">
                                Your next beauty experience is just a few clicks away.
                            </p>

                            <a
                                href="index.php?page=book"
                                class="inline-block bg-violet text-white px-6 py-3 rounded-xl font-semibold hover:bg-burgundy transition">

                                Book Your First Appointment

                            </a>

                        </div>

                    <?php endif; ?>

                </section>


                <!-- AVAILABLE SERVICES -->

                <section>

                    <div class="flex items-center justify-between mb-5">

                        <div>

                            <h2 class="font-display text-2xl text-charcoal">
                                Explore Our Services
                            </h2>

                            <p class="text-sm text-gray-500 mt-1">
                                Discover something special for yourself.
                            </p>

                        </div>

                        <a
                            href="index.php?page=book"
                            class="text-sm text-burgundy font-semibold hover:underline">

                            Book now →

                        </a>

                    </div>


                    <?php if (!empty($services)): ?>

                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">

                            <?php foreach ($services as $service): ?>

                                <div class="dashboard-card bg-white rounded-2xl p-6 border border-purple-50">

                                    <div class="w-full h-52 rounded-2xl overflow-hidden mb-5 bg-lavender">

                                        <img
                                            src="<?= e($serviceImages[strtolower(trim($service['service_name']))] ?? 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=900&q=85') ?>"
                                            alt="<?= e($service['service_name']) ?>"
                                            class="w-full h-full object-cover"
                                            loading="lazy">

                                    </div>

                                    <h3 class="font-bold text-charcoal text-lg mb-2">
                                        <?= e($service['service_name']) ?>
                                    </h3>

                                    <p class="text-sm text-gray-500 leading-relaxed mb-4">
                                        <?= e($service['description'] ?: 'Experience professional care at Grace Beauty Salon.') ?>
                                    </p>

                                    <div class="flex items-center justify-between gap-3">

                                        <span class="text-violet font-bold">
                                            KSh <?= number_format((float) $service['price'], 2) ?>
                                        </span>

                                        <a
                                            href="index.php?page=book&service_id=<?= (int) $service['id'] ?>"
                                            class="text-sm font-bold text-burgundy hover:underline">

                                            Book →

                                        </a>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <div class="bg-white rounded-2xl p-8 text-center border border-purple-100">

                            <p class="text-gray-500">
                                Our services will appear here once the administrator adds them.
                            </p>

                        </div>

                    <?php endif; ?>

                </section>


            <!-- BOOK APPOINTMENT PAGE -->

            <?php elseif ($activePage === 'book'): ?>

                <?php require __DIR__ . '/book-appointment.php'; ?>


            <!-- MY APPOINTMENTS PAGE -->

            <?php elseif ($activePage === 'appointments'): ?>

                <?php require __DIR__ . '/my-appointments.php'; ?>


            <!-- PROFILE PAGE -->

            <?php elseif ($activePage === 'profile'): ?>

                <?php require __DIR__ . '/profile.php'; ?>

            <?php endif; ?>

        </main>


        <!-- FOOTER -->

        <footer class="px-5 md:px-8 py-6 border-t border-purple-100 bg-white">

            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">

                <p class="text-sm text-gray-400">
                    © <?= date('Y') ?> Grace Beauty Salon. All rights reserved.
                </p>

                <p class="text-sm text-gray-400">
                    Made with care for your beauty.
                </p>

            </div>

        </footer>

    </div>

</div>


<!-- MOBILE SIDEBAR SCRIPT -->

<script>

    function toggleSidebar() {

        const sidebar = document.getElementById('sidebar');

        const overlay = document.getElementById('sidebarOverlay');

        sidebar.classList.toggle('-translate-x-full');

        overlay.classList.toggle('hidden');

    }

</script>

</body>
</html>