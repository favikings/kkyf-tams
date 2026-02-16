<?php
// admin/roster.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

// Enforce Role
checkAuth('Super Admin');

// --- Backend Logic ---

// Search Logic
$search = $_GET['search'] ?? '';
$searchParam = "%$search%";

// Pagination Logic
$limit = 50;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Total Count for Pagination UI
if ($search) {
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM Members WHERE Full_Name LIKE ?");
    $stmtCount->execute([$searchParam]);
} else {
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM Members");
}
$totalRecords = $stmtCount->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Fetch Members with Tent Name
// Using LEFT JOIN to get Tent Name for display
$sql = "
    SELECT m.*, t.Tent_Name 
    FROM Members m 
    LEFT JOIN Tents t ON m.Current_Tent_ID = t.Tent_ID 
";

if ($search) {
    $sql .= " WHERE m.Full_Name LIKE :search ";
}

$sql .= " ORDER BY m.Full_Name ASC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

if ($search) {
    $stmt->bindValue(':search', $searchParam, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Master Roster</h1>
            <p class="text-slate-500">Manage all fellowship members.</p>
        </div>
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <!-- Search Form -->
            <form method="GET" class="relative w-full sm:w-64">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search using name..."
                    class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#00BD06] focus:border-transparent">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 absolute left-3 top-3"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </form>

            <button onclick="openAddModal()"
                class="bg-[#00BD06] hover:bg-[#009605] text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-colors flex items-center gap-2 flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span class="hidden sm:inline">Add Member</span>
            </button>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden min-h-[400px]">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500 font-semibold tracking-wider">
                        <th class="px-6 py-4">Full Name</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Tent</th>
                        <th class="px-6 py-4">School</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (count($members) > 0): ?>
                        <?php foreach ($members as $member): ?>
                            <tr class="hover:bg-slate-50 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-xs font-bold uppercase">
                                            <?= substr($member['Full_Name'], 0, 2) ?>
                                        </div>
                                        <span
                                            class="font-medium text-slate-900"><?= htmlspecialchars($member['Full_Name']) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                        <?= $member['Status'] === 'Student' ? 'bg-blue-50 text-blue-700' : ($member['Status'] === 'Worker' ? 'bg-purple-50 text-purple-700' : 'bg-slate-100 text-slate-600') ?>">
                                        <?= htmlspecialchars($member['Status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <?= htmlspecialchars($member['Tent_Name'] ?? 'Unassigned') ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500">
                                    <?= htmlspecialchars($member['School'] ?? '-') ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button onclick='openEditModal(<?= json_encode($member) ?>)'
                                        class="text-slate-400 hover:text-[#00BD06] transition-colors p-1" title="Edit Member">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                <?php if ($search): ?>
                                    <div class="flex flex-col items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300 mb-2"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                        </svg>
                                        <p>No members found matching "<?= htmlspecialchars($search) ?>"</p>
                                    </div>
                                <?php else: ?>
                                    <div class="flex flex-col items-center py-8">
                                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="9" cy="7" r="4"></circle>
                                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                            </svg>
                                        </div>
                                        <p class="text-lg font-medium text-slate-900">No members yet</p>
                                        <p class="text-slate-500 mb-4">Get started by adding your first member.</p>
                                        <button
                                            class="bg-[#00BD06] hover:bg-[#009605] text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-colors">
                                            Add First Member
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 border-t border-slate-200 flex justify-between items-center bg-slate-50">
                <span class="text-sm text-slate-500">
                    Page <span class="font-medium text-slate-900"><?= $page ?></span> of <span
                        class="font-medium"><?= $totalPages ?></span>
                </span>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>"
                            class="px-3 py-1 bg-white border border-slate-300 rounded text-sm text-slate-600 hover:bg-slate-50">Previous</a>
                    <?php endif; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>"
                            class="px-3 py-1 bg-white border border-slate-300 rounded text-sm text-slate-600 hover:bg-slate-50">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Member Modal -->
<div id="editModal"
    class="fixed inset-0 bg-black/50 z-[60] hidden flex items-center justify-center opacity-0 transition-opacity duration-300"
    role="dialog" aria-modal="true">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform scale-95 transition-transform duration-300"
        id="editModalContent">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-slate-800">Edit Member</h3>
            <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <form id="editMemberForm" onsubmit="handleEditSubmit(event)" class="p-6 space-y-4">
            <input type="hidden" name="action" value="update_member">
            <input type="hidden" name="member_uuid" id="edit_uuid">

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Full Name</label>
                <input type="text" name="full_name" id="edit_name" required
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#00BD06] focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                <input type="tel" name="phone" id="edit_phone"
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#00BD06] focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Birthday (Month & Day)</label>
                <div class="grid grid-cols-2 gap-4">
                    <select id="edit_dob_month"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#00BD06]">
                        <option value="">Month</option>
                        <option value="01">January</option>
                        <option value="02">February</option>
                        <option value="03">March</option>
                        <option value="04">April</option>
                        <option value="05">May</option>
                        <option value="06">June</option>
                        <option value="07">July</option>
                        <option value="08">August</option>
                        <option value="09">September</option>
                        <option value="10">October</option>
                        <option value="11">November</option>
                        <option value="12">December</option>
                    </select>
                    <select id="edit_dob_day"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#00BD06]">
                        <option value="">Day</option>
                        <?php for ($i = 1; $i <= 31; $i++): ?>
                            <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <input type="hidden" name="dob" id="edit_dob_hidden">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                    <select name="status" id="edit_status" onchange="toggleSchoolField()"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#00BD06]">
                        <option value="Student">Student</option>
                        <option value="Worker">Worker</option>
                        <option value="Alumni">Alumni</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tent</label>
                    <!-- Dynamically populated or static logic for now -->
                    <?php
                    // Fetch Tents only once for the dropdown
                    $stmtTents = $pdo->query("SELECT Tent_ID, Tent_Name FROM Tents ORDER BY Tent_Name");
                    $allTents = $stmtTents->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <select name="tent_id" id="edit_tent"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#00BD06]">
                        <?php foreach ($allTents as $tent): ?>
                            <option value="<?= $tent['Tent_ID'] ?>"><?= htmlspecialchars($tent['Tent_Name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="school_field_container">
                <label class="block text-sm font-medium text-slate-700 mb-1">School</label>
                <input type="text" name="school" id="edit_school"
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#00BD06] focus:border-transparent">
            </div>

            <div class="pt-2 flex justify-end gap-3">
                <button type="button" onclick="closeEditModal()"
                    class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-lg transition-colors">Cancel</button>
                <button type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-[#00BD06] hover:bg-[#009605] rounded-lg shadow-sm transition-colors relative"
                    id="saveBtn">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('editModal');
    const content = document.getElementById('editModalContent');
    const form = document.getElementById('editMemberForm');
    const modalTitle = modal.querySelector('h3');

    function openAddModal() {
        // Reset Form
        form.reset();

        // set Mode
        form.elements['action'].value = 'create_member';
        form.elements['member_uuid'].value = '';

        // UI
        modalTitle.innerText = 'Add New Member';
        document.getElementById('saveBtn').innerText = 'Create Member';

        // Default Status handling
        toggleSchoolField();

        removeDeleteButton(); // No delete on add
        showModal();
    }

    function openEditModal(member) {
        // Set Data
        form.elements['action'].value = 'update_member';
        form.elements['member_uuid'].value = member.Member_UUID;

        document.getElementById('edit_name').value = member.Full_Name;
        document.getElementById('edit_phone').value = member.Phone || '';

        // DOB Parsing
        if (member.Birthdate) {
            const parts = member.Birthdate.split('-'); // YYYY-MM-DD
            if (parts.length === 3) {
                document.getElementById('edit_dob_month').value = parts[1];
                document.getElementById('edit_dob_day').value = parts[2];
            }
        } else {
            document.getElementById('edit_dob_month').value = '';
            document.getElementById('edit_dob_day').value = '';
        }

        document.getElementById('edit_status').value = member.Status;
        document.getElementById('edit_status').value = member.Status;
        document.getElementById('edit_status').value = member.Status;
        document.getElementById('edit_tent').value = member.Current_Tent_ID;
        document.getElementById('edit_school').value = member.School || '';

        // UI
        modalTitle.innerText = 'Edit Member';
        document.getElementById('saveBtn').innerText = 'Save Changes';

        // Initial Status Check
        toggleSchoolField();

        addDeleteButton(); // Add delete functionality
        showModal();
    }

    function addDeleteButton() {
        if (document.getElementById('deleteMemberBtn')) return;

        const delBtn = document.createElement('button');
        delBtn.id = 'deleteMemberBtn';
        delBtn.type = 'button';
        delBtn.className = 'w-full bg-red-50 hover:bg-red-100 text-red-600 font-bold py-2 rounded-lg transition-colors mt-4 text-sm';
        delBtn.textContent = 'Delete Member';
        delBtn.onclick = confirmDelete;

        form.appendChild(delBtn);
    }

    function removeDeleteButton() {
        const btn = document.getElementById('deleteMemberBtn');
        if (btn) btn.remove();
    }

    async function confirmDelete() {
        if (await StitchAlert.confirmAction('Delete Member?', "This action cannot be undone and will remove all their attendance history.", 'Yes, Delete', true)) {
            const uuid = form.querySelector('input[name="member_uuid"]').value;
            deleteMember(uuid);
        }
    }

    async function deleteMember(uuid) {
        try {
            const formData = new FormData();
            formData.append('action', 'delete_member');
            formData.append('member_uuid', uuid);

            const res = await fetch('<?= BASE_PATH ?>/api/member_ops.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                await StitchAlert.showSuccess('Deleted!', 'Member has been removed.');
                location.reload();
            } else {
                StitchAlert.showError('Error', data.error);
            }
        } catch (err) {
            StitchAlert.showError('Error', 'Delete failed.');
        }
    }

    function showModal() {
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }, 10);
    }

    function closeEditModal() {
        modal.classList.add('opacity-0');
        content.classList.remove('scale-100');
        content.classList.add('scale-95');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Edge Case: Conditional School Field
    function toggleSchoolField() {
        const status = document.getElementById('edit_status').value;
        const schoolContainer = document.getElementById('school_field_container');
        const schoolInput = document.getElementById('edit_school');

        if (status === 'Worker') {
            schoolContainer.classList.add('opacity-50', 'pointer-events-none');
            schoolInput.disabled = true;
            schoolInput.value = ''; // Clear it visually
        } else {
            schoolContainer.classList.remove('opacity-50', 'pointer-events-none');
            schoolInput.disabled = false;
        }
    }

    async function handleEditSubmit(e) {
        e.preventDefault();
        const formData = new FormData(form);
        const btn = document.getElementById('saveBtn');
        const originalText = btn.innerText;

        // Stitch DOB
        const m = document.getElementById('edit_dob_month').value;
        const d = document.getElementById('edit_dob_day').value;
        if (m && d) {
            document.getElementById('edit_dob_hidden').value = `2000-${m}-${d}`;
        } else {
            document.getElementById('edit_dob_hidden').value = '';
        }

        btn.innerText = 'Processing...';
        btn.disabled = true;

        // STRICT DUPLICATE CHECK
        const action = formData.get('action');
        const name = formData.get('full_name');

        if (action === 'create_member') {
            try {
                // We need to fetch search result. We don't have scope here, assume global search effectively as Super Admin?
                // Roster search endpoint works. 
                // But roster logic was `SELECT m.* ...` directly.
                // We can use the API. `api/member_search.php` exists.
                // Need to import it or use it? Wait, we are in admin/roster.php, does it use member_search api?
                // Step 785 shows pure PHP loading logic. 
                // `api/member_search.php` exists. Let's use it.

                const searchRes = await fetch(`<?= BASE_PATH ?>/api/member_search.php?query=${encodeURIComponent(name)}&scope=search`);
                const searchData = await searchRes.json();

                if (searchData.success && searchData.data.length > 0) {
                    StitchAlert.showError('Duplicate Member', "This person is already a member");
                    btn.innerText = originalText;
                    btn.disabled = false;
                    return;
                }
            } catch (err) {
                console.error("Dup check error", err);
            }
        }

        try {
            const res = await fetch('<?= BASE_PATH ?>/api/member_ops.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                await StitchAlert.showSuccess('Success', 'Member saved successfully!');
                location.reload();
            } else {
                StitchAlert.showError('Error', data.error);
            }
        } catch (err) {
            console.error(err);
            StitchAlert.showError('System Error', 'Check console for details.');
        } finally {
            btn.innerText = originalText;
            btn.disabled = false;
        }
    }
</script>
</div>

</main>
</body>

</html>