<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigratePrivateFiles extends Command
{
    protected $signature = 'scp:migrate-private-files';

    protected $description = 'Pindahkan dokumen izin & foto jurnal dari disk publik ke disk privat (local) tanpa mengubah path di database.';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $dirs = ['leave-documents', 'journals'];

        $plan = [];
        foreach ($dirs as $dir) {
            $plan[$dir] = $public->allFiles($dir);
        }

        $totalFiles = array_sum(array_map('count', $plan));
        if ($totalFiles === 0) {
            $this->info('Tidak ada file publik yang perlu dipindahkan. Semua sudah privat.');

            return self::SUCCESS;
        }

        $this->line('Rencana pemindahan file publik -> privat (disk local):');
        foreach ($plan as $dir => $files) {
            $this->line(sprintf('  - %s: %d file', $dir, count($files)));
        }

        $moved = 0;
        $skipped = 0;
        foreach ($plan as $dir => $files) {
            $dirMoved = 0;
            $dirSkipped = 0;

            foreach ($files as $file) {
                if ($private->exists($file)) {
                    $dirSkipped++;
                } else {
                    $private->put($file, $public->get($file));
                    $dirMoved++;
                }

                $public->delete($file);
            }

            $moved += $dirMoved;
            $skipped += $dirSkipped;

            $this->info("{$dir}: {$dirMoved} dipindahkan, {$dirSkipped} dilewati.");
        }

        $this->info("Selesai. Total dipindahkan: {$moved}, dilewati (sudah ada): {$skipped}.");

        return self::SUCCESS;
    }
}
