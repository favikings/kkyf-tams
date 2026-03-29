<?php
// admin/birthdays.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

checkAuth('Super Admin');

$pageTitle = 'Birthdays';

require_once '../includes/header.php';

// Fetch all tents for filter dropdown
$stmtTents = $pdo->query("SELECT Tent_ID, Tent_Name FROM Tents ORDER BY Tent_Name ASC");
$tents = $stmtTents->fetchAll(PDO::FETCH_ASSOC);

$tentId = isset($_GET['tent_id']) ? $_GET['tent_id'] : 'all';
?>

<style>
    .month-badge.active {
        background-color: #00BD06;
        color: white;
    }
</style>

<div class="px-4 py-8 max-w-4xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">Birthdays</h1>
        <p class="text-slate-500 text-sm">View birthdays across all tents</p>
    </div>

    <!-- Tent Filter -->
    <div class="mb-4">
        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Filter by Tent</label>
        <select id="tentFilter" onchange="updateTent()" class="w-full max-w-xs p-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#00BD06]">
            <option value="all" <?= $tentId === 'all' ? 'selected' : '' ?>>All Tents</option>
            <?php foreach ($tents as $tent): ?>
                <option value="<?= $tent['Tent_ID'] ?>" <?= $tentId == $tent['Tent_ID'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tent['Tent_Name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Month Filter -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button onclick="filterByMonth(0)" id="btn-upcoming" class="month-badge px-3 py-1.5 rounded-lg text-sm font-medium bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
            Upcoming
        </button>
        <button onclick="filterByMonth(1)" id="btn-1" class="month-badge px-3 py-1.5 rounded-lg text-sm font-medium bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
            Jan
        </button>
        <button onclick="filterByMonth(2)" id="btn-2" class="month-badge px-3 py-1.5 rounded-lg text-sm font-medium bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
            Feb
        </button>
        <button onclick="filterByMonth(3)" id="btn-3" class="month-badge px-3 py-1.5 rounded-lg text-sm font-medium bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
            Mar
        </button>
        <button onclick="filterByMonth(4)" id="btn-4" class="month-badge px-3 py-1.5 rounded-lg text-sm font-medium bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
            Apr
        </button>
        <button onclick="filterByMonth(5)" id="btn-5" class="month-badge px-3 py-1.5 rounded-lg text-sm font-medium bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
            May
        </button>
        <button onclick="filterByMonth(6)" id="btn-6" class="month-badge px-3 py-1.5 rounded-lg text-sm font-medium bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
            Jun
        </button>
        <button onclick="filterByMonth(7)" id="btn-7" class="month-badge px-3 py-1.5 rounded-lg text-sm font-medium bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
            Jul
        </button>
        <button onclick="filterByMonth(8)" id="btn-8" class="month-badge px-3 py-1.5 rounded-lg text-sm font-medium bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
            Aug
        </button>
        <button onclick="filterByMonth(9)" id="btn-9" class="month-badge px-3 py-1.5 rounded-lg text-sm font-medium bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
            Sep
        </button>
        <button onclick="filterByMonth(10)" id="btn-10" class="month-badge px-3 py-1.5 rounded-lg text-sm font-medium bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
            Oct
        </button>
        <button onclick="filterByMonth(11)" id="btn-11" class="month-badge px-3 py-1.5 rounded-lg text-sm font-medium bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
            Nov
        </button>
        <button onclick="filterByMonth(12)" id="btn-12" class="month-badge px-3 py-1.5 rounded-lg text-sm font-medium bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
            Dec
        </button>
    </div>

    <!-- Results Count -->
    <p id="resultCount" class="text-sm text-slate-500 mb-4">Loading...</p>

    <!-- Birthday List -->
    <div id="birthdayList" class="space-y-3">
        <div class="animate-pulse space-y-3">
            <div class="h-16 bg-slate-100 rounded-xl"></div>
            <div class="h-16 bg-slate-100 rounded-xl"></div>
            <div class="h-16 bg-slate-100 rounded-xl"></div>
        </div>
    </div>
</div>

<script>
    let currentMonth = 0;
    let currentTent = '<?= $tentId ?>';

    document.addEventListener('DOMContentLoaded', () => {
        filterByMonth(0);
    });

    function updateTent() {
        const select = document.getElementById('tentFilter');
        currentTent = select.value;
        filterByMonth(currentMonth);
    }

    async function filterByMonth(month) {
        currentMonth = month;
        
        // Update active state
        document.querySelectorAll('.month-badge').forEach(btn => btn.classList.remove('active', 'bg-[#00BD06]', 'text-white'));
        
        if (month === 0) {
            document.getElementById('btn-upcoming').classList.add('active', 'bg-[#00BD06]', 'text-white');
        } else {
            document.getElementById('btn-' + month).classList.add('active', 'bg-[#00BD06]', 'text-white');
        }

        const container = document.getElementById('birthdayList');
        const countEl = document.getElementById('resultCount');
        
        try {
            let url = '<?= BASE_PATH ?>/api/get_birthdays.php?mode=upcoming';
            if (month > 0) {
                url = '<?= BASE_PATH ?>/api/get_birthdays.php?month=' + month + '&mode=all';
            }
            
            if (currentTent !== 'all') {
                url += '&tent_id=' + currentTent;
            } else {
                url += '&tent_id=all';
            }
            
            const res = await fetch(url);
            const data = await res.json();

            if (data.success && data.data.length > 0) {
                countEl.textContent = data.data.length + ' birthday' + (data.data.length !== 1 ? 'ies' : '') + ' found';
                
                container.innerHTML = data.data.map(m => {
                    const isToday = m.is_today === true || m.days_until === 0;
                    const badgeClass = isToday ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600';
                    const badgeText = isToday ? 'Today!' : m.formatted_date;
                    
                    return `
                    <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center text-green-600 font-bold text-lg">
                                ${m.day_of_month || '-'}
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-800">${m.Full_Name}</h4>
                                <p class="text-xs text-slate-500">${m.Tent_Name || ''}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-1 rounded-full text-xs font-bold ${badgeClass}">
                                ${badgeText}
                            </span>
                            ${m.Phone ? `
                            <a href="tel:${m.Phone}" class="w-10 h-10 bg-green-50 text-[#00BD06] rounded-full flex items-center justify-center hover:bg-[#00BD06] hover:text-white transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                            </a>
                            ` : ''}
                        </div>
                    </div>
                `}).join('');
            } else {
                countEl.textContent = 'No birthdays found';
                container.innerHTML = '<div class="text-center py-12 text-slate-400">No birthdays found for this month</div>';
            }
        } catch (err) {
            countEl.textContent = 'Error loading birthdays';
            container.innerHTML = '<div class="text-center py-12 text-red-500">Error loading birthdays</div>';
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>
