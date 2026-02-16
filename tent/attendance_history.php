<?php
// tent/attendance_history.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

checkAuth('Tent Admin');

// Context Logic (Super Admin Impersonation)
$isSuperAdmin = ($_SESSION['role'] === 'Super Admin');
$tentId = $_SESSION['assigned_tent_id'] ?? null;

if ($isSuperAdmin && isset($_GET['tent_id'])) {
    $tentId = $_GET['tent_id'];
}

require_once '../includes/header.php';
?>
<style>
    @media print {
        body>*:not(#detailModal) {
            visibility: hidden;
        }

        #detailModal {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: white;
            visibility: visible;
            opacity: 1 !important;
            pointer-events: auto !important;
        }

        #detailModal .bg-white {
            width: 100%;
            max-width: none;
            box-shadow: none;
        }

        #detailModal .absolute {
            display: none;
            /* Hide Backdrop */
        }

        #printModalBtn {
            display: none;
        }
    }
</style>
<div class="px-4 py-8 max-w-4xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">Attendance History</h1>
        <p class="text-slate-500 text-sm">Weekly records of fellowship meetings.</p>
    </div>

    <!-- History List -->
    <div id="historyList" class="space-y-4">
        <!-- Loaded via JS -->
        <div class="animate-pulse space-y-4">
            <div class="h-20 bg-slate-100 rounded-xl"></div>
            <div class="h-20 bg-slate-100 rounded-xl"></div>
        </div>
    </div>

</div>

<!-- Detail Modal -->
<div id="detailModal"
    class="fixed inset-0 z-50 flex items-center justify-center pointer-events-none opacity-0 transition-opacity duration-300">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="bg-white w-full max-w-md rounded-2xl p-6 relative z-10 pointer-events-auto transform scale-95 transition-all"
        id="modalContent">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900" id="modalTitle">Sunday Details</h3>
                <p class="text-slate-500 text-xs" id="modalDate">Loading...</p>
            </div>
            <button onclick="closeModal()" class="p-2 bg-slate-100 rounded-full hover:bg-slate-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <div class="max-h-[60vh] overflow-y-auto custom-scrollbar" id="modalBody">
            <!-- Attendees Logic -->
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', loadHistory);

    async function loadHistory() {
        const container = document.getElementById('historyList');
        try {
            const tentId = '<?= $tentId ?>';
            const res = await fetch(`<?= BASE_PATH ?>/api/get_attendance_history.php?mode=summary&tent_id=${tentId}`);
            const data = await res.json();

            if (data.success && data.data.length > 0) {
                container.innerHTML = data.data.map(item => `
                <div onclick="openDetail('${item.Attendance_Date}')" class="bg-white p-5 rounded-xl border border-slate-100 shadow-sm hover:shadow-md hover:border-[#00BD06]/30 cursor-pointer transition-all flex items-center justify-between group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-lg flex flex-col items-center justify-center font-bold text-xs ring-1 ring-blue-100">
                            <span class="text-[10px] uppercase text-blue-400">FEB</span>
                            <span class="text-lg leading-none">${new Date(item.Attendance_Date).getDate()}</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800 group-hover:text-[#00BD06] transition-colors">Sunday Service</h4>
                            <p class="text-xs text-slate-500">${new Date(item.Attendance_Date).toDateString()}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-slate-900">${item.Total_Attendance}</p>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Attendees</p>
                    </div>
                </div>
            `).join('');
            } else {
                container.innerHTML = '<p class="text-center text-slate-400 py-10">No history found.</p>';
            }
        } catch (err) {
            console.error(err);
            container.innerHTML = '<p class="text-center text-red-500">Failed to load history.</p>';
        }
    }

    async function openDetail(date) {
        const modal = document.getElementById('detailModal');
        const content = document.getElementById('modalContent');
        const body = document.getElementById('modalBody');

        // Open Modal
        modal.classList.remove('pointer-events-none', 'opacity-0');
        content.classList.remove('scale-95');

        document.getElementById('modalTitle').textContent = 'Attendees List';
        document.getElementById('modalDate').textContent = new Date(date).toDateString();
        body.innerHTML = '<div class="flex justify-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#00BD06]"></div></div>';

        try {
            const res = await fetch(`<?= BASE_PATH ?>/api/get_attendance_history.php?mode=detail&date=${date}`);
            const data = await res.json();

            if (data.success) {
                body.innerHTML = `
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50 sticky top-0">
                        <tr>
                            <th class="px-2 py-2">Name</th>
                            <th class="px-2 py-2 text-right">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        ${data.data.map(m => `
                            <tr>
                                <td class="px-2 py-3 font-medium text-slate-800">${m.Full_Name}</td>
                                <td class="px-2 py-3 text-right text-slate-500 text-xs font-mono">
                                    ${m.Check_In_Time ? new Date(m.Check_In_Time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '-'}
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            }
        } catch (err) {
            body.innerHTML = '<p class="text-red-500 text-center">Error loading details.</p>';
        }
    }

    function closeModal() {
        const modal = document.getElementById('detailModal');
        const content = document.getElementById('modalContent');
        modal.classList.add('pointer-events-none', 'opacity-0');
        content.classList.add('scale-95');
    }
</script>

<?php require_once '../includes/footer.php'; ?>