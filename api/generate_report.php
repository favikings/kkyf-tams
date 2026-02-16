<?php
// api/generate_report.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

checkAuth('Super Admin');

$type = $_GET['type'] ?? 'annual';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('n');

$reportData = [];
$reportTitle = "";

if ($type === 'annual') {
    $reportTitle = "Annual Performance Report - $year";

    // Aggregated stats per tent for the year
    $sql = "
        SELECT 
            t.Tent_Name,
            COUNT(al.Log_ID) as Total_Attendance,
            SUM(CASE WHEN al.Is_First_Timer = 1 THEN 1 ELSE 0 END) as Total_First_Timers
        FROM Tents t
        LEFT JOIN Attendance_Log al ON t.Tent_ID = al.Tent_ID 
            AND YEAR(al.Attendance_Date) = ?
        GROUP BY t.Tent_ID, t.Tent_Name
        ORDER BY Total_Attendance DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$year]);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($type === 'monthly') {
    $monthName = date('F', mktime(0, 0, 0, $month, 1));
    $reportTitle = "Monthly Detail Summary - $monthName $year";

    // 1. Identify all Sundays in the selected month/year
    $sundays = [];
    $firstDay = "$year-$month-01";
    $lastDay = date('Y-m-t', strtotime($firstDay));

    $current = new DateTime($firstDay);
    $end = new DateTime($lastDay);

    while ($current <= $end) {
        if ($current->format('w') == 0) { // 0 = Sunday
            $sundays[] = $current->format('Y-m-d');
        }
        $current->modify('+1 day');
    }

    // 2. Pivot Table: Tents vs Sundays
    $sundayColumns = "";
    foreach ($sundays as $date) {
        $shortDate = date('M j', strtotime($date));
        $sundayColumns .= ", SUM(CASE WHEN al.Attendance_Date = '$date' THEN 1 ELSE 0 END) as '$shortDate'";
    }

    $sql = "
        SELECT 
            t.Tent_Name
            $sundayColumns,
            COUNT(al.Log_ID) as Monthly_Total
        FROM Tents t
        LEFT JOIN Attendance_Log al ON t.Tent_ID = al.Tent_ID 
            AND MONTH(al.Attendance_Date) = ? 
            AND YEAR(al.Attendance_Date) = ?
        GROUP BY t.Tent_ID, t.Tent_Name
        ORDER BY t.Tent_Name ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$month, $year]);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $reportData['sundays'] = $sundays;

} elseif ($type === 'weekly') {
    $date = $_GET['date'] ?? date('Y-m-d');
    $reportTitle = "Weekly Performance Report - " . date('F j, Y', strtotime($date));

    // Fetch stats for specific date for all tents
    // If no log exists, it will show as NULL/0
    $sql = "
        SELECT 
            t.Tent_Name,
            COUNT(al.Log_ID) as Total_Attendance,
            SUM(CASE WHEN al.Is_First_Timer = 1 THEN 1 ELSE 0 END) as Total_First_Timers,
            CASE WHEN COUNT(al.Log_ID) > 0 THEN 'Active' ELSE 'No Report' END as Status
        FROM Tents t
        LEFT JOIN Attendance_Log al ON t.Tent_ID = al.Tent_ID 
            AND al.Attendance_Date = ?
        GROUP BY t.Tent_ID, t.Tent_Name
        ORDER BY Total_Attendance DESC, t.Tent_Name ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date]);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// FOR NOW: We present a "Print-Optimized" HTML view. 
// This is more visually consistent with the PWA. 
// The user can "Print to PDF" from the browser.
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>
        <?= $reportTitle ?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print {
                display: none;
            }

            body {
                background: white;
                padding: 0;
            }

            .print-container {
                box-shadow: none;
                border: none;
                width: 100%;
                max-width: none;
            }
        }
    </style>
</head>

<body class="bg-slate-50 py-10 px-4">

    <div class="max-w-4xl mx-auto bg-white shadow-xl rounded-2xl overflow-hidden print-container">
        <!-- Report Header -->
        <div class="bg-slate-900 p-8 text-white flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">
                    <?= $reportTitle ?>
                </h1>
                <p class="text-slate-400 text-sm mt-1">Generated on
                    <?= date('M j, Y \a\t H:i') ?>
                </p>
            </div>
            <div class="text-right">
                <p class="text-xs font-bold uppercase tracking-widest text-[#00BD06]">KKYF FELLOWSHIP</p>
                <p class="text-xs text-slate-500">Management System</p>
            </div>
        </div>

        <div class="p-8">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b-2 border-slate-100">
                            <th class="py-4 font-bold text-slate-500 uppercase text-xs">Fellowship Tent</th>
                            <?php if ($type === 'annual'): ?>
                                <th class="py-4 font-bold text-slate-500 uppercase text-xs text-center">Total Attendance</th>
                                <th class="py-4 font-bold text-slate-500 uppercase text-xs text-center">First Timers</th>
                                <th class="py-4 font-bold text-slate-500 uppercase text-xs text-center">Conversion %</th>
                            <?php elseif ($type === 'weekly'): ?>
                                <th class="py-4 font-bold text-slate-500 uppercase text-xs text-center">Status</th>
                                <th class="py-4 font-bold text-slate-500 uppercase text-xs text-center">Attendance</th>
                                <th class="py-4 font-bold text-slate-500 uppercase text-xs text-center">First Timers</th>
                                <th class="py-4 font-bold text-slate-500 uppercase text-xs text-center">% of Total</th>
                            <?php else: ?>
                                <?php foreach ($reportData['sundays'] as $date): ?>
                                    <th class="py-4 font-bold text-slate-500 uppercase text-xs text-center">
                                        <?= date('M j', strtotime($date)) ?>
                                    </th>
                                <?php endforeach; ?>
                                <th class="py-4 font-bold text-slate-900 uppercase text-xs text-center">Total</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php
                        $grandTotal = 0;
                        // Calculate Grand Total first for % in weekly
                        if ($type === 'weekly') {
                            foreach ($reportData as $row) $grandTotal += $row['Total_Attendance'];
                        }

                        foreach ($reportData as $key => $row):
                            if ($key === 'sundays') continue;
                            if ($type !== 'weekly') $grandTotal += ($type === 'annual' ? $row['Total_Attendance'] : $row['Monthly_Total']);
                            ?>
                            <tr>
                                <td class="py-4 font-bold text-slate-800">
                                    <?= htmlspecialchars($row['Tent_Name']) ?>
                                </td>
                                <?php if ($type === 'annual'): ?>
                                    <td class="py-4 text-center text-slate-600 font-medium"><?= number_format($row['Total_Attendance']) ?></td>
                                    <td class="py-4 text-center text-slate-600"><?= number_format($row['Total_First_Timers']) ?></td>
                                    <td class="py-4 text-center">
                                        <?php
                                        $conv = ($row['Total_Attendance'] > 0) ? ($row['Total_First_Timers'] / $row['Total_Attendance']) * 100 : 0;
                                        $color = $conv > 10 ? 'text-[#00BD06]' : 'text-slate-400';
                                        ?>
                                        <span class="font-bold <?= $color ?>"><?= number_format($conv, 1) ?>%</span>
                                    </td>
                                <?php elseif ($type === 'weekly'): ?>
                                    <td class="py-4 text-center text-xs">
                                        <span class="px-2 py-1 rounded-full font-bold <?= $row['Status'] === 'Active' ? 'bg-green-50 text-green-600' : 'bg-slate-100 text-slate-400' ?>">
                                            <?= $row['Status'] ?>
                                        </span>
                                    </td>
                                    <td class="py-4 text-center text-slate-900 font-bold"><?= number_format($row['Total_Attendance']) ?></td>
                                    <td class="py-4 text-center text-slate-600"><?= number_format($row['Total_First_Timers']) ?></td>
                                    <td class="py-4 text-center text-slate-500 text-xs">
                                        <?= ($grandTotal > 0) ? number_format(($row['Total_Attendance'] / $grandTotal) * 100, 1) . '%' : '-' ?>
                                    </td>
                                <?php else: ?>
                                    <?php foreach ($reportData['sundays'] as $date): ?>
                                        <?php $col = date('M j', strtotime($date)); ?>
                                        <td class="py-4 text-center text-slate-600"><?= number_format($row[$col]) ?></td>
                                    <?php endforeach; ?>
                                    <td class="py-4 text-center font-bold text-slate-900 bg-slate-50/50"><?= number_format($row['Monthly_Total']) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-900 text-white">
                            <td class="py-4 px-4 font-bold rounded-bl-xl">GRAND TOTAL</td>
                            <?php if ($type === 'annual'): ?>
                                <td class="py-4 text-center font-bold" colspan="2"><?= number_format($grandTotal) ?> Attendees</td>
                                <td class="py-4 rounded-br-xl"></td>
                            <?php elseif ($type === 'weekly'): ?>
                                <td class="py-4 text-center font-bold" colspan="1"></td> <!-- Empty for status -->
                                <td class="py-4 text-center font-bold"><?= number_format($grandTotal) ?></td>
                                <td class="py-4 rounded-br-xl" colspan="2"></td>
                            <?php else: ?>
                                <td class="py-4 text-center font-bold" colspan="<?= count($reportData['sundays']) + 1 ?>"><?= number_format($grandTotal) ?> Monthly Attendance</td>
                            <?php endif; ?>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Footer Note -->
            <div class="mt-12 pt-8 border-t border-slate-100 flex justify-between items-end">
                <div class="text-sm text-slate-400">
                    <p>Verified Administrative Document</p>
                    <p>KKYF Fellowship Registry</p>
                </div>
                <div class="no-print">
                    <button onclick="window.print()"
                        class="bg-[#00BD06] text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-green-500/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 6 2 18 2 18 9" />
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                            <rect width="12" height="8" x="6" y="14" />
                        </svg>
                        Print Repoert / Save as PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Admin link in print-hidden -->
    <div class="max-w-4xl mx-auto mt-6 no-print text-center">
        <a href="<?= BASE_PATH ?>/admin/reports.php" class="text-slate-400 hover:text-slate-600 font-medium">← Back to
            Reports Dashboard</a>
    </div>

</body>

</html>