<?php

namespace App\Console\Commands;

use App\Models\ReportImage;
use Illuminate\Console\Command;
use Intervention\Image\Laravel\Facades\Image;

class CompressExistingImages extends Command
{
    protected $signature = 'images:compress {--dry-run : Afficher ce qui serait fait sans le faire}';
    protected $description = 'Compresse toutes les images existantes dans la base de données';

    private const MAX_WIDTH = 1920;
    private const MAX_HEIGHT = 1920;
    private const QUALITY = 85;

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $images = ReportImage::all();
        $this->info("Nombre total d'images à traiter: {$images->count()}");

        if ($dryRun) {
            $this->warn("Mode DRY-RUN activé - Aucune modification ne sera effectuée");
        }

        $progressBar = $this->output->createProgressBar($images->count());
        $progressBar->start();

        $stats = [
            'compressed' => 0,
            'skipped' => 0,
            'errors' => 0,
            'total_saved' => 0,
        ];

        foreach ($images as $image) {
            try {
                $fullPath = storage_path('app/public/' . $image->image_path);

                if (!file_exists($fullPath)) {
                    $this->newLine();
                    $this->warn("Fichier introuvable: {$image->image_path}");
                    $stats['skipped']++;
                    $progressBar->advance();
                    continue;
                }

                $originalSize = filesize($fullPath);

                if (!$dryRun) {
                    // Charger et compresser l'image
                    $img = Image::read($fullPath);

                    // Redimensionner si nécessaire
                    if ($img->width() > self::MAX_WIDTH || $img->height() > self::MAX_HEIGHT) {
                        $img->scale(width: self::MAX_WIDTH, height: self::MAX_HEIGHT);
                    }

                    // Sauvegarder avec compression
                    $img->save($fullPath, quality: self::QUALITY);

                    $newSize = filesize($fullPath);
                    $saved = $originalSize - $newSize;
                    $stats['total_saved'] += $saved;
                } else {
                    $newSize = $originalSize;
                    $saved = 0;
                }

                $stats['compressed']++;
                $progressBar->advance();

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Erreur sur {$image->image_path}: {$e->getMessage()}");
                $stats['errors']++;
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Afficher les statistiques
        $this->info("=== Résumé ===");
        $this->info("Images compressées: {$stats['compressed']}");
        $this->info("Images ignorées: {$stats['skipped']}");
        $this->info("Erreurs: {$stats['errors']}");
        
        if (!$dryRun) {
            $savedMB = round($stats['total_saved'] / 1048576, 2);
            $this->info("Espace économisé: {$savedMB} MB");
        }

        return Command::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
