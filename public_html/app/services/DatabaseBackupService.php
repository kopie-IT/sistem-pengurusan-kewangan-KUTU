<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Service for Database SQL Backup (Export) and Restoration (Import).
 */
final class DatabaseBackupService
{
    /**
     * Export all database tables into a clean SQL dump string.
     */
    public function exportSql(): string
    {
        $pdo = Database::connection();
        $dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();

        $tablesStmt = $pdo->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'');
        $tables = [];
        while ($row = $tablesStmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = (string) $row[0];
        }

        $sql = "-- ========================================================\n";
        $sql .= "-- Sistem Main Kutu - Database SQL Backup / Dump\n";
        $sql .= "-- Pangkalan Data: {$dbName}\n";
        $sql .= "-- Tarikh Dijana: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- ========================================================\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql .= "SET time_zone = \"+00:00\";\n\n";

        foreach ($tables as $table) {
            $sql .= "-- --------------------------------------------------------\n";
            $sql .= "-- Struktur Jadual: `{$table}`\n";
            $sql .= "-- --------------------------------------------------------\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";

            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
            $createTableSql = $createRow['Create Table'] ?? '';
            $sql .= $createTableSql . ";\n\n";

            $rowsStmt = $pdo->query("SELECT * FROM `{$table}`");
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                $sql .= "-- Data untuk Jadual: `{$table}`\n";
                $columns = array_keys($rows[0]);
                $colNames = implode('`, `', $columns);

                foreach (array_chunk($rows, 100) as $chunk) {
                    $valuesList = [];
                    foreach ($chunk as $row) {
                        $escapedValues = array_map(function ($val) use ($pdo) {
                            if ($val === null) {
                                return 'NULL';
                            }
                            return $pdo->quote((string) $val);
                        }, array_values($row));

                        $valuesList[] = '(' . implode(', ', $escapedValues) . ')';
                    }

                    $sql .= "INSERT INTO `{$table}` (`{$colNames}`) VALUES\n" . implode(",\n", $valuesList) . ";\n";
                }
                $sql .= "\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        $sql .= "-- Selesai Eksport Pangkalan Data.\n";

        return $sql;
    }

    /**
     * Import a SQL script into the database.
     *
     * @param string $sqlContent
     * @return array{ok: bool, queriesExecuted?: int, error?: string}
     */
    public function importSql(string $sqlContent): array
    {
        $sqlContent = trim($sqlContent);
        if ($sqlContent === '') {
            return ['ok' => false, 'error' => 'Fail SQL kosong atau tidak sah.'];
        }

        $pdo = Database::connection();

        // Split SQL statements reasonably
        $queries = $this->splitSqlStatements($sqlContent);
        if (empty($queries)) {
            return ['ok' => false, 'error' => 'Tiada pernyataan SQL yang sah dijumpai dalam fail.'];
        }

        $pdo->beginTransaction();
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

            $count = 0;
            foreach ($queries as $query) {
                $query = trim($query);
                if ($query === '') {
                    continue;
                }
                $pdo->exec($query);
                $count++;
            }

            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            $pdo->commit();

            return ['ok' => true, 'queriesExecuted' => $count];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[DatabaseBackupService::importSql] Error: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Ralat ketika memproses import SQL: ' . $e->getMessage()];
        }
    }

    /**
     * Split raw SQL script into executable statement chunks safely.
     *
     * @return string[]
     */
    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $lines = explode("\n", $sql);
        $buffer = '';

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*') || str_starts_with($trimmed, '#')) {
                continue;
            }

            $buffer .= $line . "\n";
            if (str_ends_with(rtrim($line), ';')) {
                $statements[] = $buffer;
                $buffer = '';
            }
        }

        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }
}
