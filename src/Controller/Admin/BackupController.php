<?php

namespace App\Controller\Admin;

use ZipArchive;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Filesystem;

#[Route('/admin/backups', name: 'admin_backups_')]
class BackupController extends AbstractController
{
    private string $backupDir;

    public function __construct()
    {
        $this->backupDir = __DIR__ . '/../../../var/backups';
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $fs = new Filesystem();
        if (!$fs->exists($this->backupDir)) {
            $fs->mkdir($this->backupDir);
        }

        $files = array_diff(scandir($this->backupDir), ['.', '..']);
        $fileData = [];

        foreach ($files as $file) {
            $path = $this->backupDir . '/' . $file;
            $fileData[] = [
                'name' => $file,
                'size' => filesize($path),
            ];
        }

        return $this->render('admin/backups/index.html.twig', [
            'files' => $fileData,
        ]);
    }


    #[Route('/create', name: 'create')]
    public function create(): Response
    {
        $fs = new Filesystem();

        if (!$fs->exists($this->backupDir)) {
            $fs->mkdir($this->backupDir);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $backupName = "backup_$timestamp.zip";
        $zipPath = $this->backupDir . '/' . $backupName;

        // 📦 Crée une archive ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new \Exception('Impossible de créer l’archive ZIP');
        }

        // 1️⃣ Sauvegarde la base de données via PDO (sans mysqldump)
        $dumpPath = $this->backupDir . "/dump_$timestamp.sql";

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'] ?? '127.0.0.1',
            $_ENV['DB_NAME'] ?? 'symfony'
        );
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASSWORD'] ?? '';

        try {
            $pdo = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);

            $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
            $sqlDump = "-- Sauvegarde du " . date('Y-m-d H:i:s') . "\n\n";

            foreach ($tables as $table) {
                // Structure
                $createStmt = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(\PDO::FETCH_ASSOC);
                $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
                $sqlDump .= $createStmt['Create Table'] . ";\n\n";

                // Données
                $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($rows as $row) {
                    $values = array_map([$pdo, 'quote'], array_values($row));
                    $sqlDump .= "INSERT INTO `$table` VALUES (" . implode(',', $values) . ");\n";
                }
                $sqlDump .= "\n\n";
            }

            file_put_contents($dumpPath, $sqlDump);
            $zip->addFile($dumpPath, "database.sql");
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Erreur lors de la sauvegarde SQL : ' . $e->getMessage());
        }


        // 2️⃣ Ajoute les dossiers principaux
        $foldersToBackup = [
            realpath(__DIR__ . '/../../../src'),
            realpath(__DIR__ . '/../../../templates'),
            realpath(__DIR__ . '/../../../public'),
        ];

        foreach ($foldersToBackup as $folder) {
            $this->addFolderToZip($folder, $zip, basename($folder));
        }

        $zip->close();

        // Supprime le fichier SQL temporaire
        if ($fs->exists($dumpPath)) {
            $fs->remove($dumpPath);
        }

        $this->addFlash('success', "Sauvegarde complète créée : $backupName");
        return $this->redirectToRoute('admin_backups_index');
    }

    private function addFolderToZip(string $folder, ZipArchive $zip, string $parentFolder = '')
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $parentFolder . '/' . substr($filePath, strlen($folder) + 1);

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    #[Route('/download/{filename}', name: 'download')]
    public function download(string $filename): Response
    {
        $path = $this->backupDir . '/' . $filename;
        return $this->file($path);
    }

    #[Route('/delete/{filename}', name: 'delete')]
    public function delete(string $filename): Response
    {
        $path = $this->backupDir . '/' . $filename;
        if (file_exists($path)) {
            unlink($path);
        }
        $this->addFlash('success', 'Sauvegarde supprimée.');
        return $this->redirectToRoute('admin_backups_index');
    }
}
