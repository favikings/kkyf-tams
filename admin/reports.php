<?php
// admin/reports.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

checkAuth('Super Admin');

require_once '../includes/header.php';
?>

<div class="px-4 py-8 max-w-5xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900">Reports & Analytics</h1>
        <p class="text-slate-500">Generate formal documents and view long-term fellowship growth.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

        <!-- Annual Report Card -->
        <div
            class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 flex flex-col h-full group hover:shadow-xl hover:shadow-indigo-500/10 transition-all duration-300 transform hover:-translate-y-1">
            <div
                class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mb-6 ring-8 ring-indigo-50/50 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 22h14a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v4" />
                    <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                    <path d="M5 12h14" />
                    <path d="M5 16h14" />
                    <path d="M5 20h14" />
                </svg>
            </div>

            <h2 class="text-xl font-bold text-slate-900 mb-2">Annual Performance Report</h2>
            <p class="text-slate-500 text-sm mb-8 flex-1">
                A comprehensive summary of all 10 Tents for the entire year. Includes total attendance, first-timer
                counts, and month-over-month growth comparisons.
            </p>

            <form action="<?= BASE_PATH ?>/api/generate_report.php" method="GET" class="space-y-4">
                <input type="hidden" name="type" value="annual">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Select
                        Year</label>
                    <select name="year"
                        class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all appearance-none cursor-pointer">
                        <?php
                        $startYear = 2025;
                        $currentYear = date('Y');
                        for ($y = $currentYear; $y >= $startYear; $y--) {
                            echo "<option value=\"$y\">$y Fellowship Year</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit"
                    class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3.5 rounded-xl active:scale-95 transition-all flex items-center justify-center gap-2 group">
                    Generate Annual PDF
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 group-hover:translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                </button>
            </form>
        </div>


        <!-- Weekly Report Card -->
        <div
            class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 flex flex-col h-full group hover:shadow-xl hover:shadow-purple-500/10 transition-all duration-300 transform hover:-translate-y-1">
            <div
                class="w-14 h-14 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center mb-6 ring-8 ring-purple-50/50 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">Weekly Performance Report</h2>
            <p class="text-slate-500 text-sm mb-8 flex-1">
                A snapshot of all Tents for a specific Sunday. Compare performance across the fellowship for any given
                service.
            </p>
            <form action="<?= BASE_PATH ?>/api/generate_report.php" method="GET" class="space-y-4">
                <input type="hidden" name="type" value="weekly">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Select
                        Sunday</label>
                    <input type="date" name="date" required
                        class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all cursor-pointer">
                </div>
                <button type="submit"
                    class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3.5 rounded-xl active:scale-95 transition-all flex items-center justify-center gap-2 group">
                    Generate Weekly PDF
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 group-hover:translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                </button>
            </form>
        </div>

        <!-- Monthly Summary Card -->
        <div
            class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 flex flex-col h-full group hover:shadow-xl hover:shadow-[#00BD06]/10 transition-all duration-300 transform hover:-translate-y-1">
            <div
                class="w-14 h-14 bg-green-50 text-[#00BD06] rounded-2xl flex items-center justify-center mb-6 ring-8 ring-green-50/50 group-hover:bg-[#00BD06] group-hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2" />
                    <line x1="16" y1="2" x2="16" y2="6" />
                    <line x1="8" y1="2" x2="8" y2="6" />
                    <line x1="3" y1="10" x2="21" y2="10" />
                </svg>
            </div>

            <h2 class="text-xl font-bold text-slate-900 mb-2">Monthly Detail Summary</h2>
            <p class="text-slate-500 text-sm mb-8 flex-1">
                Detailed breakdown for a specific month. Shows weekly Sunday attendance figures for each fellowship
                center and highlights top-performing tents.
            </p>

            <form action="<?= BASE_PATH ?>/api/generate_report.php" method="GET" class="grid grid-cols-2 gap-4">
                <input type="hidden" name="type" value="monthly">
                <div class="col-span-1">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Month</label>
                    <select name="month"
                        class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#00BD06] transition-all appearance-none cursor-pointer text-sm">
                        <?php
                        for ($m = 1; $m <= 12; $m++) {
                            $monthName = date('F', mktime(0, 0, 0, $m, 1));
                            $selected = ($m == date('n')) ? 'selected' : '';
                            echo "<option value=\"$m\" $selected>$monthName</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Year</label>
                    <select name="year"
                        class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#00BD06] transition-all appearance-none cursor-pointer text-sm">
                        <?php
                        for ($y = $currentYear; $y >= $startYear; $y--) {
                            echo "<option value=\"$y\">$y</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit"
                    class="col-span-2 bg-[#00BD06] hover:bg-[#009605] text-white font-bold py-3.5 rounded-xl shadow-lg shadow-green-500/20 active:scale-95 transition-all flex items-center justify-center gap-2 group">
                    Download Monthly PDF
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 group-hover:translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                </button>
            </form>
        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>