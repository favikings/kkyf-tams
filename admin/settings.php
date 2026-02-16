<?php
// admin/settings.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

// Auth: Super Admin required for settings
checkAuth('Super Admin');

// Fetch current active session
$stmt = $pdo->query("SELECT Session_Name FROM Sessions WHERE Is_Active = 1 LIMIT 1");
$activeSession = $stmt->fetchColumn() ?: "None";

require_once '../includes/header.php';
?>

<div class="px-4 py-8 max-w-4xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900">System Settings</h1>
        <p class="text-slate-500">Global configurations and administrative tools.</p>
    </div>

    <div class="space-y-8">
        <!-- Session Management Section -->
        <section class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-50 bg-slate-50/50">
                <h2 class="font-bold text-slate-800 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <rect width="18" height="18" x="3" y="4" rx="2" ry="2" />
                        <line x1="16" y1="2" x2="16" y2="6" />
                        <line x1="8" y1="2" x2="8" y2="6" />
                        <line x1="3" y1="10" x2="21" y2="10" />
                    </svg>
                    Fellowship Session
                </h2>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <p class="text-sm font-medium text-slate-700">Currently Active Session</p>
                        <p class="text-2xl font-bold text-indigo-600">
                            <?= htmlspecialchars($activeSession) ?>
                        </p>
                    </div>
                </div>

                <!-- Danger Zone -->
                <div class="rounded-xl border border-red-200 bg-red-50 p-6">
                    <div class="flex items-start gap-4">
                        <div class="mt-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
                                <path d="M12 9v4" />
                                <path d="M12 17h.01" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-red-900 mb-1">Danger Zone: Start New Year</h3>
                            <p class="text-sm text-red-700 mb-4 leading-relaxed">
                                This will archive the current session and start a new fellowship year.
                                Dashboard counts will reset to zero, but member profiles and historical data will be
                                preserved.
                            </p>

                            <form onsubmit="handleSessionReset(event)" class="flex flex-col sm:flex-row gap-3">
                                <input type="text" name="session_name"
                                    placeholder="New Session Name (e.g. <?= date('Y') + 1 ?>)" required
                                    class="flex-1 px-4 py-2.5 rounded-xl border border-red-200 bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                                <button type="submit"
                                    class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-lg shadow-red-500/30 active:scale-95 transition-all flex items-center justify-center gap-2">
                                    Archive & Reset
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- System Status -->
        <section class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-50">
                <h2 class="font-bold text-slate-800">System Information</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                    <div>
                        <dt class="text-slate-500 font-medium">PHP Version</dt>
                        <dd class="text-slate-900 font-bold">
                            <?= phpversion() ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 font-medium">Server Date</dt>
                        <dd class="text-slate-900 font-bold">
                            <?= date('M j, Y H:i:s') ?>
                        </dd>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
    async function handleSessionReset(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const newSessionName = formData.get('session_name');

        if (!confirm(`Are you SURE you want to archive current data and start session "${newSessionName}"?\n\nThis cannot be easily undone.`)) {
            return;
        }

        const btn = e.target.querySelector('button');
        const originalContent = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<div class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent"></div>';

        try {
            formData.append('action', 'reset_session');
            const res = await fetch('<?= BASE_PATH ?>/api/session_ops.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        } catch (err) {
            console.error(err);
            alert('Network error. Failed to reset session.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalContent;
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>