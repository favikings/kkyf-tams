<?php
// includes/header.php

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user role safely
$userRole = $_SESSION['role'] ?? 'Guest';

// Helper to check if a path is active
$current_page = basename($_SERVER['PHP_SELF']);
function is_active($path, $current_page)
{
    return basename($path) === $current_page ? 'text-[#00BD06]' : '';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>KKYF TAMS - Dashboard</title>

    <!-- PWA Manifest -->
    <link rel="manifest" href="<?= BASE_PATH ?>/manifest.json">
    <meta name="theme-color" content="#00BD06">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS (CDN for Dev) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        inter: ['"Inter"', 'sans-serif'],
                    },
                    colors: {
                        primary: '#00BD06', // Vision Green
                        'primary-hover': '#009605',
                        secondary: '#1E293B', // Slate 800
                        'bg-light': '#F8FAFC', // Slate 50
                    }
                }
            }
        }
    </script>
    <style>
        /* Mobile Sticky Footer Fix */
        body {
            padding-bottom: 80px;
        }
    </style>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= BASE_PATH ?>/assets/js/stitch_alerts.js"></script>
</head>

<body class="bg-slate-50 min-h-screen">

    <!-- Navbar -->
    <nav class="bg-white border-b border-slate-100 sticky top-0 z-50 h-16 flex items-center">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="flex justify-between h-16 items-center">
                <!-- Branding -->
                <div class="flex items-center">
                    <a href="<?= BASE_PATH ?>/" class="flex-shrink-0 flex items-center gap-2 h-12">
                        <div class="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center text-[#00BD06]">
                            <!-- Simple Logo Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <span class="font-bold text-slate-800 tracking-tight">KKYF TAMS</span>
                    </a>
                </div>

                <!-- Desktop Navigation (Hidden on Mobile) -->
                <div class="hidden md:flex md:items-center md:space-x-8">
                    <?php if ($userRole === 'Super Admin'): ?>
                        <a href="<?= BASE_PATH ?>/admin/dashboard.php"
                            class="font-medium hover:text-[#00BD06] transition-colors h-12 flex items-center <?= is_active(BASE_PATH . '/admin/dashboard.php', $current_page) ?: 'text-slate-900' ?>">Dashboard</a>
                        <a href="<?= BASE_PATH ?>/admin/roster.php"
                            class="hover:text-[#00BD06] transition-colors h-12 flex items-center <?= is_active(BASE_PATH . '/admin/roster.php', $current_page) ?: 'text-slate-500' ?>">Master
                            Roster</a>
                        <a href="<?= BASE_PATH ?>/admin/reports.php"
                            class="hover:text-[#00BD06] transition-colors h-12 flex items-center <?= is_active(BASE_PATH . '/admin/reports.php', $current_page) ?: 'text-slate-500' ?>">Reports</a>
                        <a href="<?= BASE_PATH ?>/admin/settings.php"
                            class="hover:text-[#00BD06] transition-colors h-12 flex items-center <?= is_active(BASE_PATH . '/admin/settings.php', $current_page) ?: 'text-slate-500' ?>">Settings</a>
                    <?php elseif ($userRole === 'Tent Admin'): ?>
                        <a href="<?= BASE_PATH ?>/tent/dashboard.php"
                            class="font-medium hover:text-[#00BD06] transition-colors h-12 flex items-center <?= is_active(BASE_PATH . '/tent/dashboard.php', $current_page) ?: 'text-slate-900' ?>">Dashboard</a>
                        <a href="<?= BASE_PATH ?>/tent/attendance.php"
                            class="bg-[#00BD06] text-white px-4 py-2 rounded-full text-sm font-medium hover:bg-[#009605] shadow-lg shadow-green-500/20 transition-all flex items-center justify-center h-10">Take
                            Attendance</a>
                        <a href="<?= BASE_PATH ?>/tent/members.php"
                            class="hover:text-[#00BD06] transition-colors h-12 flex items-center <?= is_active(BASE_PATH . '/tent/members.php', $current_page) ?: 'text-slate-500' ?>">My
                            Members</a>
                    <?php endif; ?>
                </div>

                <div class="flex items-center">
                    <div class="flex items-center gap-4 h-12">
                        <div class="hidden sm:flex flex-col items-end">
                            <span class="text-sm font-semibold text-slate-800 leading-tight">
                                <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
                            </span>
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $userRole === 'Super Admin' ? 'bg-indigo-50 text-indigo-600' : 'bg-green-100 text-[#009605]' ?>">
                                <?= $userRole === 'Tent Admin' ? "Tent Admin: " . htmlspecialchars($_SESSION['tent_name'] ?? 'Unassigned') : $userRole ?>
                            </span>
                        </div>
                        <a href="<?= BASE_PATH ?>/logout.php"
                            class="text-slate-400 hover:text-red-500 transition-colors p-2 rounded-full hover:bg-slate-100 h-10 w-10 flex items-center justify-center"
                            title="Logout">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Bottom Navigation (Visible only on Mobile) -->
    <div
        class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-100 h-16 px-6 flex justify-between items-center z-50 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
        <?php if ($userRole === 'Super Admin'): ?>
            <a href="<?= BASE_PATH ?>/admin/dashboard.php"
                class="flex flex-col items-center justify-center gap-1 h-full w-16 <?= is_active(BASE_PATH . '/admin/dashboard.php', $current_page) ?: 'text-slate-400' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <span class="text-[10px] font-medium">Home</span>
            </a>
            <a href="<?= BASE_PATH ?>/admin/roster.php"
                class="flex flex-col items-center justify-center gap-1 h-full w-16 <?= is_active(BASE_PATH . '/admin/roster.php', $current_page) ?: 'text-slate-400' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span class="text-[10px] font-medium">Roster</span>
            </a>
            <a href="<?= BASE_PATH ?>/admin/reports.php"
                class="flex flex-col items-center justify-center gap-1 h-full w-16 <?= is_active(BASE_PATH . '/admin/reports.php', $current_page) ?: 'text-slate-400' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <span class="text-[10px] font-medium">Reports</span>
            </a>
            <a href="<?= BASE_PATH ?>/admin/settings.php"
                class="flex flex-col items-center justify-center gap-1 h-full w-16 <?= is_active(BASE_PATH . '/admin/settings.php', $current_page) ?: 'text-slate-400' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                <span class="text-[10px] font-medium">Settings</span>
            </a>
        <?php elseif ($userRole === 'Tent Admin'): ?>
            <a href="<?= BASE_PATH ?>/tent/dashboard.php"
                class="flex flex-col items-center justify-center gap-1 h-full w-16 <?= is_active(BASE_PATH . '/tent/dashboard.php', $current_page) ?: 'text-slate-400' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <span class="text-[10px] font-medium">Home</span>
            </a>

            <!-- Floating FAB for Attendance -->
            <div class="relative -top-6">
                <a href="<?= BASE_PATH ?>/tent/attendance.php"
                    class="bg-[#00BD06] text-white p-4 rounded-full shadow-xl shadow-green-500/40 active:scale-95 transition-all flex items-center justify-center w-14 h-14">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </a>
            </div>

            <a href="<?= BASE_PATH ?>/tent/members.php"
                class="flex flex-col items-center justify-center gap-1 h-full w-16 <?= is_active(BASE_PATH . '/tent/members.php', $current_page) ?: 'text-slate-400' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span class="text-[10px] font-medium">Members</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- Main Content Wrapper (Used by parent pages) -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">