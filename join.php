<?php
// join.php
require_once 'includes/db_connect.php';
// Public page, no auth check
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title>Join a Tent | KKYF</title>
    <meta name="theme-color" content="#00BD06">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500&display=swap"
        rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#00bd06",
                        "background-light": "#f6f8fc",
                        "deep-slate": "#1E293B",
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "sans-serif"],
                        "sans": ["Inter", "sans-serif"]
                    }
                }
            }
        }
    </script>
    <style>
        .organic-shape {
            border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
        }

        /* Hide scrollbar for clean mobile UI */
        ::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>

<body
    class="bg-background-light font-sans text-slate-800 antialiased min-h-screen flex flex-col relative overflow-x-hidden">

    <!-- Organic Background Elements (Stitch UI) -->
    <div class="fixed top-0 left-0 w-full h-64 bg-deep-slate z-0 overflow-hidden rounded-b-[40px] shadow-lg">
        <div class="organic-shape absolute -top-20 -left-10 h-64 w-64 bg-primary/20 blur-2xl"></div>
        <div class="organic-shape absolute top-10 -right-20 h-48 w-48 bg-primary/30 blur-2xl"></div>

        <div class="pt-12 px-6 relative z-10">
            <h1 class="font-display text-4xl font-black text-white tracking-tight">Join the Family</h1>
            <p class="text-slate-300 font-medium mt-1">Register for your KKYF Tent</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 flex-1 px-4 pt-44 pb-12 sm:max-w-md md:max-w-lg mx-auto w-full">

        <!-- Registration Card -->
        <div class="bg-white rounded-[24px] shadow-sm border border-slate-100 p-6 sm:p-8">
            <form id="joinForm" class="space-y-5">

                <!-- Full Name -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5 pl-1">Full
                        Name</label>
                    <input type="text" name="full_name" required placeholder="John Doe"
                        class="w-full px-4 py-3.5 bg-slate-50 border-transparent focus:bg-white focus:ring-2 focus:ring-primary rounded-xl transition-all font-semibold text-slate-900 placeholder-slate-400">
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5 pl-1">Phone
                        Number</label>
                    <input type="tel" name="phone" required placeholder="0800 000 0000"
                        class="w-full px-4 py-3.5 bg-slate-50 border-transparent focus:bg-white focus:ring-2 focus:ring-primary rounded-xl transition-all font-semibold text-slate-900 placeholder-slate-400">
                </div>

                <!-- Birthday (Month/Day) -->
                <div>
                    <label
                        class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5 pl-1">Birthday</label>
                    <div class="grid grid-cols-2 gap-3">
                        <select id="dob_month"
                            class="w-full px-4 py-3.5 bg-slate-50 border-transparent focus:bg-white focus:ring-2 focus:ring-primary rounded-xl transition-all font-semibold text-slate-900"
                            required>
                            <option value="">Month</option>
                            <?php
                            for ($m = 1; $m <= 12; $m++) {
                                $monthName = date('F', mktime(0, 0, 0, $m, 1));
                                echo "<option value='" . str_pad($m, 2, "0", STR_PAD_LEFT) . "'>$monthName</option>";
                            }
                            ?>
                        </select>
                        <select id="dob_day"
                            class="w-full px-4 py-3.5 bg-slate-50 border-transparent focus:bg-white focus:ring-2 focus:ring-primary rounded-xl transition-all font-semibold text-slate-900"
                            required>
                            <option value="">Day</option>
                            <?php
                            for ($d = 1; $d <= 31; $d++) {
                                echo "<option value='" . str_pad($d, 2, "0", STR_PAD_LEFT) . "'>$d</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Status & School-->
                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2">
                        <label
                            class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5 pl-1">Status</label>
                        <select name="status" id="status"
                            class="w-full px-4 py-3.5 bg-slate-50 border-transparent focus:bg-white focus:ring-2 focus:ring-primary rounded-xl transition-all font-semibold text-slate-900">
                            <option value="Student">Student</option>
                            <option value="Worker">Worker</option>
                        </select>
                    </div>

                    <div id="school-group" class="col-span-2 transition-all duration-300">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5 pl-1">School
                            / Institution</label>
                        <input type="text" name="school" id="school" placeholder="E.g. University of..."
                            class="w-full px-4 py-3.5 bg-slate-50 border-transparent focus:bg-white focus:ring-2 focus:ring-primary rounded-xl transition-all font-semibold text-slate-900 placeholder-slate-400">
                    </div>
                </div>

                <!-- Tent Selection -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5 pl-1">Select
                        Tent</label>
                    <div class="relative">
                        <select name="tent_id" id="tent_id" required
                            class="w-full px-4 py-3.5 bg-primary/5 border border-primary/20 focus:bg-white focus:ring-2 focus:ring-primary rounded-xl transition-all font-bold text-primary appearance-none pr-10">
                            <option value="" disabled selected>Loading Tents...</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-primary">
                            <svg class="h-5 w-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="submitBtn"
                    class="w-full mt-2 bg-primary hover:bg-[#00a305] text-white py-4 rounded-[16px] font-bold text-lg shadow-lg shadow-primary/25 active:scale-[0.98] transition-all flex justify-center items-center gap-2">
                    <span>Register Now</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>
            </form>
        </div>

        <!-- Link to Public Attendance (If applicable) -->
        <div class="mt-8 text-center">
            <a href="checkin.php"
                class="inline-flex items-center gap-2 text-slate-500 hover:text-primary font-semibold transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                Already registered? Mark Attendance
            </a>
        </div>

    </div>

    <!-- Alert Toast (StitchAlert) -->
    <div id="stitch-alert"
        class="fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-96 transform transition-all duration-300 translate-y-[150%] z-50 rounded-xl shadow-xl flex items-center p-4">
        <div id="alert-icon" class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center mr-3"></div>
        <div class="flex-1">
            <h4 id="alert-title" class="font-bold text-sm"></h4>
            <p id="alert-message" class="text-xs mt-0.5 opacity-90"></p>
        </div>
        <button onclick="hideAlert()" class="opacity-70 hover:opacity-100 p-1"><svg class="w-4 h-4" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg></button>
    </div>

    <script>
        // --- 1. Fetch Tents ---
        async function loadTents() {
            try {
                const res = await fetch('api/get_tents.php');
                const data = await res.json();

                const select = document.getElementById('tent_id');
                if (data.success && data.data.length > 0) {
                    select.innerHTML = '<option value="" disabled selected>Select your Tent...</option>' +
                        data.data.map(t => `<option value="${t.Tent_ID}">${t.Tent_Name}</option>`).join('');
                } else {
                    select.innerHTML = '<option value="" disabled>No Tents Available</option>';
                }
            } catch (err) {
                console.error("Failed to load tents", err);
            }
        }
        loadTents();

        // --- 2. Conditional Logic: School Field ---
        const statusSelect = document.getElementById('status');
        const schoolGroup = document.getElementById('school-group');
        const schoolInput = document.getElementById('school');

        statusSelect.addEventListener('change', (e) => {
            if (e.target.value === 'Worker') {
                schoolGroup.style.display = 'none';
                schoolInput.value = '';
                schoolInput.required = false;
            } else {
                schoolGroup.style.display = 'block';
                schoolInput.required = true;
            }
        });

        // --- 3. Form Submission ---
        document.getElementById('joinForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerHTML;

            // Format Data
            const formData = new FormData(e.target);
            const jsonData = Object.fromEntries(formData.entries());

            // Format DOB
            const month = document.getElementById('dob_month').value;
            const day = document.getElementById('dob_day').value;
            // Use dummy year 2000 for storage since we only care about month/day
            jsonData.dob = `2000-${month}-${day}`;

            // Loading state
            btn.innerHTML = `<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> <span>Processing...</span>`;
            btn.disabled = true;

            try {
                const res = await fetch('api/public_register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(jsonData)
                });
                const data = await res.json();

                if (data.success) {
                    showAlert('Success', data.message, 'success');
                    e.target.reset(); // Clear form
                    statusSelect.dispatchEvent(new Event('change')); // Reset school logic
                } else {
                    showAlert('Registration Failed', data.message || data.error, data.error === 'DUPLICATE' ? 'warning' : 'error');
                }
            } catch (err) {
                showAlert('Error', 'Connection failed. Please try again.', 'error');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });

        // --- 4. StitchAlert ---
        function showAlert(title, message, type = 'success') {
            const alert = document.getElementById('stitch-alert');
            const icon = document.getElementById('alert-icon');
            const titleEl = document.getElementById('alert-title');
            const msgEl = document.getElementById('alert-message');

            titleEl.textContent = title;
            msgEl.textContent = message;

            // Reset classes
            alert.className = 'fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-96 transform transition-all duration-300 z-50 rounded-[16px] shadow-xl flex items-center p-4 border';

            if (type === 'success') {
                alert.classList.add('bg-green-50', 'text-green-900', 'border-green-200');
                icon.className = 'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center mr-3 bg-green-100 text-green-600';
                icon.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
            } else if (type === 'warning') {
                alert.classList.add('bg-orange-50', 'text-orange-900', 'border-orange-200');
                icon.className = 'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center mr-3 bg-orange-100 text-orange-600';
                icon.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
            } else {
                alert.classList.add('bg-red-50', 'text-red-900', 'border-red-200');
                icon.className = 'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center mr-3 bg-red-100 text-red-600';
                icon.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
            }

            // Animate In
            requestAnimationFrame(() => {
                alert.classList.remove('translate-y-[150%]');
                alert.classList.add('translate-y-0');
            });

            // Auto Close
            setTimeout(() => hideAlert(), 4000);
        }

        function hideAlert() {
            const alert = document.getElementById('stitch-alert');
            alert.classList.remove('translate-y-0');
            alert.classList.add('translate-y-[150%]');
        }
    </script>
</body>

</html>