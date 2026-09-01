<?php

namespace Vendor\BackupData\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Génère une sauvegarde globale de la base de données dans un fichier .zip
 * contenant :
 *   - database.sql  : structure + données (INSERT) de toutes les tables
 *   - csv/<table>.csv : une exportation CSV par table
 *
 * Implémentation 100% PHP (pas de dépendance à mysqldump), écriture en flux
 * dans des fichiers temporaires pour rester léger en mémoire.
 */
class DatabaseBackupService
{
    /** Dossier (disque "local" = storage/app) où sont stockés les .zip. */
    public const BACKUP_DIR = 'backups';

    /**
     * Construit l'archive .zip et renvoie ses métadonnées.
     *
     * @return array{filename:string, path:string, size_bytes:int, tables_count:int, rows_count:int}
     */
    public function create(): array
    {
        $pdo = DB::getPdo();
        $tables = $this->listTables();

        $tmpDir = $this->makeTempDir();
        $sqlFile = $tmpDir . DIRECTORY_SEPARATOR . 'database.sql';
        $csvDir  = $tmpDir . DIRECTORY_SEPARATOR . 'csv';
        @mkdir($csvDir, 0775, true);

        $totalRows = 0;

        // ── Fichier SQL ───────────────────────────────────────────────
        $sql = fopen($sqlFile, 'w');
        fwrite($sql, "-- Sauvegarde base de données : " . DB::getDatabaseName() . "\n");
        fwrite($sql, "-- Générée le : " . now()->format('Y-m-d H:i:s') . "\n");
        fwrite($sql, "SET FOREIGN_KEY_CHECKS=0;\n");
        fwrite($sql, "SET NAMES utf8mb4;\n\n");

        foreach ($tables as $table) {
            // Structure
            fwrite($sql, "-- ----------------------------\n");
            fwrite($sql, "-- Structure de la table `{$table}`\n");
            fwrite($sql, "-- ----------------------------\n");
            fwrite($sql, "DROP TABLE IF EXISTS `{$table}`;\n");

            $createRow = DB::selectOne("SHOW CREATE TABLE `{$table}`");
            $createSql = $createRow->{'Create Table'} ?? ($createRow->{'Create View'} ?? null);
            if ($createSql) {
                fwrite($sql, $createSql . ";\n\n");
            }

            // Données
            $columns = Schema::getColumnListing($table);
            $colList = '`' . implode('`, `', $columns) . '`';

            // CSV en parallèle
            $csvHandle = fopen($csvDir . DIRECTORY_SEPARATOR . $table . '.csv', 'w');
            // BOM UTF-8 pour un affichage correct des accents dans Excel
            fwrite($csvHandle, "\xEF\xBB\xBF");
            fputcsv($csvHandle, $columns, ';');

            $rowsForTable = 0;
            foreach (DB::table($table)->cursor() as $row) {
                $rowArray = (array) $row;

                // Ligne SQL INSERT
                $values = array_map(function ($value) use ($pdo) {
                    if (is_null($value)) {
                        return 'NULL';
                    }
                    if (is_int($value) || is_float($value)) {
                        return $value;
                    }
                    return $pdo->quote((string) $value);
                }, array_values($rowArray));

                fwrite($sql, "INSERT INTO `{$table}` ({$colList}) VALUES (" . implode(', ', $values) . ");\n");

                // Ligne CSV
                fputcsv($csvHandle, array_map(fn ($v) => is_null($v) ? '' : $v, array_values($rowArray)), ';');

                $rowsForTable++;
            }

            fclose($csvHandle);
            fwrite($sql, "\n");
            $totalRows += $rowsForTable;
        }

        fwrite($sql, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($sql);

        // ── Archive ZIP ───────────────────────────────────────────────
        $filename = 'backup_' . DB::getDatabaseName() . '_' . now()->format('Y-m-d_H-i-s') . '.zip';
        $zipTmp   = $tmpDir . DIRECTORY_SEPARATOR . $filename;

        $zip = new ZipArchive();
        $zip->open($zipTmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFile($sqlFile, 'database.sql');
        foreach ($tables as $table) {
            $csvPath = $csvDir . DIRECTORY_SEPARATOR . $table . '.csv';
            if (is_file($csvPath)) {
                $zip->addFile($csvPath, 'csv/' . $table . '.csv');
            }
        }
        $zip->close();

        // ── Stockage dans storage/app/backups ─────────────────────────
        $relativePath = self::BACKUP_DIR . '/' . $filename;
        Storage::disk('local')->put($relativePath, file_get_contents($zipTmp));
        $sizeBytes = Storage::disk('local')->size($relativePath);

        // Nettoyage des fichiers temporaires
        $this->deleteDir($tmpDir);

        return [
            'filename'     => $filename,
            'path'         => $relativePath,
            'size_bytes'   => $sizeBytes,
            'tables_count' => count($tables),
            'rows_count'   => $totalRows,
        ];
    }

    /**
     * Liste des tables de la base courante.
     *
     * @return string[]
     */
    private function listTables(): array
    {
        $rows = DB::select('SHOW TABLES');
        $tables = [];
        foreach ($rows as $row) {
            $arr = (array) $row;
            $tables[] = reset($arr); // la clé est "Tables_in_<db>"
        }
        sort($tables);
        return $tables;
    }

    private function makeTempDir(): string
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dbbackup_' . uniqid();
        @mkdir($dir, 0775, true);
        return $dir;
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->deleteDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
