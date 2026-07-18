document.documentElement.dataset.ready = 'true';

let deferredInstallPrompt = null;

window.addEventListener('DOMContentLoaded', () => {
    const basePath = document.body?.dataset.basePath || '';
    if ('serviceWorker' in navigator && basePath !== '') {
        navigator.serviceWorker.register(`${basePath}/service-worker.js`).catch(() => {
            // Ignore registration failures in development and unsupported hosts.
        });
    }

    const appShell = document.querySelector('.app-shell');
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const sidebarClose = document.querySelector('[data-sidebar-close]');
    const portalSidebar = document.querySelector('[data-portal-sidebar]');
    const portalBackdrop = document.querySelector('[data-portal-backdrop]');

    const setSidebar = (open) => {
        if (!appShell || !sidebarToggle || !portalSidebar) {
            return;
        }

        appShell.classList.toggle('is-sidebar-open', open);
        portalSidebar.classList.toggle('-translate-x-full', !open);
        portalBackdrop?.classList.toggle('hidden', !open);
        sidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.style.overflow = open ? 'hidden' : '';
    };

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            setSidebar(!appShell?.classList.contains('is-sidebar-open'));
        });
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', () => setSidebar(false));
    }

    document.querySelectorAll('[data-sidebar-nav] a').forEach((link) => {
        link.addEventListener('click', () => setSidebar(false));
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 880) {
            setSidebar(false);
        }
    });

    const openModal = (modal) => {
        if (!modal) {
            return;
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        modal.querySelector('input, select, textarea, button')?.focus();
    };

    const closeModal = (modal) => {
        if (!modal) {
            return;
        }

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        if (!appShell?.classList.contains('is-sidebar-open')) {
            document.body.style.overflow = '';
        }
    };

    document.querySelectorAll('[data-modal-open]').forEach((button) => {
        button.addEventListener('click', () => {
            openModal(document.querySelector(`[data-modal="${button.dataset.modalOpen}"]`));
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', () => {
            closeModal(button.closest('[data-modal]'));
        });
    });

    document.querySelectorAll('[data-modal]').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            document.querySelectorAll('[data-modal].is-open').forEach(closeModal);
        }
    });

    const notice = document.querySelector('.notice');
    const alert = document.querySelector('.alert');

    if (window.Swal && notice) {
        window.Swal.fire({
            icon: 'success',
            title: 'Done',
            text: notice.textContent.trim(),
            confirmButtonColor: '#00a83b'
        });
    }

    if (window.Swal && alert) {
        window.Swal.fire({
            icon: 'error',
            title: 'Needs attention',
            text: alert.textContent.trim(),
            confirmButtonColor: '#c2410c'
        });
    }

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-password-target');
            const input = targetId ? document.getElementById(targetId) : null;

            if (!input) {
                return;
            }

            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');

            const icon = button.querySelector('i');

            if (icon) {
                icon.setAttribute('data-lucide', isHidden ? 'eye-off' : 'eye');
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            }
        });
    });

    const offlineAttendanceRoot = document.querySelector('[data-offline-attendance]');
    if (offlineAttendanceRoot) {
        setupOfflineAttendance(offlineAttendanceRoot);
    }

    setupNotifications(basePath);
    setupPwaInstallPrompt();
    setupDashboardAttendanceChart();
});

function setupNotifications(basePath) {
    const root = document.querySelector('[data-notification-root]');
    const toggles = document.querySelectorAll('[data-notification-toggle]');
    const badges = document.querySelectorAll('[data-notification-badge]');

    if (!root || toggles.length === 0) {
        return;
    }

    const list = root.querySelector('[data-notification-list]');
    const empty = root.querySelector('[data-notification-empty]');
    const unreadLabel = root.querySelector('[data-notification-unread-label]');
    const readAllButton = root.querySelector('[data-notification-read-all]');
    const enableBrowserButton = root.querySelector('[data-notification-enable-browser]');
    const feedUrl = root.getAttribute('data-notification-feed-url') || `${basePath}/notifications`;
    const readUrl = root.getAttribute('data-notification-read-url') || `${basePath}/notifications/read`;
    const readAllUrl = root.getAttribute('data-notification-read-all-url') || `${basePath}/notifications/read-all`;
    const csrfToken = root.getAttribute('data-csrf-token') || '';
    const browserPrefKey = 'kkyf-browser-notifications-enabled';
    const seenKey = 'kkyf-last-notification-id';
    let isOpen = false;
    let hasLoaded = false;
    let pollTimer = null;

    const closePanel = () => {
        isOpen = false;
        root.classList.add('hidden');
    };

    const openPanel = () => {
        isOpen = true;
        root.classList.remove('hidden');
        fetchFeed(true);
    };

    const setBadges = (count) => {
        badges.forEach((badge) => {
            if (!badge) {
                return;
            }

            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : String(count);
                badge.classList.remove('hidden');
                badge.classList.add('inline-flex');
            } else {
                badge.textContent = '';
                badge.classList.add('hidden');
                badge.classList.remove('inline-flex');
            }
        });

        if (unreadLabel) {
            unreadLabel.textContent = `${count} unread`;
        }
    };

    const browserEnabled = () => window.localStorage.getItem(browserPrefKey) === 'yes';

    const updateBrowserButton = () => {
        if (!enableBrowserButton) {
            return;
        }

        if (!('Notification' in window)) {
            enableBrowserButton.textContent = 'Browser alerts unavailable';
            enableBrowserButton.setAttribute('disabled', 'disabled');
            enableBrowserButton.classList.add('opacity-50');
            return;
        }

        if (Notification.permission === 'granted' && browserEnabled()) {
            enableBrowserButton.textContent = 'Browser alerts enabled';
            return;
        }

        enableBrowserButton.textContent = 'Enable browser alerts';
    };

    const showBrowserNotifications = async (items) => {
        if (!browserEnabled() || !('Notification' in window) || Notification.permission !== 'granted') {
            return;
        }

        const seenValue = Number.parseInt(window.localStorage.getItem(seenKey) || '0', 10) || 0;
        const fresh = [...items]
            .filter((item) => Number(item.id || 0) > seenValue)
            .sort((left, right) => Number(left.id || 0) - Number(right.id || 0));

        if (fresh.length === 0) {
            return;
        }

        const latestId = Number(fresh[fresh.length - 1].id || 0);
        window.localStorage.setItem(seenKey, String(latestId));

        for (const item of fresh.slice(-3)) {
            const title = item.title || 'KKYF Notification';
            const notificationUrl = item.link_url ? `${basePath}${item.link_url}`.replace(`${basePath}${basePath}`, `${basePath}`) : '';
            const options = {
                body: item.message || '',
                data: {
                    url: notificationUrl,
                },
                tag: `kkyf-notification-${item.id}`,
            };

            if (navigator.serviceWorker) {
                const registration = await navigator.serviceWorker.getRegistration();
                if (registration) {
                    registration.showNotification(title, options).catch(() => {
                        new Notification(title, options);
                    });
                    continue;
                }
            }

            new Notification(title, options);
        }
    };

    const renderItems = (items) => {
        if (!list || !empty) {
            return;
        }

        if (!items.length) {
            empty.textContent = 'No notifications yet.';
            list.innerHTML = '';
            list.appendChild(empty);
            return;
        }

        list.innerHTML = items.map((item) => {
            const unreadClass = item.is_unread ? 'border-l-4 border-emerald-500 bg-emerald-50/55' : 'border-l-4 border-transparent bg-white';
            const actionButton = item.is_unread
                ? `<button type="button" class="inline-flex min-h-8 items-center justify-center rounded-full border border-slate-200 bg-white px-3 text-[11px] font-bold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" data-notification-read-id="${String(item.id)}">Mark read</button>`
                : '';
            const openLink = item.link_url
                ? `<a class="inline-flex min-h-8 items-center justify-center rounded-full bg-[#013f26] px-3 text-[11px] font-bold text-white no-underline" href="${escapeHtml(`${basePath}${item.link_url}`.replace(`${basePath}${basePath}`, `${basePath}`))}">Open</a>`
                : '';

            return `
                <article class="${unreadClass} px-4 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-slate-900">${escapeHtml(item.title || 'Notification')}</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-600">${escapeHtml(item.message || '')}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
                                <span class="inline-flex min-h-7 items-center rounded-full bg-slate-100 px-2.5">${escapeHtml(item.category || 'activity')}</span>
                                <span>${escapeHtml(item.created_at_human || '')}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        ${openLink}
                        ${actionButton}
                    </div>
                </article>
            `;
        }).join('');
    };

    const fetchFeed = async (interactive = false) => {
        try {
            const response = await fetch(feedUrl, {
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Unable to load notifications.');
            }

            const payload = await response.json();
            const items = Array.isArray(payload.items) ? payload.items : [];
            const unreadCount = Number(payload.unread_count || 0);

            renderItems(items);
            setBadges(unreadCount);

            if (hasLoaded) {
                showBrowserNotifications(items.filter((item) => item.is_unread)).catch(() => {});
            } else if (items.length > 0) {
                const latestId = Number(items[0].id || 0);
                if (latestId > 0 && !window.localStorage.getItem(seenKey)) {
                    window.localStorage.setItem(seenKey, String(latestId));
                }
            }

            hasLoaded = true;
        } catch (error) {
            if (interactive && empty) {
                empty.textContent = 'Unable to load notifications right now.';
            }
        }
    };

    const postAction = async (url, body) => {
        const response = await fetch(url, {
            method: 'POST',
            body,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Request failed.');
        }

        return response.json();
    };

    toggles.forEach((toggle) => {
        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            if (isOpen) {
                closePanel();
            } else {
                openPanel();
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (isOpen && !root.contains(event.target)) {
            closePanel();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen) {
            closePanel();
        }
    });

    root.addEventListener('click', async (event) => {
        const readButton = event.target.closest('[data-notification-read-id]');
        if (readButton) {
            const formData = new FormData();
            formData.append('_csrf_token', csrfToken);
            formData.append('id', readButton.getAttribute('data-notification-read-id') || '0');

            try {
                const payload = await postAction(readUrl, formData);
                renderItems(Array.isArray(payload.items) ? payload.items : []);
                setBadges(Number(payload.unread_count || 0));
            } catch (error) {
                // Ignore inline action failure.
            }
        }
    });

    if (readAllButton) {
        readAllButton.addEventListener('click', async () => {
            const formData = new FormData();
            formData.append('_csrf_token', csrfToken);

            try {
                const payload = await postAction(readAllUrl, formData);
                renderItems(Array.isArray(payload.items) ? payload.items : []);
                setBadges(Number(payload.unread_count || 0));
            } catch (error) {
                // Ignore inline action failure.
            }
        });
    }

    if (enableBrowserButton) {
        enableBrowserButton.addEventListener('click', async () => {
            if (!('Notification' in window)) {
                return;
            }

            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                window.localStorage.setItem(browserPrefKey, 'yes');
            }
            updateBrowserButton();
        });
    }

    updateBrowserButton();
    fetchFeed(false);
    pollTimer = window.setInterval(() => {
        fetchFeed(false);
    }, 60000);

    window.addEventListener('beforeunload', () => {
        if (pollTimer) {
            window.clearInterval(pollTimer);
        }
    });
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function setupDashboardAttendanceChart() {
    const canvas = document.getElementById('dashboardAttendanceChart');
    if (!canvas || !window.Chart) {
        return;
    }

    let labels = [];
    let values = [];

    try {
        labels = JSON.parse(canvas.dataset.labels || '[]');
        values = JSON.parse(canvas.dataset.values || '[]');
    } catch (error) {
        return;
    }

    const maxValue = Math.max(...values, 1);
    const baselineValues = values.map(() => Math.ceil(maxValue * 1.15));

    new window.Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Baseline',
                    data: baselineValues,
                    backgroundColor: '#dff3e7',
                    borderRadius: 6,
                    barPercentage: 0.72,
                    categoryPercentage: 0.7,
                    grouped: false,
                },
                {
                    label: 'Attended',
                    data: values,
                    backgroundColor: '#27ae60',
                    borderRadius: 6,
                    barPercentage: 0.52,
                    categoryPercentage: 0.7,
                    grouped: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    displayColors: false,
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${context.parsed.y.toLocaleString()}`,
                    },
                },
            },
            scales: {
                x: {
                    stacked: false,
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: '#647067',
                        font: {
                            size: 12,
                            weight: '700',
                        },
                    },
                },
                y: {
                    beginAtZero: true,
                    display: false,
                    grid: {
                        display: false,
                    },
                    suggestedMax: Math.ceil(maxValue * 1.2),
                },
            },
        },
    });
}

function setupPwaInstallPrompt() {
    const banner = document.querySelector('[data-pwa-install-banner]');
    const installButton = document.querySelector('[data-pwa-install-action]');
    const dismissButton = document.querySelector('[data-pwa-install-dismiss]');
    const messageNode = document.querySelector('[data-pwa-install-message]');
    const dismissedKey = 'kkyf-pwa-install-dismissed';

    if (!banner || !installButton || !dismissButton) {
        return;
    }

    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    if (isStandalone) {
        banner.hidden = true;
        return;
    }

    const isiPhone = /iphone|ipad|ipod/i.test(window.navigator.userAgent);
    const isSafari = /^((?!chrome|android).)*safari/i.test(window.navigator.userAgent);

    const showInstallHelp = (message, title = 'Install App') => {
        if (window.Swal) {
            window.Swal.fire({
                icon: 'info',
                title,
                text: message,
                confirmButtonColor: '#00a83b'
            });
            return;
        }

        window.alert(message);
    };

    const showBanner = (message) => {
        if (window.localStorage.getItem(dismissedKey) === '1') {
            return;
        }

        if (messageNode && message) {
            messageNode.textContent = message;
        }

        banner.hidden = false;
        banner.classList.remove('is-hidden');
        banner.style.display = 'grid';
    };

    const hideBanner = () => {
        banner.hidden = true;
        banner.classList.add('is-hidden');
        banner.style.display = 'none';
    };

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredInstallPrompt = event;
        showBanner('Quick access for members and attendance.');
    });

    window.addEventListener('appinstalled', () => {
        deferredInstallPrompt = null;
        window.localStorage.setItem(dismissedKey, '1');
        hideBanner();
    });

    if (isiPhone && isSafari) {
        showBanner('On iPhone, use Share and then Add to Home Screen to install this app.');
        installButton.querySelector('span')?.remove();
        installButton.innerHTML = '<i data-lucide="share"></i> How to Install';
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    installButton.addEventListener('click', async () => {
        if (isiPhone && isSafari) {
            showInstallHelp('In Safari on iPhone, tap Share, then choose Add to Home Screen. Apple does not show the normal install popup button here.');
            return;
        }

        if (!deferredInstallPrompt) {
            showInstallHelp('This browser has not exposed the install prompt yet. Try Chrome or Edge, refresh once, and if needed use the browser menu and choose Install app or Add to Home Screen.', 'Install Not Ready');
            return;
        }

        deferredInstallPrompt.prompt();
        const choice = await deferredInstallPrompt.userChoice;

        if (choice.outcome !== 'accepted') {
            showBanner('Install is still available whenever you are ready.');
            return;
        }

        deferredInstallPrompt = null;
        hideBanner();
    });

    dismissButton.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        deferredInstallPrompt = null;
        window.localStorage.setItem(dismissedKey, '1');
        hideBanner();
    });
}

function setupOfflineAttendance(root) {
    const dbName = 'kkyf-attendance-offline';
    const storeName = 'attendanceQueue';
    const syncStorageKey = 'kkyf-attendance-last-sync';
    const syncEndpoint = root.getAttribute('data-sync-endpoint') || '';
    const csrfToken = root.getAttribute('data-csrf-token') || '';
    const attendanceDate = root.getAttribute('data-attendance-date') || '';
    const queueCountNode = root.querySelector('[data-offline-queue-count]');
    const syncStateNode = root.querySelector('[data-offline-sync-state]');
    const syncMessageNode = root.querySelector('[data-offline-sync-message]');
    const lastSyncTimeNode = root.querySelector('[data-offline-last-sync-time]');
    const lastSyncResultNode = root.querySelector('[data-offline-last-sync-result]');
    const networkLabelNode = root.querySelector('[data-offline-network-label]');
    const syncButton = root.querySelector('[data-offline-sync-now]');
    const clearButton = root.querySelector('[data-offline-clear-queue]');
    const queuedSummary = root.querySelector('[data-offline-queued-summary]');
    const queuedSummaryText = root.querySelector('[data-offline-queued-summary-text]');
    const queuedFeed = root.querySelector('[data-offline-queued-feed]');
    const queuedFeedCount = root.querySelector('[data-offline-queued-feed-count]');
    const queuedFeedList = root.querySelector('[data-offline-queued-feed-list]');
    const recentEmptyState = root.querySelector('[data-recent-checkins-empty]');
    let syncing = false;

    const queuedMarkup = '<span class="queued-state"><i data-lucide="wifi-off"></i> Queued Offline</span>';

    const formatDateTime = (value) => {
        if (!value) {
            return 'Not yet synced on this device';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return 'Not yet synced on this device';
        }

        return new Intl.DateTimeFormat(undefined, {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(date);
    };

    const readLastSyncReport = () => {
        try {
            const raw = window.localStorage.getItem(syncStorageKey);
            return raw ? JSON.parse(raw) : null;
        } catch (error) {
            return null;
        }
    };

    const writeLastSyncReport = (report) => {
        try {
            window.localStorage.setItem(syncStorageKey, JSON.stringify(report));
        } catch (error) {
            // Ignore storage failures on locked-down browsers.
        }
    };

    const renderLastSyncReport = (report) => {
        if (lastSyncTimeNode) {
            lastSyncTimeNode.textContent = formatDateTime(report?.timestamp || '');
        }

        if (!lastSyncResultNode) {
            return;
        }

        if (!report) {
            lastSyncResultNode.textContent = 'Queued, duplicate, and error counts will show here.';
            return;
        }

        const fragments = [];

        if (typeof report.synced === 'number') {
            fragments.push(`${report.synced} synced`);
        }

        if (typeof report.duplicates === 'number' && report.duplicates > 0) {
            fragments.push(`${report.duplicates} duplicate`);
        }

        if (typeof report.failed === 'number' && report.failed > 0) {
            fragments.push(`${report.failed} failed`);
        }

        if (fragments.length === 0 && report.message) {
            fragments.push(report.message);
        }

        lastSyncResultNode.textContent = fragments.join(' · ') || 'Queued, duplicate, and error counts will show here.';
    };

    const persistLastSyncReport = (report) => {
        writeLastSyncReport(report);
        renderLastSyncReport(report);
    };

    const requestBackgroundSync = async () => {
        if (!('serviceWorker' in navigator)) {
            return;
        }

        try {
            const registration = await navigator.serviceWorker.ready;
            if ('sync' in registration) {
                await registration.sync.register('attendance-sync');
            }
        } catch (error) {
            // Ignore unsupported background sync environments.
        }
    };

    const openDb = () => new Promise((resolve, reject) => {
        const request = window.indexedDB.open(dbName, 1);
        request.onupgradeneeded = () => {
            const db = request.result;
            if (!db.objectStoreNames.contains(storeName)) {
                db.createObjectStore(storeName, { keyPath: 'local_id' });
            }
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });

    const getAllQueued = async () => {
        const db = await openDb();
        return await new Promise((resolve, reject) => {
            const transaction = db.transaction(storeName, 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result || []);
            request.onerror = () => reject(request.error);
        });
    };

    const saveQueued = async (record) => {
        const db = await openDb();
        return await new Promise((resolve, reject) => {
            const transaction = db.transaction(storeName, 'readwrite');
            transaction.objectStore(storeName).put(record);
            transaction.oncomplete = () => resolve(true);
            transaction.onerror = () => reject(transaction.error);
        });
    };

    const deleteQueued = async (localIds) => {
        const db = await openDb();
        return await new Promise((resolve, reject) => {
            const transaction = db.transaction(storeName, 'readwrite');
            const store = transaction.objectStore(storeName);
            localIds.forEach((id) => store.delete(id));
            transaction.oncomplete = () => resolve(true);
            transaction.onerror = () => reject(transaction.error);
        });
    };

    const clearQueue = async () => {
        const db = await openDb();
        return await new Promise((resolve, reject) => {
            const transaction = db.transaction(storeName, 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.clear();
            request.onsuccess = () => resolve(true);
            request.onerror = () => reject(request.error);
        });
    };

    const setState = (state, message) => {
        if (syncStateNode) {
            syncStateNode.textContent = state;
        }
        if (syncMessageNode) {
            syncMessageNode.textContent = message;
        }
    };

    const updateNetworkLabel = () => {
        if (!networkLabelNode) {
            return;
        }
        networkLabelNode.textContent = navigator.onLine ? 'Online' : 'Offline';
    };

    const refreshQueueUi = async () => {
        const queued = await getAllQueued();
        applyQueuedRowState(queued);
        applyQueuedFeedState(queued);
        if (queueCountNode) {
            queueCountNode.textContent = String(queued.length);
        }
        if (syncButton) {
            syncButton.disabled = queued.length === 0 || syncing || !navigator.onLine;
            syncButton.setAttribute('aria-disabled', syncButton.disabled ? 'true' : 'false');
        }
        if (clearButton) {
            clearButton.disabled = queued.length === 0 || syncing;
            clearButton.setAttribute('aria-disabled', clearButton.disabled ? 'true' : 'false');
        }
        return queued;
    };

    const applyQueuedFeedState = (queued) => {
        if (queuedSummary && queuedSummaryText) {
            queuedSummary.hidden = queued.length === 0;
            queuedSummaryText.textContent = queued.length === 1
                ? '1 check-in is waiting in the offline queue.'
                : `${queued.length} check-ins are waiting in the offline queue.`;
        }

        if (queuedFeed && queuedFeedCount && queuedFeedList) {
            queuedFeed.hidden = queued.length === 0;
            queuedFeedCount.textContent = queued.length === 1 ? '1 pending' : `${queued.length} pending`;
            queuedFeedList.innerHTML = queued
                .slice(0, 4)
                .map((record) => {
                    const parts = String(record.member_name || 'Member').trim().split(/\s+/);
                    const initials = ((parts[0] || 'M').slice(0, 1) + (parts[1] || '').slice(0, 1)).toUpperCase();
                    const tentName = record.tent_name || 'Tent';
                    return `
                        <div class="queued-feed-item">
                            <span class="mini-icon mini-avatar">${initials}</span>
                            <div>
                                <strong>${escapeHtml(String(record.member_name || 'Member'))}</strong>
                                <small>${escapeHtml(String(tentName))} · queued offline</small>
                            </div>
                        </div>
                    `;
                })
                .join('');
        }

        if (recentEmptyState) {
            recentEmptyState.hidden = queued.length > 0;
        }
    };

    const applyQueuedRowState = (queued) => {
        const queuedIds = new Set(queued.map((record) => String(record.member_id || '')));

        root.querySelectorAll('[data-attendance-row]').forEach((row) => {
            const memberId = row.getAttribute('data-member-id') || '';
            const form = row.querySelector('[data-offline-checkin-form]');
            const button = row.querySelector('[data-checkin-button]');
            const actionCell = row.querySelector('td[data-label="Action"]');

            if (queuedIds.has(memberId) && form && actionCell) {
                row.classList.add('is-queued-offline');
                form.hidden = true;
                let badge = actionCell.querySelector('.queued-state');
                if (!badge) {
                    actionCell.insertAdjacentHTML('beforeend', queuedMarkup);
                    badge = actionCell.querySelector('.queued-state');
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                }
                return;
            }

            row.classList.remove('is-queued-offline');
            actionCell?.querySelector('.queued-state')?.remove();
            if (form) {
                form.hidden = false;
            }
            if (button && !button.disabled) {
                button.textContent = 'Check In';
            }
        });
    };

    const syncQueuedAttendance = async () => {
        if (syncing || !navigator.onLine || syncEndpoint === '' || csrfToken === '') {
            return;
        }

        const queued = await refreshQueueUi();
        if (queued.length === 0) {
            setState('Idle', 'No queued check-ins are waiting to sync.');
            return;
        }

        syncing = true;
        await refreshQueueUi();
        setState('Syncing', `Syncing ${queued.length} queued check-in(s)...`);

        try {
            const response = await fetch(syncEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    csrf_token: csrfToken,
                    records: queued,
                }),
            });

            const payload = await response.json();
            const results = Array.isArray(payload.results) ? payload.results : [];
            const removableIds = results
                .filter((item) => item.status === 'sent' || item.status === 'duplicate')
                .map((item) => item.local_id);

            if (removableIds.length > 0) {
                await deleteQueued(removableIds);
            }

            const failedCount = results.filter((item) => item.status === 'error').length;
            const syncedCount = results.filter((item) => item.status === 'sent').length;
            const duplicateCount = results.filter((item) => item.status === 'duplicate').length;
            const errorMessages = results
                .filter((item) => item.status === 'error' && item.message)
                .map((item) => String(item.message));

            setState(
                failedCount > 0 ? 'Partial' : 'Complete',
                failedCount > 0
                    ? `${syncedCount} synced, ${duplicateCount} duplicate, ${failedCount} still queued for review.`
                    : `${syncedCount} queued check-in(s) synced successfully${duplicateCount > 0 ? `, ${duplicateCount} already existed` : ''}.`
            );

            persistLastSyncReport({
                timestamp: new Date().toISOString(),
                synced: syncedCount,
                duplicates: duplicateCount,
                failed: failedCount,
                message: errorMessages[0] || payload.message || 'Offline attendance sync completed.',
            });
        } catch (error) {
            setState('Queued', 'Unable to sync right now. Queued records stay safely on this device.');
            persistLastSyncReport({
                timestamp: new Date().toISOString(),
                synced: 0,
                duplicates: 0,
                failed: 0,
                message: 'Sync could not reach the server. The local queue is still safe on this device.',
            });
        } finally {
            syncing = false;
            await refreshQueueUi();
        }
    };

    root.querySelectorAll('[data-offline-checkin-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            if (navigator.onLine) {
                return;
            }

            event.preventDefault();
            const button = form.querySelector('[data-member-id]');
            if (!button) {
                return;
            }

            const record = {
                local_id: `offline-${Date.now()}-${button.getAttribute('data-member-id')}`,
                member_id: Number(button.getAttribute('data-member-id') || '0'),
                member_name: button.getAttribute('data-member-name') || 'Member',
                tent_name: button.getAttribute('data-member-tent') || '',
                attendance_date: attendanceDate,
                queued_at: new Date().toISOString(),
            };

            try {
                await saveQueued(record);
                await requestBackgroundSync();
                button.disabled = true;
                button.textContent = 'Queued Offline';
                setState('Queued', `${record.member_name} was saved for sync when internet returns.`);
                await refreshQueueUi();
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'success',
                        title: 'Saved offline',
                        text: `${record.member_name} will sync automatically when you are back online.`,
                        confirmButtonColor: '#00a83b'
                    });
                }
            } catch (error) {
                setState('Error', 'Offline queue could not save this check-in on the device.');
            }
        });
    });

    syncButton?.addEventListener('click', async () => {
        await syncQueuedAttendance();
    });

    clearButton?.addEventListener('click', async () => {
        await clearQueue();
        setState('Cleared', 'Local offline attendance queue cleared on this device.');
        await refreshQueueUi();
    });

    window.addEventListener('online', async () => {
        updateNetworkLabel();
        await syncQueuedAttendance();
    });

    window.addEventListener('offline', () => {
        updateNetworkLabel();
        setState('Offline', 'New check-ins will be saved locally until your connection returns.');
        refreshQueueUi();
    });

    navigator.serviceWorker?.addEventListener('message', async (event) => {
        if (event.data?.type === 'offline-attendance-sync') {
            await syncQueuedAttendance();
        }
    });

    updateNetworkLabel();
    renderLastSyncReport(readLastSyncReport());
    refreshQueueUi().then((queued) => {
        if (navigator.onLine && queued.length > 0) {
            setState('Queued', `${queued.length} queued check-in(s) are ready to sync.`);
            syncQueuedAttendance();
            return;
        }

        if (!navigator.onLine) {
            setState('Offline', 'New check-ins will be saved locally until your connection returns.');
            return;
        }

        setState('Ready', 'Online check-ins submit immediately. Offline queue is standing by.');
    });
}

function escapeHtml(value) {
    return value
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
