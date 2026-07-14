<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This tool must be run from the command line.\n");
    exit(1);
}

$sourcePath = $argv[1] ?? null;

if ($sourcePath === null || !is_file($sourcePath)) {
    fwrite(STDERR, "Usage: php database/import_legacy_sources.php C:\\path\\to\\legacy_dump.sql\n");
    exit(1);
}

$sql = file_get_contents($sourcePath);

if ($sql === false) {
    fwrite(STDERR, "Could not read SQL dump.\n");
    exit(1);
}

$pdo = new PDO(
    'mysql:host=127.0.0.1;port=3306;dbname=kkyf_tams_v2_dev;charset=utf8mb4',
    'root',
    '',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
    ]
);

$imports = [
    'Tents' => 'legacy_tents',
    'Members' => 'legacy_members',
];

foreach ($imports as $legacyTable => $targetTable) {
    $pdo->exec("DROP TABLE IF EXISTS `{$targetTable}`");

    $section = extractTableSection($sql, $legacyTable);
    $section = str_replace("`{$legacyTable}`", "`{$targetTable}`", $section);
    executeStatements($pdo, $section);

    foreach (extractUsefulAlterStatements($sql, $legacyTable, $targetTable) as $statement) {
        $pdo->exec($statement);
    }

    $count = (int) $pdo->query("SELECT COUNT(*) FROM `{$targetTable}`")->fetchColumn();
    echo "Imported {$targetTable}: {$count} rows\n";
}

function extractTableSection(string $sql, string $table): string
{
    $startMarker = "-- Table structure for table `{$table}`";
    $start = strpos($sql, $startMarker);

    if ($start === false) {
        throw new RuntimeException("Could not find table section for {$table}.");
    }

    $next = strpos($sql, "-- Table structure for table `", $start + strlen($startMarker));
    $indexes = strpos($sql, "-- Indexes for dumped tables", $start + strlen($startMarker));

    $endCandidates = array_filter([$next, $indexes], static fn ($position): bool => $position !== false);
    $end = $endCandidates === [] ? strlen($sql) : min($endCandidates);

    return substr($sql, $start, $end - $start);
}

function extractUsefulAlterStatements(string $sql, string $table, string $targetTable): array
{
    preg_match_all('/ALTER TABLE `' . preg_quote($table, '/') . '`\s+.*?;/s', $sql, $matches);

    $statements = [];

    foreach ($matches[0] as $statement) {
        if (stripos($statement, 'ADD CONSTRAINT') !== false) {
            continue;
        }

        $statements[] = str_replace("`{$table}`", "`{$targetTable}`", $statement);
    }

    return $statements;
}

function executeStatements(PDO $pdo, string $sql): void
{
    $sql = stripSqlCommentLines($sql);

    foreach (splitSqlStatements($sql) as $statement) {
        $trimmed = trim($statement);

        if ($trimmed === '') {
            continue;
        }

        $pdo->exec($trimmed);
    }
}

function stripSqlCommentLines(string $sql): string
{
    $lines = preg_split('/\R/', $sql) ?: [];
    $kept = [];

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*!')) {
            continue;
        }

        $kept[] = $line;
    }

    return implode("\n", $kept);
}

function splitSqlStatements(string $sql): array
{
    $statements = [];
    $current = '';
    $length = strlen($sql);
    $quote = null;

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $current .= $char;

        if ($quote !== null) {
            if ($char === '\\' && $i + 1 < $length) {
                $current .= $sql[++$i];
                continue;
            }

            if ($char === $quote) {
                $quote = null;
            }

            continue;
        }

        if ($char === '\'' || $char === '"') {
            $quote = $char;
            continue;
        }

        if ($char === ';') {
            $statements[] = $current;
            $current = '';
        }
    }

    if (trim($current) !== '') {
        $statements[] = $current;
    }

    return $statements;
}
