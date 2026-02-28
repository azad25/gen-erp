<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupPageActions extends Command
{
    protected $signature = 'ui:cleanup-page-actions';
    protected $description = 'Remove default getHeaderActions from pages (now in base classes)';

    private array $cleaned = [];
    private array $skipped = [];

    public function handle()
    {
        $this->info('🧹 Cleaning up page actions...');
        $this->newLine();

        $resourcePath = app_path('Filament/Resources');
        $resources = File::directories($resourcePath);

        $totalFiles = 0;
        foreach ($resources as $resource) {
            $pagesPath = $resource . '/Pages';
            if (File::exists($pagesPath)) {
                $totalFiles += count(File::files($pagesPath));
            }
        }

        $bar = $this->output->createProgressBar($totalFiles);
        $bar->start();

        foreach ($resources as $resource) {
            $pagesPath = $resource . '/Pages';
            
            if (!File::exists($pagesPath)) {
                continue;
            }

            $files = File::files($pagesPath);
            
            foreach ($files as $file) {
                $this->cleanupPage($file->getPathname());
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->displayResults();
    }

    private function cleanupPage(string $file): void
    {
        $content = File::get($file);
        $basename = basename($file);
        $original = $content;

        // Pattern 1: Simple DeleteAction only
        $content = preg_replace(
            '/\n\s*protected function getHeaderActions\(\): array\s*\{\s*return\s*\[\s*DeleteAction::make\(\)\s*\];\s*\}\s*/s',
            "\n",
            $content
        );

        // Pattern 2: CreateAction only
        $content = preg_replace(
            '/\n\s*protected function getHeaderActions\(\): array\s*\{\s*return\s*\[\s*CreateAction::make\(\)\s*\];\s*\}\s*/s',
            "\n",
            $content
        );

        // Pattern 3: Fully qualified DeleteAction
        $content = preg_replace(
            '/\n\s*protected function getHeaderActions\(\): array\s*\{\s*return\s*\[\s*\\\\Filament\\\\Actions\\\\DeleteAction::make\(\)\s*\];\s*\}\s*/s',
            "\n",
            $content
        );

        // Pattern 4: Fully qualified CreateAction
        $content = preg_replace(
            '/\n\s*protected function getHeaderActions\(\): array\s*\{\s*return\s*\[\s*\\\\Filament\\\\Actions\\\\CreateAction::make\(\)\s*\];\s*\}\s*/s',
            "\n",
            $content
        );

        // Pattern 5: Fully qualified CreateAction with comma
        $content = preg_replace(
            '/\n\s*protected function getHeaderActions\(\): array\s*\{\s*return\s*\[\s*\\\\Filament\\\\Actions\\\\CreateAction::make\(\),\s*\];\s*\}\s*/s',
            "\n",
            $content
        );

        // Pattern 6: CreateAction with comma
        $content = preg_replace(
            '/\n\s*protected function getHeaderActions\(\): array\s*\{\s*return\s*\[\s*CreateAction::make\(\),\s*\];\s*\}\s*/s',
            "\n",
            $content
        );

        // Pattern 7: DeleteAction with comma
        $content = preg_replace(
            '/\n\s*protected function getHeaderActions\(\): array\s*\{\s*return\s*\[\s*DeleteAction::make\(\),\s*\];\s*\}\s*/s',
            "\n",
            $content
        );

        // Pattern 8: Fully qualified DeleteAction with comma
        $content = preg_replace(
            '/\n\s*protected function getHeaderActions\(\): array\s*\{\s*return\s*\[\s*\\\\Filament\\\\Actions\\\\DeleteAction::make\(\),\s*\];\s*\}\s*/s',
            "\n",
            $content
        );

        // Clean up extra blank lines
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        // Only write if changed
        if ($content !== $original) {
            File::put($file, $content);
            $this->cleaned[] = $basename;
        } else {
            $this->skipped[] = $basename;
        }
    }

    private function displayResults(): void
    {
        if (count($this->cleaned) > 0) {
            $this->info('✅ Cleaned ' . count($this->cleaned) . ' pages');
            $this->newLine();
        }

        if (count($this->skipped) > 0) {
            $this->comment('⏭️  Skipped ' . count($this->skipped) . ' pages (no default actions or custom actions)');
        }

        $this->newLine();
        $this->info('🎉 Cleanup complete!');
    }
}
