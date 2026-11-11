<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';

// Check customer authentication.
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'customer'
) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

// Accept POST requests only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?page=book');
    exit;
}

// Validate CSRF token.
$token = $_POST['csrf_token'] ?? '';

if (
    !isset($_SESSION['csrf_token']) ||
    !is_string($token) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    $_SESSION['booking_error'] =
        'Invalid submission. Please refresh and try again.';

    header('Location: index.php?page=book');
    exit;
}

// Collect form data.
$serviceId = filter_var(
    $_POST['service_id'] ?? '',
    FILTER_VALIDATE_INT
);

$salonistChoice = $_POST['salonist_id'] ?? 'any';

$appointmentDate = trim($_POST['appointment_date'] ?? '');
$appointmentTime = trim($_POST['appointment_time'] ?? '');
$stylePreferences = trim($_POST['style_preferences'] ?? '');
$additionalNotes = trim($_POST['additional_notes'] ?? '');

// Validate date.
$dateObject = DateTime::createFromFormat(
    '!Y-m-d',
    $appointmentDate
);

$validDate = (
    $dateObject !== false &&
    $dateObject->format('Y-m-d') === $appointmentDate &&
    $appointmentDate >= date('Y-m-d')
);

// Validate time.
$validTime = preg_match(
    '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
    $appointmentTime
);

// Reject a time in the past.
if ($validDate && $validTime) {
    $requestedDateTime = strtotime(
        $appointmentDate . ' ' . $appointmentTime
    );

    if ($requestedDateTime <= time()) {
        $validTime = false;
    }
}

// Validate salonist selection.
$validSalonistChoice = (
    $salonistChoice === 'any' ||
    (
        filter_var($salonistChoice, FILTER_VALIDATE_INT) !== false &&
        (int) $salonistChoice > 0
    )
);

// Verify service.
$validService = false;

if ($serviceId !== false && $serviceId !== null) {

    $serviceStmt = $pdo->prepare(
        "SELECT id, service_name, price, duration_minutes
         FROM services
         WHERE id = ? AND is_active = 1"
    );

    $serviceStmt->execute([$serviceId]);

    $validService = $serviceStmt->fetch(PDO::FETCH_ASSOC);
}

// Validate submitted information.
if (!$validService) {

    $_SESSION['booking_error'] =
        'Please select a valid service.';

} elseif (!$validDate) {

    $_SESSION['booking_error'] =
        'Please select a valid appointment date.';

} elseif (!$validTime) {

    $_SESSION['booking_error'] =
        'Please select a valid future appointment time.';

} elseif (!$validSalonistChoice) {

    $_SESSION['booking_error'] =
        'Please select a valid salonist.';

} elseif (
    strlen($stylePreferences) > 2000 ||
    strlen($additionalNotes) > 2000
) {

    $_SESSION['booking_error'] =
        'Your notes are too long.';

} else {

    try {

        $pdo->beginTransaction();

        /*
         * Retrieve active salonists who provide
         * the selected service.
         */
        $eligibleStmt = $pdo->prepare(
            "SELECT
                u.id,
                u.full_name,
                u.email
             FROM users u
             INNER JOIN salonist_services ss
                ON ss.salonist_id = u.id
             WHERE u.role = 'salonist'
               AND u.is_active = 1
               AND ss.service_id = ?
             ORDER BY u.full_name ASC"
        );

        $eligibleStmt->execute([$serviceId]);

        $eligibleSalonists = $eligibleStmt->fetchAll(PDO::FETCH_ASSOC);

        // If a specific salonist was selected, keep only that salonist.
        if ($salonistChoice !== 'any') {

            $requestedSalonistId = (int) $salonistChoice;

            $eligibleSalonists = array_values(
                array_filter(
                    $eligibleSalonists,
                    function ($salonist) use ($requestedSalonistId) {
                        return (int) $salonist['id'] === $requestedSalonistId;
                    }
                )
            );

            if (empty($eligibleSalonists)) {
                throw new RuntimeException(
                    'The selected salonist does not provide this service or is unavailable.'
                );
            }
        }

        /*
         * Calculate the requested appointment interval.
         */
        $requestedStart = strtotime(
            $appointmentDate . ' ' . $appointmentTime
        );

        $requestedEnd = $requestedStart +
            ((int) $validService['duration_minutes'] * 60);

        $assignedSalonist = null;

        /*
         * Find a salonist without an overlapping
         * pending or confirmed appointment.
         */
        foreach ($eligibleSalonists as $salonist) {

            // Lock the salonist's user row during assignment.
            $lockStmt = $pdo->prepare(
                "SELECT id
                 FROM users
                 WHERE id = ?
                   AND role = 'salonist'
                   AND is_active = 1
                 FOR UPDATE"
            );

            $lockStmt->execute([(int) $salonist['id']]);

            if (!$lockStmt->fetch()) {
                continue;
            }

            $existingStmt = $pdo->prepare(
                "SELECT
                    a.appointment_time,
                    s.duration_minutes
                 FROM appointments a
                 INNER JOIN services s
                    ON s.id = a.service_id
                 WHERE a.salonist_id = ?
                   AND a.appointment_date = ?
                   AND a.status IN ('pending', 'confirmed')"
            );

            $existingStmt->execute([
                (int) $salonist['id'],
                $appointmentDate
            ]);

            $existingAppointments = $existingStmt->fetchAll(PDO::FETCH_ASSOC);

            $hasConflict = false;

            foreach ($existingAppointments as $existing) {

                $existingStart = strtotime(
                    $appointmentDate . ' ' . $existing['appointment_time']
                );

                $existingEnd = $existingStart +
                    ((int) $existing['duration_minutes'] * 60);

                // Two appointments conflict if their time intervals overlap.
                if (
                    $requestedStart < $existingEnd &&
                    $requestedEnd > $existingStart
                ) {
                    $hasConflict = true;
                    break;
                }
            }

            if (!$hasConflict) {
                $assignedSalonist = $salonist;
                break;
            }

        }

        if (!$assignedSalonist) {

            throw new RuntimeException(
                'No available salonist was found for this service and time. Please choose another time or salonist.'
            );

        }

        // Save appointment with its assigned salonist.
        $insertStmt = $pdo->prepare(
            "INSERT INTO appointments
            (
                customer_id,
                salonist_id,
                service_id,
                appointment_date,
                appointment_time,
                style_preferences,
                additional_notes,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
        );

        $insertStmt->execute([
            $userId,
            (int) $assignedSalonist['id'],
            $serviceId,
            $appointmentDate,
            $appointmentTime,
            $stylePreferences,
            $additionalNotes
        ]);

        $appointmentId = $pdo->lastInsertId();

        // Retrieve customer details.
        $customerStmt = $pdo->prepare(
            "SELECT full_name, email
             FROM users
             WHERE id = ?"
        );

        $customerStmt->execute([$userId]);

        $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);

        // Commit appointment to database.
        $pdo->commit();

        // Send customer confirmation email.
        $customerEmailSent = false;

        try {

            if ($customer) {

                $customerEmailSent = sendAppointmentConfirmation(
                    $customer['email'],
                    $customer['full_name'],
                    $validService['service_name'],
                    $appointmentDate,
                    $appointmentTime
                );

            }

        } catch (Throwable $emailError) {

            error_log(
                'Customer appointment email error: ' .
                $emailError->getMessage()
            );

        }

        // Notify assigned salonist.
        $salonistEmailSent = false;

        try {

            $salonistEmailSent = sendSalonistAppointmentNotification(
                $assignedSalonist['email'],
                $assignedSalonist['full_name'],
                $customer['full_name'] ?? 'Customer',
                $validService['service_name'],
                $appointmentDate,
                $appointmentTime,
                (int) $validService['duration_minutes'],
                $additionalNotes
            );

        } catch (Throwable $emailError) {

            error_log(
                'Salonist appointment notification error: ' .
                $emailError->getMessage()
            );

        }

        // Store success information.
        $_SESSION['booking_success'] =
            'Your appointment request has been submitted successfully.';

        $_SESSION['booking_email_sent'] = $customerEmailSent;

        $_SESSION['booking_appointment_id'] = $appointmentId;

        $_SESSION['booking_assigned_salonist'] =
            $assignedSalonist['full_name'];

        $_SESSION['booking_salonist_email_sent'] = $salonistEmailSent;

        // Redirect to success page.
        header('Location: booking-success.php');
        exit;

    } catch (RuntimeException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $_SESSION['booking_error'] = $e->getMessage();

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Booking error: ' . $e->getMessage());

        $_SESSION['booking_error'] =
            'We could not save your appointment. Please try again.';

    }

}

// Return to booking form if something goes wrong.
header('Location: index.php?page=book');
exit;

?>