<?php
// tent/attendance.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

checkAuth('Tent Admin');

// Context Logic
$isSuperAdmin = ($_SESSION['role'] === 'Super Admin');
$tentId = null;
$tentName = null;

if ($isSuperAdmin && isset($_GET['tent_id'])) {
    // Super Admin Impersonation Mode
    $tentId = $_GET['tent_id'];
    // Fetch Tent Name
    $stmtName = $pdo->prepare("SELECT Tent_Name FROM Tents WHERE Tent_ID = ?");
    $stmtName->execute([$tentId]);
    $tentName = $stmtName->fetchColumn();
} else {
    // Regular Tent Admin
    $tentId = $_SESSION['assigned_tent_id'] ?? null;
    $tentName = $_SESSION['tent_name'] ?? 'Unknown Tent';
}

if (!$tentId) {
    die("Tent context required. Please return to dashboard.");
}
require_once '../includes/header.php';
?>

<div class="px-4 py-6 max-w-lg mx-auto min-h-screen flex flex-col">

    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Take Attendance</h1>
            <p class="text-slate-500 text-sm"><?= htmlspecialchars($tentName) ?> • <?= date('D, M j') ?></p>
        </div>
        <a href="<?= BASE_PATH ?>/tent/dashboard.php" class="text-slate-400 hover:text-slate-600 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5" />
                <path d="M12 19l-7-7 7-7" />
            </svg>
        </a>
    </div>

    <!-- Search Bar -->
    <div class="relative mb-6 group z-30">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-slate-400 group-focus-within:text-[#00BD06] transition-colors"
                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                    clip-rule="evenodd" />
            </svg>
        </div>
        <input type="text" id="memberSearch" placeholder="Search member by name..."
            class="block w-full pl-10 pr-3 py-3 border border-slate-200 rounded-xl leading-5 bg-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#00BD06] focus:border-transparent shadow-sm transition-all"
            autocomplete="off">
    </div>

    <!-- Results List -->
    <div id="resultsArea" class="flex-1 space-y-3 pb-24">
        <!-- Default State or Results -->
        <div class="text-center py-10 opacity-50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-slate-300 mb-2" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <p class="text-slate-500">Search to find members</p>
        </div>
    </div>

    <!-- FAB: Add Member -->
    <button onclick="openAddModal()"
        class="fixed bottom-24 right-6 md:bottom-10 md:right-10 w-14 h-14 bg-[#00BD06] hover:bg-[#009605] text-white rounded-full shadow-lg shadow-green-500/40 flex items-center justify-center transition-transform active:scale-95 z-40 group">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 group-hover:rotate-90 transition-transform"
            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
    </button>

</div>

<!-- Add Member Modal -->
<div id="addMemberModal"
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center pointer-events-none opacity-0 transition-opacity duration-300">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeAddModal()"></div>

    <!-- Modal Content -->
    <div class="bg-white w-full max-w-md rounded-t-2xl sm:rounded-2xl p-6 transform translate-y-full sm:translate-y-0 sm:scale-95 transition-all duration-300 pointer-events-auto"
        id="modalContent">
        <div class="flex justify-between items-center mb-5">
            <h3 class="text-lg font-bold text-slate-900">Add First Timer</h3>
            <button onclick="closeAddModal()" class="text-slate-400 hover:text-slate-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <form id="addMemberForm" onsubmit="handleAddMember(event)" class="space-y-4">
            <input type="hidden" name="action" value="create_member">
            <input type="hidden" name="tent_id" value="<?= htmlspecialchars($tentId) ?>">

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Full Name</label>
                <input type="text" name="full_name" required
                    class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#00BD06]">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Phone (Opt)</label>
                    <input type="tel" name="phone"
                        class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#00BD06]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">DOB (Opt)</label>
                    <input type="date" name="dob"
                        class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#00BD06]">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                <select name="status"
                    class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#00BD06]">
                    <option value="Student">Student</option>
                    <option value="Worker">Worker</option>
                    <option value="Guest">Guest</option>
                </select>
            </div>

            <button type="submit"
                class="w-full bg-[#00BD06] hover:bg-[#009605] text-white font-bold py-3.5 rounded-xl shadow-lg shadow-green-500/20 active:scale-95 transition-all mt-4">
                Save & Mark Present
            </button>
        </form>
    </div>
</div>

<script>
    const searchInput = document.getElementById('memberSearch');
    const resultsArea = document.getElementById('resultsArea');
    let debounceTimer;

    // Search Logic
    searchInput.addEventListener('input', (e) => {
        const query = e.target.value;
        clearTimeout(debounceTimer);

        if (query.length < 2) {
            resultsArea.innerHTML = `
                <div class="text-center py-10 opacity-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-slate-300 mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <p class="text-slate-500">Search to find members</p>
                </div>`;
            return;
        }

        debounceTimer = setTimeout(() => performSearch(query), 300);
    });

    async function performSearch(query) {
        resultsArea.innerHTML = '<div class="flex justify-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#00BD06]"></div></div>';

        try {
            const res = await fetch(`<?= BASE_PATH ?>/api/member_search.php?query=${encodeURIComponent(query)}`);
            const data = await res.json();

            if (data.success && data.data.length > 0) {
                renderResults(data.data);
            } else {
                resultsArea.innerHTML = `
                    <div class="text-center py-10">
                        <p class="text-slate-400">No members found.</p>
                        <button onclick="openAddModal()" class="text-[#00BD06] font-bold mt-2 hover:underline">Add New Member?</button>
                    </div>`;
            }
        } catch (err) {
            console.error(err);
            resultsArea.innerHTML = '<p class="text-center text-red-500">Error searching.</p>';
        }
    }

    function renderResults(members) {
        const queue = getQueue();
        const pendingUuids = queue.map(i => i.memberUuid);

        resultsArea.innerHTML = members.map(m => {
            const isPresent = parseInt(m.has_attended_today) === 1;
            const isPending = pendingUuids.includes(m.Member_UUID);

            let btnClass, btnContent, onClick;

            if (isPresent) {
                btnClass = 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed';
                btnContent = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> <span>Present</span>`;
                onClick = '';
            } else if (isPending) {
                btnClass = 'bg-yellow-100 text-yellow-600 border-yellow-200 cursor-wait';
                btnContent = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> <span>Pending</span>`;
                onClick = '';
            } else {
                btnClass = 'bg-[#00BD06] text-white border-transparent hover:bg-[#009605] shadow-md active:scale-95 transition-transform';
                btnContent = `<span>Mark Present</span>`;
                // Quote the UUID string!
                onClick = `onclick="markAttendance('${m.Member_UUID}', this)"`;
            }

            return `
            <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between">
                <div>
                    <h4 class="font-bold text-slate-800">${m.Full_Name}</h4>
                    <p class="text-xs text-slate-500">${m.Status}</p>
                </div>
                <button ${onClick} data-member-uuid="${m.Member_UUID}" class="px-4 py-2 rounded-lg font-bold text-sm border transition-all flex items-center gap-2 ${btnClass}">
                    ${btnContent}
                </button>
            </div>
            `;
        }).join('');
    }

    // --- Offline Sync Logic ---
    const QUEUE_KEY = 'attendance_queue';

    function getQueue() {
        return JSON.parse(localStorage.getItem(QUEUE_KEY) || '[]');
    }

    function addToQueue(item) {
        const queue = getQueue();
        queue.push(item);
        localStorage.setItem(QUEUE_KEY, JSON.stringify(queue));
    }

    function removeFromQueue(memberUuid) {
        let queue = getQueue();
        queue = queue.filter(i => i.memberUuid !== memberUuid);
        localStorage.setItem(QUEUE_KEY, JSON.stringify(queue));
    }

    async function processQueue() {
        const queue = getQueue();
        if (queue.length === 0) return;

        console.log('Processing offline queue:', queue.length, 'items');

        // Process sequentially to avoid overwhelming server
        for (const item of queue) {
            try {
                const res = await fetch(`<?= BASE_PATH ?>/api/mark_attendance.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ member_uuid: item.memberUuid })
                });
                const data = await res.json();

                if (data.success) {
                    removeFromQueue(item.memberUuid);
                    // Update UI if the element exists (user matches search)
                    updateUIState(item.memberUuid, 'synced');
                }
            } catch (err) {
                console.error('Sync failed for', item.memberUuid, err);
                // Keep in queue
            }
        }
    }

    function updateUIState(memberUuid, state) {
        const btn = document.querySelector(`button[data-member-uuid="${memberUuid}"]`);
        if (btn) {
            if (state === 'pending') {
                btn.className = "px-4 py-2 rounded-lg font-bold text-sm border transition-all flex items-center gap-2 bg-yellow-100 text-yellow-600 border-yellow-200 cursor-wait";
                btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> <span>Pending</span>`;
                btn.removeAttribute('onclick'); // Prevent double click
            } else if (state === 'synced') {
                btn.className = "px-4 py-2 rounded-lg font-bold text-sm border transition-all flex items-center gap-2 bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed";
                btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> <span>Present</span>`;
            }
        }
    }

    // Event Listeners
    window.addEventListener('online', () => {
        console.log('Online! Syncing...');
        processQueue();
    });

    window.addEventListener('load', () => {
        if (navigator.onLine) {
            processQueue();
        }
    });

    async function markAttendance(memberUuid, btn) {
        if (btn.disabled) return;
        btn.disabled = true;
        btn.style.pointerEvents = 'none';

        // Optimistic UI Update -> Pending State
        const originalContent = btn.innerHTML;
        const originalClass = btn.className;

        btn.innerHTML = '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-current"></div>';

        try {
            const res = await fetch(`<?= BASE_PATH ?>/api/mark_attendance.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ member_uuid: memberUuid })
            });
            const data = await res.json();

            if (data.success) {
                // Success State
                updateUIState(memberUuid, 'synced');
            } else {
                throw new Error(data.error);
            }
        } catch (err) {
            console.error('Network failed, queuing...', err);
            // Offline Fallback
            addToQueue({ memberUuid: memberUuid, timestamp: Date.now() });
            updateUIState(memberUuid, 'pending');
            // Allow retry of sync later (via online event)
        }
    }

    // Modal Logic
    const modal = document.getElementById('addMemberModal');
    const modalContent = document.getElementById('modalContent');

    function openAddModal() {
        modal.classList.remove('pointer-events-none', 'opacity-0');
        modalContent.classList.remove('translate-y-full', 'scale-95');
    }

    function closeAddModal() {
        modal.classList.add('pointer-events-none', 'opacity-0');
        modalContent.classList.add('translate-y-full', 'scale-95');
        document.getElementById('addMemberForm').reset();
    }

    async function handleAddMember(e) {
        e.preventDefault();
        const formData = new FormData(e.target);

        const name = formData.get('full_name');

        // 1. Duplicate Check
        try {
            const searchRes = await fetch(`<?= BASE_PATH ?>/api/member_search.php?query=${encodeURIComponent(name)}`);
            const searchData = await searchRes.json();

            if (searchData.success && searchData.data.length > 0) {
                // Found potential duplicates
                const duplicateNames = searchData.data.map(m => m.Full_Name).join(', ');
                const confirmed = confirm(`Possible duplicate(s) found:\n${duplicateNames}\n\nAre you sure you want to create "${name}"?`);
                if (!confirmed) return;
            }
        } catch (e) {
            console.error("Duplicate check failed, proceeding anyway", e);
        }

        // 2. Create Member
        try {
            const res = await fetch(`<?= BASE_PATH ?>/api/member_ops.php`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                closeAddModal();
                // Clear search and show success message or just refresh logic
                // For better UX, let's just trigger a search for the new name
                searchInput.value = formData.get('full_name');
                searchInput.dispatchEvent(new Event('input'));

                // Show Toast (Using simplified alert for now, or stitch toast if available)
                alert('Member Added & Marked Present!');
            } else {
                alert('Error: ' + data.error);
            }
        } catch (err) {
            console.error(err);
            alert('Failed to add member.');
        }
    }
</script>

<style>
    .animate-bounce-short {
        animation: bounce-short 0.3s ease-in-out;
    }

    @keyframes bounce-short {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }
    }
</style>

<?php require_once '../includes/footer.php'; ?>