<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\ReportService;
use App\Services\TentService;

final class ReportController
{
    private ReportService $reports;
    private TentService $tents;

    public function __construct()
    {
        $this->reports = new ReportService();
        $this->tents = new TentService();
    }

    public function index(): string
    {
        AuthMiddleware::requireAuth();

        $user = AuthService::user() ?? [];
        $type = trim((string) ($_GET['type'] ?? 'weekly'));
        $tentId = (int) ($_GET['tent_id'] ?? 0);
        $dateFrom = trim((string) ($_GET['date_from'] ?? ''));
        $dateTo = trim((string) ($_GET['date_to'] ?? ''));

        return View::render('reports/index', [
            'title' => 'Reports',
            'user' => $user,
            'csrfToken' => Csrf::token(),
            'report' => $this->reports->build($user, $type, $tentId > 0 ? $tentId : null, $dateFrom ?: null, $dateTo ?: null),
            'tents' => $this->tents->availableForUser($user),
        ]);
    }

    public function print(): string
    {
        AuthMiddleware::requireAuth();

        $user = AuthService::user() ?? [];
        $type = trim((string) ($_GET['type'] ?? 'weekly'));
        $tentId = (int) ($_GET['tent_id'] ?? 0);
        $dateFrom = trim((string) ($_GET['date_from'] ?? ''));
        $dateTo = trim((string) ($_GET['date_to'] ?? ''));

        return View::render('reports/print', [
            'title' => 'Print Report',
            'user' => $user,
            'report' => $this->reports->build($user, $type, $tentId > 0 ? $tentId : null, $dateFrom ?: null, $dateTo ?: null),
        ]);
    }

    public function export(): string
    {
        AuthMiddleware::requireAuth();

        $user = AuthService::user() ?? [];
        $type = trim((string) ($_GET['type'] ?? 'weekly'));
        $tentId = (int) ($_GET['tent_id'] ?? 0);
        $dateFrom = trim((string) ($_GET['date_from'] ?? ''));
        $dateTo = trim((string) ($_GET['date_to'] ?? ''));
        $format = trim((string) ($_GET['format'] ?? 'excel'));
        $report = $this->reports->build($user, $type, $tentId > 0 ? $tentId : null, $dateFrom ?: null, $dateTo ?: null);

        if ($format === 'pdf') {
            $this->sendPdf($report);

            return '';
        }

        $this->sendExcel($report);

        return '';
    }

    public function exportAll(): string
    {
        AuthMiddleware::requireAuth();

        $user = AuthService::user() ?? [];
        $format = trim((string) ($_GET['format'] ?? 'excel'));
        $rows = $this->reports->allAttendanceRows($user);

        if ($format === 'csv') {
            $this->sendAllCsv($rows);

            return '';
        }

        $this->sendAllExcel($rows);

        return '';
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function sendAllCsv(array $rows): void
    {
        $filename = 'all-attendance-' . date('Ymd-His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, ['Date', 'Tent', 'Member', 'Phone', 'Service', 'Checked By', 'Source', 'Recorded At']);
        foreach ($rows as $row) {
            fputcsv($output, $this->exportAllRow($row));
        }

        fclose($output);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function sendAllExcel(array $rows): void
    {
        $filename = 'all-attendance-' . date('Ymd-His') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $lines = [];
        $lines[] = implode("\t", ['Date', 'Tent', 'Member', 'Phone', 'Service', 'Checked By', 'Source', 'Recorded At']);

        foreach ($rows as $row) {
            $lines[] = implode("\t", $this->exportAllRow($row));
        }

        echo implode("\r\n", $lines);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<int, string>
     */
    private function exportAllRow(array $row): array
    {
        return [
            trim((string) ($row['attendance_date'] ?? '')),
            trim((string) ($row['tent_name'] ?? '')),
            trim((string) ($row['full_name'] ?? '')),
            trim((string) ($row['phone'] ?? '')),
            trim((string) ($row['service_type'] ?? '')),
            trim((string) ($row['checked_by_name'] ?? '')),
            ucfirst(trim((string) ($row['source'] ?? 'web'))),
            trim((string) ($row['created_at'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $report
     */
    private function sendExcel(array $report): void
    {
        $filename = $this->slug((string) ($report['title'] ?? 'report')) . '-' . date('Ymd-His') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $lines = [];
        $lines[] = (string) ($report['title'] ?? 'Report');
        $lines[] = 'Date Window' . "\t" . (string) ($report['date_from'] ?? '') . ' to ' . (string) ($report['date_to'] ?? '');
        $summary = (array) ($report['summary'] ?? []);
        $lines[] = 'Total Check-ins' . "\t" . (string) ($summary['total_checkins'] ?? 0);
        $lines[] = 'Unique Members' . "\t" . (string) ($summary['unique_members'] ?? 0);
        $lines[] = 'Tents Reached' . "\t" . (string) ($summary['tents_reached'] ?? 0);
        $lines[] = 'Service Days' . "\t" . (string) ($summary['service_days'] ?? 0);
        $lines[] = 'Average Daily Attendance' . "\t" . (string) ($summary['average_daily_attendance'] ?? 0);
        $lines[] = '';
        $columns = (array) ($report['columns'] ?? []);
        $lines[] = implode("\t", $columns);

        foreach ((array) ($report['rows'] ?? []) as $row) {
            if ((string) ($report['type'] ?? '') === 'sunday') {
                $lines[] = implode("\t", [
                    (string) ($row['full_name'] ?? ''),
                    (string) ($row['tent_name'] ?? ''),
                    (string) ($row['phone'] ?? ''),
                    (string) ($row['checked_by_name'] ?? ''),
                    (string) ($row['source'] ?? ''),
                ]);
                continue;
            }

            $lines[] = implode("\t", [
                (string) ($row['attendance_date'] ?? ''),
                (string) ($row['tent_name'] ?? ''),
                (string) ($row['total_checkins'] ?? 0),
                (string) ($row['unique_members'] ?? 0),
            ]);
        }

        echo implode("\r\n", $lines);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function sendPdf(array $report): void
    {
        $filename = $this->slug((string) ($report['title'] ?? 'report')) . '-' . date('Ymd-His') . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $lines = [];
        $lines[] = (string) ($report['title'] ?? 'Report');
        $lines[] = 'Date Window: ' . (string) ($report['date_from'] ?? '') . ' to ' . (string) ($report['date_to'] ?? '');
        $summary = (array) ($report['summary'] ?? []);
        $lines[] = 'Total Check-ins: ' . (string) ($summary['total_checkins'] ?? 0);
        $lines[] = 'Unique Members: ' . (string) ($summary['unique_members'] ?? 0);
        $lines[] = 'Tents Reached: ' . (string) ($summary['tents_reached'] ?? 0);
        $lines[] = 'Service Days: ' . (string) ($summary['service_days'] ?? 0);
        $lines[] = 'Average Daily Attendance: ' . (string) ($summary['average_daily_attendance'] ?? 0);
        $lines[] = '';

        foreach ((array) ($report['rows'] ?? []) as $row) {
            if ((string) ($report['type'] ?? '') === 'sunday') {
                $lines[] = trim(sprintf(
                    '%s | %s | %s | %s | %s',
                    (string) ($row['full_name'] ?? ''),
                    (string) ($row['tent_name'] ?? ''),
                    (string) ($row['phone'] ?? ''),
                    (string) ($row['checked_by_name'] ?? ''),
                    (string) ($row['source'] ?? '')
                ));
                continue;
            }

            $lines[] = trim(sprintf(
                '%s | %s | %s check-ins | %s members',
                (string) ($row['attendance_date'] ?? ''),
                (string) ($row['tent_name'] ?? ''),
                (string) ($row['total_checkins'] ?? 0),
                (string) ($row['unique_members'] ?? 0)
            ));
        }

        echo $this->simplePdf($lines);
    }

    /**
     * @param array<int, string> $lines
     */
    private function simplePdf(array $lines): string
    {
        $escaped = array_map(function (string $line): string {
            $line = str_replace('\\', '\\\\', $line);
            $line = str_replace('(', '\\(', $line);
            $line = str_replace(')', '\\)', $line);

            return preg_replace('/[^\x20-\x7E]/', '', $line) ?? '';
        }, $lines);

        $content = "BT\n/F1 11 Tf\n50 790 Td\n14 TL\n";
        foreach ($escaped as $index => $line) {
            if ($index > 0) {
                $content .= "T*\n";
            }
            $content .= '(' . $line . ") Tj\n";
        }
        $content .= "ET";

        $objects = [];
        $objects[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj";
        $objects[] = "2 0 obj << /Type /Pages /Count 1 /Kids [3 0 R] >> endobj";
        $objects[] = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj";
        $objects[] = "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj";
        $objects[] = "5 0 obj << /Length " . strlen($content) . " >> stream\n" . $content . "\nendstream endobj";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object . "\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i]) . "\n";
        }

        $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    private function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? 'report';

        return trim($value, '-') ?: 'report';
    }
}
