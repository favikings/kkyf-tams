<?php
// tent/first_timers.php
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

<div class="px-4 py-8 max-w-4xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">New Members (First Timers)</h1>
        <p class="text-slate-500 text-sm">Follow up with new souls added in the last 30 days.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="ftList">
        <!-- JS Populated -->
        <div class="col-span-full py-10 text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[#00BD06]"></div>
        </div>
    </div>
</div>

</div>

<!-- Detail Modal -->
<div id="memberModal"
    class="fixed inset-0 z-50 flex items-center justify-center pointer-events-none opacity-0 transition-opacity duration-300">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="bg-white w-full max-w-xs sm:max-w-sm rounded-2xl p-6 relative z-10 pointer-events-auto transform scale-95 transition-all text-center"
        id="modalContent">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold text-green-600"
            id="mInitials">
            --
        </div>
        <h3 class="text-xl font-bold text-slate-900" id="mName">Member Name</h3>
        <p class="text-slate-500 text-sm mb-4" id="mStatus">Status</p>

        <div class="space-y-3 text-left bg-slate-50 p-4 rounded-xl text-sm">
            <div class="flex justify-between">
                <span class="text-slate-400">Phone</span>
                <span class="font-bold text-slate-700 font-mono" id="mPhone">--</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Joined</span>
                <span class="font-bold text-slate-700" id="mJoined">--</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">First Time</span>
                <span class="font-bold text-slate-700" id="mDate">--</span>
            </div>
        </div>

        <button onclick="closeModal()"
            class="mt-6 w-full py-3 rounded-xl bg-slate-100 text-slate-600 font-bold hover:bg-slate-200 transition-colors">
            Close
        </button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', loadFirstTimers);

    function openMemberDetail(mEncoded) {
        const m = JSON.parse(decodeURIComponent(mEncoded));
        const modal = document.getElementById('memberModal');
        const content = document.getElementById('modalContent');

        document.getElementById('mName').textContent = m.Full_Name;
        document.getElementById('mStatus').textContent = m.Status;
        document.getElementById('mPhone').textContent = m.Phone || 'N/A';
        document.getElementById('mJoined').textContent = new Date(m.Attendance_Date).toLocaleDateString();
        document.getElementById('mDate').textContent = new Date(m.Attendance_Date).toDateString();

        const initials = m.Full_Name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        document.getElementById('mInitials').textContent = initials;

        modal.classList.remove('pointer-events-none', 'opacity-0');
        content.classList.remove('scale-95');
    }

    function closeModal() {
        const modal = document.getElementById('memberModal');
        const content = document.getElementById('modalContent');
        modal.classList.add('pointer-events-none', 'opacity-0');
        content.classList.add('scale-95');
    }

    async function loadFirstTimers() {
        const container = document.getElementById('ftList');
        try {
            const res = await fetch('<?= BASE_PATH ?>/api/get_first_timers.php?filter=recent');
            const data = await res.json();

            if (data.success && data.data.length > 0) {
                container.innerHTML = data.data.map(m => `
                <div onclick='openMemberDetail("${encodeURIComponent(JSON.stringify(m))}")' 
                     class="bg-white p-5 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between cursor-pointer hover:shadow-md hover:border-[#00BD06]/50 transition-all group">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <h4 class="font-bold text-slate-800 group-hover:text-[#00BD06] transition-colors">${m.Full_Name}</h4>
                            <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">New</span>
                        </div>
                        <p class="text-xs text-slate-500">Joined: ${new Date(m.Attendance_Date).toLocaleDateString()}</p>
                    </div>
                    <div class="flex gap-2" onclick="event.stopPropagation()">
                        ${m.Phone ? `
                        <a href="tel:${m.Phone}" class="w-10 h-10 bg-green-50 text-[#00BD06] rounded-full flex items-center justify-center hover:bg-[#00BD06] hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        </a>` : ''}
                    </div>
                </div>
            `).join('');
            } else {
                container.innerHTML = '<div class="col-span-full text-center py-10 text-slate-400">No new first timers recently.</div>';
            }
        } catch (err) {
            container.innerHTML = '<div class="col-span-full text-center text-red-500">Error loading data.</div>';
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>