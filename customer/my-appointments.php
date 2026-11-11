
<?php

$stmt = $pdo->prepare(
    "SELECT
        a.id,
        a.appointment_date,
        a.appointment_time,
        a.style_preferences,
        a.additional_notes,
        a.status,
        s.service_name,
        s.price,
        u.full_name AS salonist_name

     FROM appointments a

     INNER JOIN services s
        ON a.service_id = s.id

     LEFT JOIN users u
        ON a.salonist_id = u.id

     WHERE a.customer_id = ?

     ORDER BY
        a.appointment_date DESC,
        a.appointment_time DESC"
);

$stmt->execute([$userId]);

$appointments = $stmt->fetchAll();

?>

<section>

    <div class="mb-8">
        <h2 class="font-display text-3xl text-charcoal mb-2">
            My Appointments
        </h2>

        <p class="text-gray-500">
            View your booking history and appointment status.
        </p>
    </div>

    <?php if (empty($appointments)): ?>

        <div class="bg-white rounded-3xl p-10 text-center border border-purple-100">

            <h3 class="font-display text-2xl text-charcoal mb-3">
                No Appointments Yet
            </h3>

            <p class="text-gray-500 mb-6">
                You have not booked an appointment yet.
            </p>

            <a href="index.php?page=book"
               class="inline-block bg-violet text-white px-6 py-3 rounded-xl font-semibold hover:bg-burgundy transition">
                Book an Appointment
            </a>

        </div>

    <?php else: ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <?php foreach ($appointments as $appointment): ?>

                <?php
                $status = strtolower($appointment['status']);

                $statusClasses = [
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'confirmed' => 'bg-green-100 text-green-800',
                    'completed' => 'bg-blue-100 text-blue-800',
                    'cancelled' => 'bg-red-100 text-red-800'
                ];

                $statusClass = $statusClasses[$status]
                    ?? 'bg-gray-100 text-gray-700';
                ?>

                <div class="bg-white rounded-2xl p-6 border border-purple-100">

                    <div class="flex justify-between items-start gap-3 mb-5">

                        <div>
                            <h3 class="font-bold text-lg text-charcoal">
                                <?= e($appointment['service_name']) ?>
                            </h3>

                            <p class="text-sm text-gray-500 mt-1">
                                Booking #<?= (int) $appointment['id'] ?>
                            </p>
                        </div>

                        <span class="px-3 py-1 rounded-full text-xs font-bold <?= $statusClass ?>">
                            <?= e(ucfirst($status)) ?>
                        </span>

                    </div>

                    <div class="space-y-3 text-sm text-gray-600">

                        <p>
                            <strong>Date:</strong>
                            <?= e(date('d M Y', strtotime($appointment['appointment_date']))) ?>
                        </p>

                        <p>
                            <strong>Time:</strong>
                            <?= e(date('h:i A', strtotime($appointment['appointment_time']))) ?>
                        </p>

                        <p>
                            <strong>Price:</strong>
                            KSh <?= number_format((float) $appointment['price'], 2) ?>
                        </p>

                        <p>
                            <strong>Salonist:</strong>
                            <?= e($appointment['salonist_name'] ?? 'Not assigned yet') ?>
                        </p>

                        <?php if (!empty($appointment['style_preferences'])): ?>
                            <p>
                                <strong>Style Preferences:</strong>
                                <?= e($appointment['style_preferences']) ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($appointment['additional_notes'])): ?>
                            <p>
                                <strong>Additional Notes:</strong>
                                <?= e($appointment['additional_notes']) ?>
                            </p>
                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>
