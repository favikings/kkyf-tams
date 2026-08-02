<?php

declare(strict_types=1);

/**
 * Minimal pure-PHP .xlsx reader for the import wizard — reads the first worksheet
 * only and returns every row as a list of string cell values. Zero Composer
 * dependencies: only built-in PHP extensions (ZipArchive, XMLReader, DOM).
 *
 * Limitations (acceptable for an import tool, see DECISIONS):
 * - Cells whose style is a date/time number format (built-in or custom, read from
 *   xl/styles.xml) are converted from their raw Excel serial number to a "Y-m-d"
 *   string — see importXlsxDateStyleIndexes()/importXlsxSerialToDate(). Cells that
 *   are numeric but NOT date-styled are still returned as raw numbers.
 * - Cells stored as numbers drop leading zeros (a phone like "0801…" saved as a
 *   number becomes "801…") — format the phone column as text in Excel.
 * - Only the first worksheet is read; merged cells keep only their top-left value.
 */

/**
 * Returns the path (inside the zip) of the first worksheet, e.g. "xl/worksheets/sheet1.xml".
 */
function importXlsxSheetPath(ZipArchive $zip): string
{
    $workbook = (string) $zip->getFromName('xl/workbook.xml');
    if ($workbook === '') {
        return 'xl/worksheets/sheet1.xml';
    }

    $relId = null;
    if (preg_match('/<sheet\b[^>]*\br:id="([^"]+)"/', $workbook, $m) === 1) {
        $relId = $m[1];
    }

    if ($relId !== null) {
        $rels = (string) $zip->getFromName('xl/_rels/workbook.xml.rels');
        $relsXml = $rels !== '' ? @simplexml_load_string($rels, 'SimpleXMLElement', LIBXML_NONET) : false;
        if ($relsXml !== false) {
            foreach ($relsXml->Relationship as $relationship) {
                if ((string) $relationship['Id'] !== $relId) {
                    continue;
                }
                $target = (string) $relationship['Target'];
                // Target is either package-root-relative ("/xl/worksheets/sheet1.xml")
                // or relative to xl/ ("worksheets/sheet1.xml") — writers disagree, and
                // attribute order (Id vs Target first) also varies, hence SimpleXML
                // over a fixed-order regex.
                return str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/' . $target;
            }
        }
    }

    return 'xl/worksheets/sheet1.xml';
}

/**
 * Returns the shared strings table as a list of decoded strings (empty when the
 * workbook stores strings inline).
 */
function importXlsxSharedStrings(ZipArchive $zip): array
{
    $xml = (string) $zip->getFromName('xl/sharedStrings.xml');
    if ($xml === '') {
        return [];
    }

    $strings = [];
    $reader = new XMLReader();
    if (!$reader->XML($xml, null, LIBXML_NONET)) {
        return [];
    }

    while ($reader->read()) {
        if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'si') {
            $si = $reader->readOuterXML();
            preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $si, $runs);
            $strings[] = html_entity_decode(implode('', $runs[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
        }
    }
    $reader->close();

    return $strings;
}

/**
 * Returns the set of cellXfs style indexes (0-based, matching a cell's `s`
 * attribute) that represent a date/time number format, by reading xl/styles.xml.
 * Keys are the style index; values are always true (used as a lookup set).
 */
function importXlsxDateStyleIndexes(ZipArchive $zip): array
{
    $xml = (string) $zip->getFromName('xl/styles.xml');
    if ($xml === '') {
        return [];
    }

    $styles = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET);
    if ($styles === false) {
        return [];
    }

    // Built-in date/time numFmtIds per ECMA-376 Part 1 §18.8.30.
    $builtinDateIds = [14, 15, 16, 17, 18, 19, 20, 21, 22, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 45, 46, 47, 50, 51, 52, 53, 54, 55, 56, 57, 58];

    $customDateIds = [];
    if (isset($styles->numFmts->numFmt)) {
        foreach ($styles->numFmts->numFmt as $numFmt) {
            if (importXlsxIsDateFormatCode((string) $numFmt['formatCode'])) {
                $customDateIds[(int) $numFmt['numFmtId']] = true;
            }
        }
    }

    $dateStyles = [];
    if (isset($styles->cellXfs->xf)) {
        $index = 0;
        foreach ($styles->cellXfs->xf as $xf) {
            $numFmtId = (int) $xf['numFmtId'];
            if (in_array($numFmtId, $builtinDateIds, true) || isset($customDateIds[$numFmtId])) {
                $dateStyles[$index] = true;
            }
            $index++;
        }
    }

    return $dateStyles;
}

/**
 * Heuristic: does this custom number-format code represent a date/time?
 * Strips bracketed sections ([Red], [$-409]) and quoted literal text, then
 * checks for date/time tokens — rejecting percent/text formats that happen
 * to contain a stray "d"/"m"/etc. via the [Red]/quote stripping above.
 */
function importXlsxIsDateFormatCode(string $code): bool
{
    $code = strtolower($code);
    $code = preg_replace('/\[[^\]]*\]/', '', $code) ?? $code;
    $code = preg_replace('/"[^"]*"/', '', $code) ?? $code;

    if ($code === '' || $code === 'general') {
        return false;
    }
    if (str_contains($code, '%') || str_contains($code, '@')) {
        return false;
    }

    return (bool) preg_match('/[ymdhs]/', $code);
}

/**
 * Converts an Excel date serial number (days since the Excel epoch) to a
 * "Y-m-d" string. Uses the standard 1899-12-30 epoch, which is correct for
 * any real date from March 1900 onward — the only range this app ever needs
 * (member birthdates) — without bothering to replicate Excel's fictitious
 * Feb 29, 1900 leap-year bug for the handful of days before that.
 */
function importXlsxSerialToDate(float $serial): ?string
{
    $days = (int) floor($serial);
    if ($days < 61) {
        return null;
    }

    return (new DateTimeImmutable('1899-12-30'))->modify('+' . $days . ' days')->format('Y-m-d');
}

/**
 * Reads the first worksheet and returns its cells as rows of string values.
 * Empty rows are skipped; short rows are padded so all rows share one width.
 */
function importReadXlsxRows(string $filePath): array
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('XLSX support requires the PHP zip extension. Convert the file to CSV and try again.');
    }

    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        throw new RuntimeException('Could not open the .xlsx file.');
    }

    $sheetPath = importXlsxSheetPath($zip);
    $shared = importXlsxSharedStrings($zip);
    $dateStyles = importXlsxDateStyleIndexes($zip);
    $sheetXml = (string) $zip->getFromName($sheetPath);
    $zip->close();

    if ($sheetXml === '') {
        throw new RuntimeException('Could not read the worksheet from the .xlsx file.');
    }

    $rows = [];
    $maxCol = 0;

    // Read the worksheet from the in-memory string (XMLReader::XML(), same as
    // importXlsxSharedStrings()) rather than writing it to a temp file first —
    // sys_get_temp_dir() is not reliably writable by the web server user on
    // every host (confirmed locally: the daemon user running Apache/PHP could
    // not write there, silently breaking every .xlsx import).
    $reader = new XMLReader();
    if (!$reader->XML($sheetXml, null, LIBXML_NONET)) {
        throw new RuntimeException('Could not parse the worksheet.');
    }

    while ($reader->read()) {
        if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
            continue;
        }

        $row = importXlsxRow($reader->readOuterXML(), $shared, $dateStyles);
        if ($row !== []) {
            $rows[] = $row;
            $maxCol = max($maxCol, count($row));
        }
    }
    $reader->close();

    foreach ($rows as &$row) {
        $row = array_pad($row, $maxCol, '');
    }
    unset($row);

    return $rows;
}

/**
 * Parses a single <row>…</row> XML string into a sequential cell list.
 * Cells are placed at their letter-derived column index so sparse rows stay aligned.
 * The fragment carries no namespace (it came from readOuterXML of the sheet's
 * default-namespaced row), so plain SimpleXML access works.
 */
function importXlsxRow(string $rowXml, array $shared, array $dateStyles = []): array
{
    $xml = @simplexml_load_string($rowXml, 'SimpleXMLElement', LIBXML_NONET);
    if ($xml === false) {
        return [];
    }

    $cells = [];
    foreach ($xml->c as $cell) {
        $ref = (string) $cell['r'];
        $col = importXlsxColumnIndex(preg_replace('/[0-9]/', '', $ref));
        $type = (string) $cell['t'];

        if ($type === 's') {
            $value = $shared[(int) (string) $cell->v] ?? '';
        } elseif ($type === 'inlineStr') {
            $value = '';
            if (isset($cell->is)) {
                foreach ($cell->is->t as $run) {
                    $value .= (string) $run;
                }
            }
        } else {
            $value = (string) $cell->v;
            $styleIndex = $cell['s'] !== null ? (int) $cell['s'] : null;
            if ($value !== '' && $styleIndex !== null && isset($dateStyles[$styleIndex]) && is_numeric($value)) {
                $converted = importXlsxSerialToDate((float) $value);
                if ($converted !== null) {
                    $value = $converted;
                }
            }
        }

        if ($value === '') {
            continue;
        }
        $cells[$col] = $value;
    }

    if ($cells === []) {
        return [];
    }

    ksort($cells);
    $row = array_fill(0, max(array_keys($cells)) + 1, '');
    foreach ($cells as $col => $value) {
        $row[$col] = $value;
    }

    return $row;
}

/**
 * Converts an Excel column label ("A", "B", …, "AA", …) to a 0-based index.
 */
function importXlsxColumnIndex(string $letters): int
{
    $index = 0;
    $len = strlen($letters);
    for ($i = 0; $i < $len; $i++) {
        $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
    }
    return $index > 0 ? $index - 1 : 0;
}
