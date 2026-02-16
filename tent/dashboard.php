<?php
// tent/dashboard.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

// Role Check: Tent Admin required (Super Admin allowed via auth_check update)
checkAuth('Tent Admin');

// Context Logic
$isSuperAdmin = ($_SESSION['role'] === 'Super Admin');
$tentId = null;
$tentName = null;

if ($isSuperAdmin && isset($_GET['tent_id'])) {
    // Super Admin Impersonation Mode
    $tentId = $_GET['tent_id'];

    // Fetch Tent Name for Display
    $stmtName = $pdo->prepare("SELECT Tent_Name FROM Tents WHERE Tent_ID = ?");
    $stmtName->execute([$tentId]);
    $tentName = $stmtName->fetchColumn();

    if (!$tentName) {
        die("Invalid Tent ID"); // Or redirect
    }
} else {
    // Regular Tent Admin (or Super Admin viewing their own assigned tent, if any)
    $tentId = $_SESSION['assigned_tent_id'] ?? null;
    $tentName = $_SESSION['tent_name'] ?? 'Unknown Tent';

    if (!$tentId && $isSuperAdmin) {
        // Super Admin without explicit tensor param -> Redirect to Global Dashboard
        header("Location: " . BASE_PATH . "/admin/dashboard.php");
        exit;
    }
}

// Helper for Links
$linkSuffix = ($isSuperAdmin && $tentId) ? "?tent_id=$tentId" : "";

// --- Backend Logic ---

// 1. Scoped Member Count
$stmtMembers = $pdo->prepare("SELECT COUNT(*) FROM Members WHERE Current_Tent_ID = ?");
$stmtMembers->execute([$tentId]);
$memberCount = $stmtMembers->fetchColumn();

// 2. Scoped Attendance Count (Last Sunday)
// Determine Last Sunday
$today = new DateTime();
$lastSunday = clone $today;

// If today is Sunday, check if we have data for today
if ($today->format('w') == 0) {
    $checkDate = $today->format('Y-m-d');
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM Attendance_Log WHERE Tent_ID = ? AND Attendance_Date = ?");
    $stmtCheck->execute([$tentId, $checkDate]);
    if ($stmtCheck->fetchColumn() > 0) {
        $lastSunday = $today; // Use Today
    } else {
        $lastSunday->modify('last sunday'); // Use Previous Sunday
    }
} else {
    $lastSunday->modify('last sunday');
}
$lastSundayStr = $lastSunday->format('Y-m-d');

$stmtAtt = $pdo->prepare("SELECT COUNT(*) FROM Attendance_Log WHERE Tent_ID = ? AND Attendance_Date = ?");
$stmtAtt->execute([$tentId, $lastSundayStr]);
$attendanceCount = $stmtAtt->fetchColumn();

// 3. First Timers (Last 7 Days)
// Query Attendance Log for Is_First_Timer flag
$stmtFT = $pdo->prepare("
    SELECT COUNT(*) 
    FROM Attendance_Log 
    WHERE Tent_ID = ? 
    AND Is_First_Timer = 1 
    AND Attendance_Date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
");
$stmtFT->execute([$tentId]);
$firstTimerCount = $stmtFT->fetchColumn();

require_once '../includes/header.php';
?>

<div class="px-4 py-6">
    <div class="mb-6">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-slate-800">Tent Dashboard: <?= htmlspecialchars($tentName) ?></h1>
            <?php if ($isSuperAdmin): ?>
                <span
                    class="bg-indigo-100 text-indigo-700 text-xs px-2 py-1 rounded-full font-bold border border-indigo-200">
                    Super Admin View
                </span>
            <?php endif; ?>
        </div>
        <p class="text-slate-500">Overview for <?= $lastSunday->format('M j, Y') ?> (Last Sunday Service)</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">

        <!-- Members Card -->
        <a href="<?= BASE_PATH ?>/tent/members.php<?= $linkSuffix ?>"
            class="block bg-white p-6 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
            <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-[#00BD06]" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div class="relative z-10">
                <div class="w-12 h-12 bg-green-50 text-[#00BD06] rounded-lg flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <h3 class="text-slate-500 text-sm font-bold uppercase tracking-wide">Total Members</h3>
                <p class="text-3xl font-bold text-slate-900 mt-1"><?= number_format($memberCount) ?></p>
            </div>
        </a>

        <!-- Attendance Card -->
        <a href="<?= BASE_PATH ?>/tent/attendance_history.php<?= $linkSuffix ?>"
            class="block bg-white p-6 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
            <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-blue-600" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 11l3 3L22 4"></path>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
            </div>
            <div class="relative z-10">
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 11l3 3L22 4"></path>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                </div>
                <h3 class="text-slate-500 text-sm font-bold uppercase tracking-wide">Attendance (Last Sun)</h3>
                <p class="text-3xl font-bold text-slate-900 mt-1"><?= number_format($attendanceCount) ?></p>
                <p class="text-xs text-slate-400 mt-1">
                    <?= number_format(($memberCount > 0 ? ($attendanceCount / $memberCount) * 100 : 0), 1) ?>% Turnout
                </p>
            </div>
        </a>

        <!-- First Timers Card -->
        <a href="<?= BASE_PATH ?>/tent/first_timers.php<?= $linkSuffix ?>"
            class="block bg-white p-6 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
            <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-purple-600" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <polyline points="17 11 19 13 23 9"></polyline>
                </svg>
            </div>
            <div class="relative z-10">
                <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-lg flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <polyline points="17 11 19 13 23 9"></polyline>
                    </svg>
                </div>
                <h3 class="text-slate-500 text-sm font-bold uppercase tracking-wide">First Timers</h3>
                <div class="flex items-baseline gap-2 mt-1">
                    <p class="text-3xl font-bold text-slate-900"><?= number_format($firstTimerCount) ?></p>
                    <span class="text-xs text-slate-400 font-medium">Last 7 Days</span>
                </div>
            </div>
        </a>

        <!-- Birthdays Card -->
        <div
            class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden md:col-span-2 lg:col-span-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-slate-800 font-bold text-lg flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-pink-500" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M20 21v-8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8" />
                        <path d="M4 16s.5-1 2-1 2.5 2 4 2 2.5-2 4-2 2.5 2 4 2 2-1 2-1" />
                        <path d="M2 21h20" />
                        <path d="M7 8v2" />
                        <path d="M12 8v2" />
                        <path d="M17 8v2" />
                        <path d="M7 4h.01" />
                        <path d="M12 4h.01" />
                        <path d="M17 4h.01" />
                    </svg>
                    Birthdays
                </h3>
                <!-- Segmented Control Style Toggle -->
                <div class="bg-slate-100 p-1 rounded-lg flex text-xs font-bold shadow-inner">
                    <button id="btn-week" onclick="setBirthdayFilter('week')"
                        class="px-3 py-1.5 rounded-md bg-white text-slate-900 shadow-sm transition-all focus:outline-none">Week</button>
                    <button id="btn-month" onclick="setBirthdayFilter('month')"
                        class="px-3 py-1.5 rounded-md text-slate-500 hover:text-slate-700 transition-all focus:outline-none">Month</button>
                </div>
            </div>

            <div id="birthdayList" class="space-y-3 max-h-64 overflow-y-auto custom-scrollbar pr-1">
                <!-- Populated via JS -->
                <div class="animate-pulse space-y-3">
                    <div class="h-14 bg-slate-50 rounded-xl w-full"></div>
                    <div class="h-14 bg-slate-50 rounded-xl w-full"></div>
                </div>
            </div>
            <div id="noBirthdaysMsg" class="hidden flex flex-col items-center justify-center py-8 text-center">
                <div class="h-12 w-12 bg-slate-50 rounded-full flex items-center justify-center mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-300" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M8 2v4" />
                        <path d="M16 2v4" />
                        <rect width="18" height="18" x="3" y="4" rx="2" />
                        <path d="M3 10h18" />
                        <path d="M8 14h.01" />
                        <path d="M12 14h.01" />
                        <path d="M16 14h.01" />
                        <path d="M8 18h.01" />
                        <path d="M12 18h.01" />
                        <path d="M16 18h.01" />
                    </svg>
                </div>
                <p class="text-slate-400 text-sm font-medium">No Birthdays this week!</p>
            </div>
        </div>

    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 gap-4 pb-20"> <!-- Added pb-20 to ensure FAB doesn't cover -->
        <a href="<?= BASE_PATH ?>/tent/attendance.php?tent_id=<?= htmlspecialchars($tentId) ?>"
            class="bg-[#00BD06] text-white p-5 rounded-2xl shadow-lg shadow-green-500/20 active:scale-95 transition-all flex flex-col items-center justify-center gap-3 text-center group h-32 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-2 opacity-10">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div class="bg-white/20 p-3 rounded-full group-hover:bg-white/30 transition-colors z-10 backdrop-blur-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <span class="font-bold text-sm z-10">Start Attendance</span>
        </a>
        <a href="<?= BASE_PATH ?>/tent/members.php<?= $linkSuffix ?>"
            class="bg-white border border-slate-200 text-slate-700 p-5 rounded-2xl shadow-sm active:scale-95 transition-all flex flex-col items-center justify-center gap-3 text-center group h-32 hover:border-[#00BD06] hover:text-[#00BD06] hover:bg-green-50/30">
            <div class="bg-slate-50 p-3 rounded-full group-hover:bg-[#00BD06]/10 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <span class="font-bold text-sm">My Members</span>
        </a>
    </div>

</div>

<script>
    let currentFilter = 'week';

    function setBirthdayFilter(filter) {
        currentFilter = filter;

        // Update UI Tabs
        const btnWeek = document.getElementById('btn-week');
        const btnMonth = document.getElementById('btn-month');

        if (filter === 'week') {
            btnWeek.className = "px-3 py-1.5 rounded-md bg-white text-slate-900 shadow-sm transition-all focus:outline-none";
            btnMonth.className = "px-3 py-1.5 rounded-md text-slate-500 hover:text-slate-700 transition-all focus:outline-none";
        } else {
            btnMonth.className = "px-3 py-1.5 rounded-md bg-white text-slate-900 shadow-sm transition-all focus:outline-none";
            btnWeek.className = "px-3 py-1.5 rounded-md text-slate-500 hover:text-slate-700 transition-all focus:outline-none";
        }

        fetchBirthdays();
    }

    async function fetchBirthdays() {
        const list = document.getElementById('birthdayList');
        const msg = document.getElementById('noBirthdaysMsg');

        // Loading State
        list.innerHTML = '<div class="animate-pulse space-y-3"><div class="h-14 bg-slate-50 rounded-xl w-full"></div><div class="h-14 bg-slate-50 rounded-xl w-full"></div></div>';
        msg.classList.add('hidden');

        try {
            // Check for tent_id in URL (Super Admin View)
            const urlParams = new URLSearchParams(window.location.search);
            const tentIdParam = urlParams.get('tent_id');
            const suffix = tentIdParam ? `&tent_id=${tentIdParam}` : '';

            const res = await fetch(`<?= BASE_PATH ?>/api/get_birthdays.php?filter=${currentFilter}${suffix}`);
            const data = await res.json();

            // Wait a tiny bit for perceived performance/loading animation smoothness
            // await new Promise(r => setTimeout(r, 300)); 

            if (data.data && data.data.length > 0) {
                list.innerHTML = data.data.map(m => {
                    // Logic: Logic for Urgent vs Normal Badge
                    // Parse Integers
                    const day = parseInt(m.days_until);
                    const isUrgent = day < 3;
                    const badgeClass = isUrgent ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600';
                    const badgeText = day === 0 ? 'Today!' : (day === 1 ? 'Tomorrow' : `In ${day} Days`);

                    // Initials
                    const initials = m.Full_Name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();

                    return `
                    <div class="flex items-center justify-between p-4 bg-white rounded-xl shadow-sm border border-slate-100 transition-transform hover:scale-[1.02] active:scale-[0.98]">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-pink-100 text-pink-600 flex items-center justify-center text-sm font-bold border-2 border-white ring-1 ring-pink-50">
                                ${initials}
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 text-sm leading-tight">${m.Full_Name}</h4>
                                <p class="text-xs text-slate-500 mt-0.5"><span class="font-bold text-slate-700">${m.formatted_date}</span></p>
                            </div>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wide px-2.5 py-1 rounded-full ${badgeClass}">
                                ${badgeText}
                            </span>
                        </div>
                    </div>
                `}).join('');
            } else {
                list.innerHTML = '';
                const emptyText = currentFilter === 'week' ? "No Birthdays this week!" : "No Birthdays this month!";
                msg.querySelector('p').textContent = emptyText;
                msg.classList.remove('hidden');
            }
        } catch (err) {
            console.error(err);
            list.innerHTML = '<p class="text-red-500 text-xs text-center">Error loading birthdays.</p>';
        }
    }

    // Load on start
    setBirthdayFilter('week');
</script>

<?php require_once '../includes/footer.php'; ?>