<?php
// checkin.php
require_once 'includes/db_connect.php';
// Public page, no auth check
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title>Check In | KKYF</title>
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
            <h1 class="font-display text-4xl font-black text-white tracking-tight">Welcome Home</h1>
            <p class="text-slate-300 font-medium mt-1">Mark your attendance for service.</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 flex-1 px-4 pt-44 pb-12 sm:max-w-md md:max-w-lg mx-auto w-full">

        <!-- Check-in Card -->
        <div
            class="bg-white rounded-[24px] shadow-sm border border-slate-100 p-6 sm:p-8 relative overflow-hidden min-h-[250px]">

            <!-- STEP 1: Identify -->
            <div id="step-1"
                class="transition-all duration-500 absolute inset-0 p-6 sm:p-8 flex flex-col justify-center bg-white">
                <form id="identifyForm" class="space-y-4 w-full">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5 pl-1">Name
                            or Phone Number</label>
                        <input type="text" id="identifier" required placeholder="John Doe or 0800..."
                            class="w-full px-4 py-3.5 bg-slate-50 border-transparent focus:bg-white focus:ring-2 focus:ring-primary rounded-xl transition-all font-semibold text-slate-900 placeholder-slate-400">
                    </div>
                    <button type="submit" id="findBtn"
                        class="w-full bg-slate-900 hover:bg-slate-800 text-white py-4 rounded-[16px] font-bold text-lg shadow-md active:scale-[0.98] transition-all flex justify-center items-center gap-2">
                        <span>Find Me</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- STEP 2: Confirm -->
            <div id="step-2"
                class="transition-all duration-500 absolute inset-0 p-6 sm:p-8 flex flex-col justify-center bg-white translate-x-full">
                <div class="text-center w-full">
                    <div
                        class="mx-auto w-16 h-16 bg-green-50 text-primary rounded-full flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <h3 class="text-slate-500 font-medium mb-1">Are you</h3>
                    <h2 id="confirm-name" class="font-display text-2xl font-bold text-slate-900">Name Here</h2>
                    <p class="text-sm font-medium text-slate-500 mt-1">from <span id="confirm-tent"
                            class="font-bold text-slate-700">Tent Name</span>?</p>

                    <div class="mt-6 flex flex-col space-y-3">
                        <button id="confirmBtn"
                            class="w-full bg-primary hover:bg-[#00a305] text-white py-4 rounded-[16px] font-bold text-lg shadow-lg shadow-primary/25 active:scale-[0.98] transition-all flex justify-center items-center gap-2">
                            <span>Yes, Mark Me Present</span>
                        </button>
                        <button id="cancelBtn"
                            class="w-full bg-white border border-slate-200 text-slate-500 py-3 rounded-[16px] font-bold active:scale-[0.98] transition-all hover:bg-slate-50">
                            No, Try Again
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <!-- Link to Join.php -->
        <div class="mt-8 text-center">
            <a href="join.php"
                class="inline-flex items-center gap-2 text-slate-500 hover:text-primary font-semibold transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
                Not registered? Join here.
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
        let currentMember = null;

        const step1 = document.getElementById('step-1');
        const step2 = document.getElementById('step-2');

        // Form Elements
        const identifyForm = document.getElementById('identifyForm');
        const identifierInput = document.getElementById('identifier');
        const findBtn = document.getElementById('findBtn');
        const confirmBtn = document.getElementById('confirmBtn');
        const cancelBtn = document.getElementById('cancelBtn');

        // Step 1: Identify Member
        identifyForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const identifier = identifierInput.value.trim();
            if (!identifier) return;

            const originalText = findBtn.innerHTML;
            findBtn.innerHTML = `<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> <span>Searching...</span>`;
            findBtn.disabled = true;

            try {
                const res = await fetch('api/public_verify_member.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ identifier })
                });
                const data = await res.json();

                if (data.success) {
                    currentMember = data.member;
                    showStep2(currentMember);
                } else {
                    showAlert('Member Not Found', data.message || 'Check spelling or try registering.', 'error');
                }
            } catch (err) {
                showAlert('Connection Error', 'Please try again.', 'error');
            } finally {
                findBtn.innerHTML = originalText;
                findBtn.disabled = false;
            }
        });

        // Step 2 functions
        function showStep2(member) {
            document.getElementById('confirm-name').textContent = member.Full_Name;
            document.getElementById('confirm-tent').textContent = member.Tent_Name || 'Unknown Tent';

            step1.classList.add('-translate-x-full');
            step2.classList.remove('translate-x-full');
            step2.classList.add('translate-x-0');
        }

        function cancelStep2() {
            currentMember = null;
            identifierInput.value = '';

            step1.classList.remove('-translate-x-full');
            step2.classList.remove('translate-x-0');
            step2.classList.add('translate-x-full');

            identifierInput.focus();
        }

        cancelBtn.addEventListener('click', cancelStep2);

        // Step 2 Submit: Mark Attendance
        confirmBtn.addEventListener('click', async () => {
            if (!currentMember) return;

            const originalText = confirmBtn.innerHTML;
            confirmBtn.innerHTML = `<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`;
            confirmBtn.disabled = true;

            try {
                const res = await fetch('api/public_mark_attendance.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        member_uuid: currentMember.Member_UUID,
                        tent_id: currentMember.Current_Tent_ID
                    })
                });
                const data = await res.json();

                if (data.success) {
                    showAlert('Success!', data.message, 'success');
                    setTimeout(cancelStep2, 2500); // Reset after 2.5s
                } else {
                    showAlert('Notice', data.message || data.error, data.error === 'ALREADY_CHECKED_IN' ? 'warning' : 'error');
                    setTimeout(cancelStep2, 3500); // Reset for next person
                }
            } catch (err) {
                showAlert('Connection Error', 'Please try again.', 'error');
            } finally {
                confirmBtn.innerHTML = originalText;
                confirmBtn.disabled = false;
            }
        });

        // --- StitchAlert Logic ---
        function showAlert(title, message, type = 'success') {
            const alert = document.getElementById('stitch-alert');
            const icon = document.getElementById('alert-icon');
            const titleEl = document.getElementById('alert-title');
            const msgEl = document.getElementById('alert-message');

            titleEl.textContent = title;
            msgEl.textContent = message;

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

            requestAnimationFrame(() => {
                alert.classList.remove('translate-y-[150%]');
                alert.classList.add('translate-y-0');
            });

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