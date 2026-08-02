<?php

declare(strict_types=1);

/**
 * Shared helpers for the member import wizard (public/import.php).
 * Pure logic only — API endpoints pull this in after auth.php (which provides
 * validateMember() via functions.php and db() via config).
 */

/**
 * Reads a CSV file (streaming, fgetcsv) into rows of string values.
 * Strips a UTF-8 BOM, detects comma/semicolon/tab delimiter from the first line,
 * and coerces non-UTF-8 input (e.g. Excel "CSV (Comma delimited)") to UTF-8.
 */
function importReadCsvRows(string $filePath): array
{
    $handle = fopen($filePath, 'rb');
    if ($handle === false) {
        throw new RuntimeException('Could not open the CSV file.');
    }

    $delimiter = ',';
    $firstLine = fgets($handle);
    rewind($handle);

    if ($firstLine !== false) {
        $probe = importStripBom($firstLine);
        $counts = [
            ',' => substr_count($probe, ','),
            ';' => substr_count($probe, ';'),
            "\t" => substr_count($probe, "\t"),
        ];
        arsort($counts);
        $delimiter = array_key_first($counts) ?: ',';
    }

    $rows = [];
    $lineNo = 0;
    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
        if ($lineNo === 0) {
            $row[0] = importStripBom((string) ($row[0] ?? ''));
        }
        $rows[] = array_map('importUtf8', $row);
        $lineNo++;
    }

    fclose($handle);

    return $rows;
}

function importStripBom(string $value): string
{
    if (str_starts_with($value, "\xEF\xBB\xBF")) {
        return substr($value, 3);
    }

    return $value;
}

function importUtf8(string $value): string
{
    if (mb_check_encoding($value, 'UTF-8')) {
        return $value;
    }

    return mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
}

/**
 * Validates the column mapping (field name → column index). Returns a list of
 * error strings (empty when valid). Full Name must be mapped (Phone is
 * optional — not every member has one), no field may map more than once, and
 * no column may feed two fields.
 */
function importValidateMapping(array $mapping, array $columns): array
{
    $errors = [];
    $allowed = ['full_name', 'phone', 'date_of_birth', 'birth_month', 'birth_day', 'occupation', 'school_name'];
    $fieldCounts = [];
    $colCounts = [];

    foreach ($mapping as $field => $colIdx) {
        if (!in_array($field, $allowed, true)) {
            $errors[] = 'Unknown field: ' . $field;
            continue;
        }
        $colIdx = (int) $colIdx;
        if ($colIdx < 0 || $colIdx >= count($columns)) {
            $errors[] = 'Column index out of range for ' . $field;
            continue;
        }
        $fieldCounts[$field] = ($fieldCounts[$field] ?? 0) + 1;
        $colCounts[$colIdx] = ($colCounts[$colIdx] ?? 0) + 1;
    }

    foreach ($fieldCounts as $field => $count) {
        if ($count > 1) {
            $errors[] = 'Field "' . $field . '" is mapped to more than one column.';
        }
    }
    foreach ($colCounts as $colIdx => $count) {
        if ($count > 1) {
            $errors[] = 'Column ' . ($colIdx + 1) . ' is used for more than one field.';
        }
    }

    if (!isset($mapping['full_name'])) {
        $errors[] = 'Full Name must be mapped to a column.';
    }
    if (isset($mapping['date_of_birth']) && (isset($mapping['birth_month']) || isset($mapping['birth_day']))) {
        $errors[] = 'Map either Date of Birth or separate Birth Month/Birth Day columns, not both.';
    }

    return array_values(array_unique($errors));
}

/**
 * Builds the input array for validateMember() from one parsed row + the mapping.
 * Occupation is optional on import: blank/unmapped becomes 'worker' (the DB
 * default) so the column can be left out entirely.
 *
 * If a "Date of Birth" column is mapped, it's split into birth_month/birth_day
 * here (importValidateMapping() already rejects mapping it alongside separate
 * Birth Month/Birth Day columns, so at most one source is active). A DOB value
 * that doesn't parse is silently dropped — the member still imports, just
 * without a birthday, rather than the whole row being rejected over an
 * optional field.
 */
function importBuildRowInput(array $row, array $mapping, int $tentId): array
{
    $get = static function (string $field) use ($row, $mapping): string {
        if (!isset($mapping[$field])) {
            return '';
        }
        return (string) ($row[(int) $mapping[$field]] ?? '');
    };

    $occupation = strtolower(trim($get('occupation')));
    if ($occupation === '') {
        $occupation = 'worker';
    }

    $birthMonth = $get('birth_month');
    $birthDay = $get('birth_day');

    if (isset($mapping['date_of_birth'])) {
        $dob = importParseDobString($get('date_of_birth'));
        if ($dob !== null) {
            $birthMonth = (string) $dob['month'];
            $birthDay = (string) $dob['day'];
        }
    }

    return [
        'full_name' => $get('full_name'),
        'phone' => $get('phone'),
        'occupation' => $occupation,
        'school_name' => $get('school_name'),
        'birth_month' => $birthMonth,
        'birth_day' => $birthDay,
        'tent_id' => $tentId,
    ];
}

/**
 * Parses a "Date of Birth" cell into ['month' => int, 'day' => int], or null
 * if unparseable. Only accepts unambiguous formats — ISO "Y-m-d"/"Y/m/d" (what
 * importXlsxSerialToDate() emits for date-styled Excel cells), or formats that
 * spell the month out ("14-Mar-2010", "March 14, 2010", ...). Deliberately does
 * NOT attempt all-numeric formats like "3/14/2010" or "14/3/2010" — guessing
 * wrong between month-first and day-first would silently swap someone's
 * birthday, which is worse than just leaving it blank.
 */
function importParseDobString(string $value): ?array
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    $formats = [
        'Y-m-d', 'Y/m/d',
        'd-M-Y', 'd/M/Y', 'd M Y', 'd F Y', 'd-F-Y',
        'M d, Y', 'M d Y', 'F d, Y', 'F d Y',
    ];

    foreach ($formats as $format) {
        $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
        if ($date === false) {
            continue;
        }
        // getLastErrors() returns false (not a zero-count array) when nothing
        // went wrong — only treat it as a rejection when it's an array reporting
        // an actual warning/error (e.g. "Feb 30" parsing but landing on Mar 2).
        $errors = DateTimeImmutable::getLastErrors();
        if (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            continue;
        }
        return ['month' => (int) $date->format('n'), 'day' => (int) $date->format('j')];
    }

    return null;
}
