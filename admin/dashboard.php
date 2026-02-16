<?php
// admin/dashboard.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

// Enforce Role
checkAuth('Super Admin');

// --- Backend Logic ---

// 1. Total Members
$stmtTotal = $pdo->query("SELECT COUNT(*) FROM Members");
$totalMembers = $stmtTotal->fetchColumn();

// 2. New Souls (First Timers in Last 7 Days)
$stmtNewSouls = $pdo->query("
    SELECT COUNT(*) 
    FROM Attendance_Log 
    WHERE Is_First_Timer = 1 
    AND Attendance_Date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
");
$newSoulsCount = $stmtNewSouls->fetchColumn();

// 3. Attendance by Tent for current active session
// Note: We need to know the active session ID, but for now we'll just count all logs or use a subquery
// Assuming there is at least one active session
$stmtStats = $pdo->query("
    SELECT t.Tent_ID, t.Tent_Name, COUNT(al.Log_ID) as count 
    FROM Tents t
    LEFT JOIN Attendance_Log al ON t.Tent_ID = al.Tent_ID
    GROUP BY t.Tent_ID, t.Tent_Name
    ORDER BY count DESC
");
$attendanceStats = $stmtStats->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="px-4 py-6">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-800">Super Admin Dashboard</h1>
        <p class="text-slate-500">Overview of the entire fellowship system.</p>
    </div>

    <?php if ($totalMembers > 0): ?>
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Members Card -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-blue-600" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h3 class="text-slate-500 text-sm font-bold uppercase tracking-wide">Total Members</h3>
                    <p class="text-3xl font-bold text-slate-900 mt-1"><?= number_format($totalMembers) ?></p>
                </div>
            </div>

            <!-- New Souls Card -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden group">
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
                    <h3 class="text-slate-500 text-sm font-bold uppercase tracking-wide">New Souls</h3>
                    <div class="flex items-baseline gap-2 mt-1">
                        <p class="text-3xl font-bold text-slate-900"><?= number_format($newSoulsCount) ?></p>
                        <span class="text-xs text-slate-400 font-medium">Last 7 Days</span>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden">
                <div class="w-12 h-12 bg-green-50 text-[#00BD06] rounded-lg flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                </div>
                <h3 class="text-slate-500 text-sm font-bold uppercase tracking-wide">System Status</h3>
                <p class="text-lg font-semibold text-[#00BD06] mt-2 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#00BD06] animate-pulse"></span>
                    Operational
                </p>
            </div>
        </div>

        <!-- Attendance Breakdown -->
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h2 class="text-lg font-bold text-slate-800">Attendance by Tent</h2>
                <button class="text-sm text-[#00BD06] hover:underline font-medium">View Full Report</button>
            </div>
            <div class="divide-y divide-slate-50">
                <?php if (count($attendanceStats) > 0): ?>
                    <?php foreach ($attendanceStats as $stat): ?>
                        <div class="px-6 py-4 flex justify-between items-center hover:bg-slate-50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-xs font-bold">
                                    <?= substr($stat['Tent_Name'], 0, 1) ?>
                                </div>
                                <a href="<?= BASE_PATH ?>/tent/dashboard.php?tent_id=<?= $stat['Tent_ID'] ?>"
                                    class="font-medium text-slate-700 hover:text-indigo-600 hover:underline transition-colors">
                                    <?= htmlspecialchars($stat['Tent_Name']) ?>
                                </a>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-right">
                                    <span class="block text-sm font-bold text-slate-900"><?= number_format($stat['count']) ?></span>
                                    <span class="block text-[10px] text-slate-400">Total Logs</span>
                                </div>
                                <div class="w-24 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                    <!-- Simple visual bar relative to max (mock math for now) -->
                                    <div class="bg-[#00BD06] h-full rounded-full"
                                        style="width: <?= min(100, ($stat['count'] / max(1, $totalMembers)) * 100) ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-8 text-center text-slate-400">
                        No attendance data found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="max-w-md mx-auto mt-20 text-center">
            <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-[#00BD06]" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 mb-2">Welcome to KKYF TAMS</h2>
            <p class="text-slate-500 mb-8">It looks like the system is fresh. Start by adding your first fellowship members
                to the Master Roster.</p>

            <a href="<?= BASE_PATH ?>/admin/roster.php"
                class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-[#00BD06] hover:bg-[#009605] shadow-lg shadow-green-500/30 transition-all hover:-translate-y-0.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add First Member
            </a>
        </div>
    <?php endif; ?>

</div>

</main>
</body>

</html>