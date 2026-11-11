<?php

// Retrieve available services.
$stmt = $pdo->query(
    "SELECT id, service_name, price, duration_minutes
     FROM services
     WHERE is_active = 1
     ORDER BY service_name ASC"
);

$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve active salonists and the services they provide.
$salonistStmt = $pdo->query(
    "SELECT
        u.id,
        u.full_name,
        GROUP_CONCAT(ss.service_id) AS service_ids
     FROM users u
     INNER JOIN salonist_services ss ON ss.salonist_id = u.id
     WHERE u.role = 'salonist'
       AND u.is_active = 1
     GROUP BY u.id, u.full_name
     ORDER BY u.full_name ASC"
);

$salonists = $salonistStmt->fetchAll(PDO::FETCH_ASSOC);

// Selected service from dashboard.
$selectedService = (int) ($_GET['service_id'] ?? 0);

// Retrieve any booking error.
$bookingError = $_SESSION['booking_error'] ?? '';
unset($_SESSION['booking_error']);

?>

<section class="max-w-4xl mx-auto">

    <div class="mb-8">

        <p class="text-burgundy uppercase tracking-widest text-xs font-bold mb-2">
            Your next beauty experience
        </p>

        <h2 class="font-display text-3xl text-charcoal mb-2">
            Book an Appointment
        </h2>

        <p class="text-gray-500">
            Choose your preferred service, salonist, date and time.
        </p>

    </div>

    <?php if ($bookingError): ?>

        <div class="bg-red-50 text-red-700 border border-red-200 rounded-xl p-4 mb-6">
            <?= e($bookingError) ?>
        </div>

    <?php endif; ?>

    <form
        method="POST"
        action="process-booking.php"
        class="bg-white rounded-3xl p-6 md:p-8 border border-purple-100 space-y-6"
    >

        <!-- CSRF protection -->
        <input
            type="hidden"
            name="csrf_token"
            value="<?= e($_SESSION['csrf_token']) ?>"
        >

        <!-- SERVICE -->
        <div>

            <label class="block font-semibold text-charcoal mb-2">
                Select Service
            </label>

            <select
                name="service_id"
                id="service_id"
                required
                class="w-full border border-gray-200 rounded-xl p-3 focus:ring-2 focus:ring-purple-300"
            >

                <option value="">
                    Choose a service
                </option>

                <?php foreach ($services as $service): ?>

                    <option
                        value="<?= (int) $service['id'] ?>"
                        <?= $selectedService === (int) $service['id'] ? 'selected' : '' ?>
                    >
                        <?= e($service['service_name']) ?>
                        - KSh <?= number_format((float) $service['price'], 2) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <!-- SALONIST -->
        <div>

            <label class="block font-semibold text-charcoal mb-2">
                Choose Your Salonist
            </label>

            <select
                name="salonist_id"
                id="salonist_id"
                required
                class="w-full border border-gray-200 rounded-xl p-3 focus:ring-2 focus:ring-purple-300"
            >

                <option value="any">
                    Any Available Salonist
                </option>

                <?php foreach ($salonists as $salonist): ?>

                    <option
                        value="<?= (int) $salonist['id'] ?>"
                        data-services="<?= e($salonist['service_ids']) ?>"
                    >
                        <?= e($salonist['full_name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <p class="text-sm text-gray-500 mt-2">
                Choose a preferred salonist or let us assign an available one.
            </p>

        </div>

        <!-- DATE AND TIME -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <div>

                <label class="block font-semibold text-charcoal mb-2">
                    Appointment Date
                </label>

                <input
                    type="date"
                    name="appointment_date"
                    min="<?= date('Y-m-d') ?>"
                    required
                    class="w-full border border-gray-200 rounded-xl p-3"
                >

            </div>

            <div>

                <label class="block font-semibold text-charcoal mb-2">
                    Appointment Time
                </label>

                <input
                    type="time"
                    name="appointment_time"
                    required
                    class="w-full border border-gray-200 rounded-xl p-3"
                >

            </div>

        </div>

        <!-- STYLE PREFERENCES -->
        <div>

            <label class="block font-semibold text-charcoal mb-2">
                Style Preferences
            </label>

            <textarea
                name="style_preferences"
                rows="3"
                maxlength="2000"
                placeholder="Describe your preferred style..."
                class="w-full border border-gray-200 rounded-xl p-3"
            ></textarea>

        </div>

        <!-- ADDITIONAL NOTES -->
        <div>

            <label class="block font-semibold text-charcoal mb-2">
                Additional Notes
            </label>

            <textarea
                name="additional_notes"
                rows="3"
                maxlength="2000"
                placeholder="Anything else we should know?"
                class="w-full border border-gray-200 rounded-xl p-3"
            ></textarea>

        </div>

        <!-- INFORMATION -->
        <div class="bg-lavender rounded-xl p-4 text-sm text-violet">

            Your appointment will initially have pending status.

            The assigned salonist will review and confirm your request.

        </div>

        <!-- SUBMIT -->
        <button
            type="submit"
            class="w-full bg-violet hover:bg-burgundy text-white font-bold py-4 rounded-xl transition"
        >
            Submit Appointment Request
        </button>

    </form>

</section>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const serviceSelect = document.getElementById('service_id');
    const salonistSelect = document.getElementById('salonist_id');

    function filterSalonists() {

        const selectedService = serviceSelect.value;
        let availableSalonistCount = 0;

        Array.from(salonistSelect.options).forEach(function (option) {

            if (option.value === 'any') {
                option.hidden = false;
                return;
            }

            const services = (option.dataset.services || '')
                .split(',')
                .map(Number);

            const matches = selectedService !== '' &&
                services.includes(Number(selectedService));

            option.hidden = !matches;

            if (matches) {
                availableSalonistCount++;
            }

        });

        const currentOption = salonistSelect.selectedOptions[0];

        if (
            currentOption &&
            currentOption.value !== 'any' &&
            currentOption.hidden
        ) {
            salonistSelect.value = 'any';
        }

        const anyOption = salonistSelect.querySelector('option[value="any"]');

        if (selectedService === '') {
            anyOption.textContent = 'Select a service first';
            salonistSelect.value = 'any';
        } else if (availableSalonistCount === 0) {
            anyOption.textContent = 'No salonist currently offers this service';
            salonistSelect.value = 'any';
        } else {
            anyOption.textContent = 'Any Available Salonist';
        }

    }

    serviceSelect.addEventListener('change', filterSalonists);

    filterSalonists();

});
</script>