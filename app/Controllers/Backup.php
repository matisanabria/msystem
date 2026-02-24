<?php

namespace App\Controllers;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\DownloadResponse; // used by getDownload()

class Backup extends Secure_Controller
{
    private BaseConnection $db;
    private string $backup_path;

    public function __construct()
    {
        parent::__construct('backup', null, 'office');
        $this->db = \Config\Database::connect();
        $this->backup_path = 'D:\\Backups\\';
    }

    /**
     * Display the backup UI with a list of existing backups.
     */
    public function getIndex(): void
    {
        $backups = [];

        if (is_dir($this->backup_path)) {
            foreach (glob($this->backup_path . 'backup_*.sql') as $file) {
                $backups[] = [
                    'filename' => basename($file),
                    'date'     => date('Y-m-d H:i:s', filemtime($file)),
                    'size'     => $this->_format_size(filesize($file)),
                ];
            }
            usort($backups, fn($a, $b) => strcmp($b['date'], $a['date']));
        }

        echo view('backup/index', ['backups' => $backups]);
    }

    /**
     * Generate a SQL dump and save it to the backup directory.
     */
    public function postCreate(): \CodeIgniter\HTTP\RedirectResponse
    {
        if (!is_dir($this->backup_path)) {
            mkdir($this->backup_path, 0755, true);
        }

        $sql = $this->_generate_dump();

        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        file_put_contents($this->backup_path . $filename, $sql);

        return redirect()->to(site_url('backup'))->with('message', lang('Backup.backup_created'));
    }

    /**
     * Download an existing backup file.
     *
     * @param string $filename
     * @return DownloadResponse
     */
    public function getDownload(string $filename): DownloadResponse
    {
        $filename = $this->_sanitize_filename($filename);
        $filepath = $this->backup_path . $filename;

        $data = file_get_contents($filepath);

        return $this->response->download($filename, $data);
    }

    /**
     * Delete a backup file and redirect back to the index page.
     */
    public function postDelete(): \CodeIgniter\HTTP\RedirectResponse
    {
        $filename = $this->_sanitize_filename($this->request->getPost('filename'));
        $filepath = $this->backup_path . $filename;

        if (file_exists($filepath)) {
            unlink($filepath);
            return redirect()->to(site_url('backup'))->with('message', lang('Backup.backup_deleted'));
        }

        return redirect()->to(site_url('backup'))->with('error', lang('Backup.backup_failed'));
    }

    /**
     * Generate a complete SQL dump using a direct MySQLi connection.
     */
    private function _generate_dump(): string
    {
        $db_config = config('Database')->default;

        $mysqli = new \mysqli(
            $db_config['hostname'],
            $db_config['username'],
            $db_config['password'],
            $db_config['database'],
            (int) ($db_config['port'] ?? 3306)
        );

        $mysqli->set_charset('utf8mb4');

        $output  = "-- OSPOS Database Backup\n";
        $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $output .= "-- Database: " . $db_config['database'] . "\n";
        $output .= "-- --------------------------------------------------------\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $output .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n";
        $output .= "SET NAMES utf8mb4;\n\n";

        $tables_result = $mysqli->query('SHOW TABLES');
        $tables = [];
        while ($row = $tables_result->fetch_row()) {
            $tables[] = $row[0];
        }

        foreach ($tables as $table) {
            $output .= "-- --------------------------------------------------------\n";
            $output .= "-- Table structure for `$table`\n";
            $output .= "-- --------------------------------------------------------\n\n";

            $output .= "DROP TABLE IF EXISTS `$table`;\n";

            $create_result = $mysqli->query("SHOW CREATE TABLE `$table`");
            $create_row = $create_result->fetch_row();
            $output .= $create_row[1] . ";\n\n";

            $rows_result = $mysqli->query("SELECT * FROM `$table`");
            if ($rows_result->num_rows > 0) {
                $output .= "-- Data for `$table`\n\n";

                $columns_result = $mysqli->query("SHOW COLUMNS FROM `$table`");
                $columns = [];
                while ($col = $columns_result->fetch_assoc()) {
                    $columns[] = '`' . $col['Field'] . '`';
                }
                $columns_str = implode(', ', $columns);

                $batch = [];
                while ($row = $rows_result->fetch_row()) {
                    $values = array_map(function ($val) use ($mysqli) {
                        if ($val === null) {
                            return 'NULL';
                        }
                        return "'" . $mysqli->real_escape_string($val) . "'";
                    }, $row);
                    $batch[] = '(' . implode(', ', $values) . ')';

                    if (count($batch) >= 100) {
                        $output .= "INSERT INTO `$table` ($columns_str) VALUES\n" . implode(",\n", $batch) . ";\n";
                        $batch = [];
                    }
                }

                if (!empty($batch)) {
                    $output .= "INSERT INTO `$table` ($columns_str) VALUES\n" . implode(",\n", $batch) . ";\n";
                }

                $output .= "\n";
            }
        }

        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $mysqli->close();

        return $output;
    }

    /**
     * Validate and return a safe backup filename.
     */
    private function _sanitize_filename(string $filename): string
    {
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.sql$/', $filename)) {
            show_error('Invalid filename.', 400);
        }
        return $filename;
    }

    /**
     * Format bytes into a human-readable size string.
     */
    private function _format_size(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
