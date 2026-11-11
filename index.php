<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Grace Beauty Salon | Beauty, Style & Elegance</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        violet: {
                            dark: '#542078',
                            DEFAULT: '#7138A8',
                            light: '#F1E8F8'
                        },
                        burgundy: '#9B2348',
                        charcoal: '#28212D'
                    },
                    fontFamily: {
                        sans: ['DM Sans', 'sans-serif'],
                        serif: ['Playfair Display', 'serif']
                    }
                }
            }
        }
    </script>

    <style>
        html {
            scroll-behaviour: smooth;
            scroll-behavior: smooth;
        }

        .hero {
            background-image:
                linear-gradient(
                    to right,
                    rgba(30, 15, 39, 0.82),
                    rgba(30, 15, 39, 0.35)
                ),
                url('https://images.fresha.com/locations/location-profile-images/118913/5249807/593dc4ff-30bb-40be-ad8b-08b3b3100322-LondonHairSalonMacleodTrail-CA-Alberta-Calgary-WindsorPark-Fresha.jpg?class=venue-gallery-small&f_width=1200');

            background-size: cover;
            background-position: center;
        }

        .service-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .service-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 15px 35px rgba(84, 32, 120, 0.13);
        }
    </style>
</head>

<body class="font-sans text-charcoal bg-white">

    <!-- NAVIGATION -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">

            <a href="index.php" class="text-2xl font-serif font-bold text-violet-dark">
                Grace <span class="text-burgundy">Beauty</span>
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm font-semibold">
                <a href="#home" class="hover:text-violet">Home</a>
                <a href="#about" class="hover:text-violet">About</a>
                <a href="#services" class="hover:text-violet">Services</a>
                <a href="#contact" class="hover:text-violet">Contact</a>
            </nav>

            <div class="hidden md:flex items-center gap-3">
                <a href="auth/login.php"
                   class="px-5 py-2.5 text-violet-dark border border-violet-dark rounded-full hover:bg-violet-light transition">
                    Login
                </a>

                <a href="auth/register.php"
                   class="px-5 py-2.5 bg-violet-dark text-white rounded-full hover:bg-burgundy transition">
                    Get Started
                </a>
            </div>

            <button id="menuButton"
                    class="md:hidden text-2xl text-violet-dark"
                    aria-label="Open navigation menu">
                ☰
            </button>

        </div>

        <!-- MOBILE MENU -->
        <div id="mobileMenu" class="hidden md:hidden px-6 pb-5 bg-white">
            <div class="flex flex-col gap-4 font-semibold">
                <a href="#home">Home</a>
                <a href="#about">About</a>
                <a href="#services">Services</a>
                <a href="#contact">Contact</a>
                <a href="auth/login.php" class="text-violet-dark">Login</a>
                <a href="auth/register.php" class="text-burgundy">Register</a>
            </div>
        </div>
    </header>


    <!-- HERO SECTION -->
    <section id="home" class="hero min-h-[600px] md:min-h-[680px] flex items-center">

        <div class="max-w-7xl mx-auto px-6 w-full py-24">

            <div class="max-w-2xl text-white">

                <p class="uppercase tracking-[4px] text-sm text-purple-200 mb-6">
                    Welcome to Grace Beauty Salon
                </p>

                <h1 class="font-serif text-5xl md:text-7xl leading-tight mb-6">
                    Beauty Begins With
                    <span class="text-purple-200">Confidence.</span>
                </h1>

                <p class="text-lg text-gray-200 leading-relaxed mb-9 max-w-xl">
                    Take a moment for yourself. Enjoy professional beauty
                    services in a comfortable and welcoming environment.
                    We're here to help you look and feel your best.
                </p>

                <div class="flex flex-wrap gap-4">

                    <a href="auth/register.php"
                       class="bg-burgundy hover:bg-white hover:text-burgundy text-white px-8 py-4 rounded-full font-semibold transition">
                        Book an Appointment
                    </a>

                    <a href="#services"
                       class="border border-white hover:bg-white hover:text-violet-dark text-white px-8 py-4 rounded-full font-semibold transition">
                        Explore Services
                    </a>

                </div>

            </div>

        </div>
    </section>


    <!-- INTRODUCTION -->
    <section id="about" class="py-24 bg-white">

        <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-2 gap-14 items-center">

            <div>
                <img
                    src="https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=1000&q=85"
                    alt="Beauty salon interior"
                    class="rounded-2xl shadow-xl w-full h-[420px] object-cover"
                >
            </div>

            <div>

                <p class="text-burgundy uppercase tracking-[3px] text-sm font-bold mb-4">
                    About Us
                </p>

                <h2 class="font-serif text-4xl md:text-5xl text-violet-dark mb-6">
                    A Little Time for Yourself
                </h2>

                <p class="text-gray-600 leading-8 mb-5">
                    At Grace Beauty Salon, we believe that beauty is more
                    than appearance. It is about confidence, self-care
                    and feeling comfortable in your own skin.
                </p>

                <p class="text-gray-600 leading-8 mb-8">
                    Our goal is to provide quality beauty services in a
                    friendly and relaxing environment where every client
                    receives care and attention.
                </p>

                <a href="#services"
                   class="inline-block bg-violet-dark text-white px-7 py-3 rounded-full hover:bg-burgundy transition">
                    Discover Our Services
                </a>

            </div>

        </div>

    </section>


    <!-- SERVICES SECTION -->
    <section id="services" class="py-24 bg-violet-light">

        <div class="max-w-7xl mx-auto px-6">

            <div class="text-center mb-14">

                <p class="text-burgundy uppercase tracking-[3px] text-sm font-bold mb-4">
                    What We Offer
                </p>

                <h2 class="font-serif text-4xl md:text-5xl text-violet-dark mb-5">
                    Our Beauty Services
                </h2>

                <p class="text-gray-600 max-w-2xl mx-auto">
                    From beautiful hairstyles to professional makeup
                    and nail care, discover services designed to make
                    you feel confident.
                </p>

            </div>


            <div class="grid md:grid-cols-3 gap-8">

                <!-- HAIR STYLING -->
                <div class="service-card bg-white rounded-2xl overflow-hidden shadow-md">

                    <img
                        src="https://static.wixstatic.com/media/6b13ad_122c0bf86c224dd8acf20192a34f2b30~mv2.webp/v1/fill/w_1000%2Ch_633%2Cal_c%2Cq_85%2Cusm_0.66_1.00_0.01/6b13ad_122c0bf86c224dd8acf20192a34f2b30~mv2.webp"
                        alt="Professional hair styling"
                        class="w-full h-64 object-cover"
                        loading="lazy"
                    >

                    <div class="p-7">

                        <h3 class="font-serif text-2xl text-violet-dark mb-3">
                            Hair Styling
                        </h3>

                        <p class="text-gray-600 leading-7 mb-6">
                            Refresh your look with professional hair
                            styling and treatments suited to your style.
                        </p>

                        <a href="auth/register.php"
                           class="text-burgundy font-bold hover:text-violet-dark">
                            Book Service →
                        </a>

                    </div>
                </div>


                <!-- MAKEUP -->
                <div class="service-card bg-white rounded-2xl overflow-hidden shadow-md">

                    <img
                        src="https://static.wixstatic.com/media/3eca91_304d4ca09b5b4253ab165b55bd769b29~mv2.jpg/v1/fill/w_2048%2Ch_2048%2Cal_c%2Cq_90%2Cenc_auto/3eca91_304d4ca09b5b4253ab165b55bd769b29~mv2.jpg"
                        alt="Professional makeup application"
                        class="w-full h-64 object-cover"
                        loading="lazy"
                    >

                    <div class="p-7">

                        <h3 class="font-serif text-2xl text-violet-dark mb-3">
                            Professional Makeup
                        </h3>

                        <p class="text-gray-600 leading-7 mb-6">
                            Get a beautiful makeup look for special
                            occasions, celebrations and everyday moments.
                        </p>

                        <a href="auth/register.php"
                           class="text-burgundy font-bold hover:text-violet-dark">
                            Book Service →
                        </a>

                    </div>
                </div>


                <!-- NAIL CARE -->
                <div class="service-card bg-white rounded-2xl overflow-hidden shadow-md">

                    <img
                        src="https://images.unsplash.com/photo-1632345031435-8727f6897d53?auto=format&fit=crop&w=1000&q=85"
                        alt="Professional nail care"
                        class="w-full h-64 object-cover"
                        loading="lazy"
                    >

                    <div class="p-7">

                        <h3 class="font-serif text-2xl text-violet-dark mb-3">
                            Nail Care
                        </h3>

                        <p class="text-gray-600 leading-7 mb-6">
                            Give your hands and nails the attention they
                            deserve with our nail care services.
                        </p>

                        <a href="auth/register.php"
                           class="text-burgundy font-bold hover:text-violet-dark">
                            Book Service →
                        </a>

                    </div>
                </div>

            </div>

        </div>

    </section>


    <!-- CALL TO ACTION -->
    <section class="bg-violet-dark py-20">

        <div class="max-w-4xl mx-auto px-6 text-center">

            <h2 class="font-serif text-4xl md:text-5xl text-white mb-6">
                Ready for Your Next Beauty Appointment?
            </h2>

            <p class="text-purple-100 text-lg mb-9">
                Create an account and book your appointment with Grace
                Beauty Salon today.
            </p>

            <a href="auth/register.php"
               class="inline-block bg-burgundy hover:bg-white hover:text-burgundy text-white px-9 py-4 rounded-full font-semibold transition">
                Create Your Account
            </a>

        </div>

    </section>


    <!-- CONTACT SECTION -->
    <section id="contact" class="py-20 bg-white">

        <div class="max-w-7xl mx-auto px-6 text-center">

            <p class="text-burgundy uppercase tracking-[3px] text-sm font-bold mb-4">
                Get In Touch
            </p>

            <h2 class="font-serif text-4xl text-violet-dark mb-6">
                We'd Love to Hear From You
            </h2>

            <p class="text-gray-600 max-w-xl mx-auto mb-8">
                Have a question about our services or appointments?
                Create an account to get started with Grace Beauty Salon.
            </p>

            <a href="auth/register.php"
               class="inline-block bg-violet-dark text-white px-8 py-3 rounded-full hover:bg-burgundy transition">
                Get Started
            </a>

        </div>

    </section>


    <!-- FOOTER -->
    <footer class="bg-charcoal text-white py-10">

        <div class="max-w-7xl mx-auto px-6">

            <div class="flex flex-col md:flex-row justify-between items-center gap-6">

                <div>
                    <h2 class="font-serif text-2xl font-bold">
                        Grace <span class="text-purple-300">Beauty</span>
                    </h2>

                    <p class="text-gray-400 text-sm mt-2">
                        Beauty, style and confidence.
                    </p>
                </div>

                <div class="flex gap-6 text-sm text-gray-300">
                    <a href="#home" class="hover:text-purple-300">Home</a>
                    <a href="#about" class="hover:text-purple-300">About</a>
                    <a href="#services" class="hover:text-purple-300">Services</a>
                    <a href="#contact" class="hover:text-purple-300">Contact</a>
                </div>

            </div>

            <div class="border-t border-gray-700 mt-8 pt-6 text-center text-gray-400 text-sm">

                &copy; <?php echo date('Y'); ?> Grace Beauty Salon.
                All rights reserved.

            </div>

        </div>

    </footer>


    <!-- MOBILE MENU JAVASCRIPT -->
    <script>
        const menuButton = document.getElementById('menuButton');
        const mobileMenu = document.getElementById('mobileMenu');

        menuButton.addEventListener('click', function () {
            mobileMenu.classList.toggle('hidden');
        });

        // Close mobile menu after selecting a link
        mobileMenu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                mobileMenu.classList.add('hidden');
            });
        });
    </script>

</body>
</html>