<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ApplyModernPages extends Command
{
    protected $signature = 'ui:apply-modern-pages';
    protected $description = 'Apply modern UI to all Filament resource pages';

    private array $updated = [];
    private array $skipped = [];

    public function handle()
    {
        $this->info('🎨 Applying Modern UI to all resource pages...');
        $this->newLine();

        $resourcePath = app_path('Filament/Resources');
        $resources = File::directories($resourcePath);

        $totalFiles = 0;
        foreach ($resources as $resource) {
            if (basename($resource) === 'BaseResource.php') {
                continue;
            }
            
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
                $this->updatePage($file->getPathname());
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->displayResults();
    }

    private function updatePage(string $file): void
    {
        $content = File::get($file);
        $basename = basename($file);
        $original = $content;

        // Determine page type
        $pageType = null;
        if (str_contains($basename, 'List')) {
            $pageType = 'List';
        } elseif (str_contains($basename, 'Create')) {
            $pageType = 'Create';
        } elseif (str_contains($basename, 'Edit')) {
            $pageType = 'Edit';
        } elseif (str_contains($basename, 'View')) {
            $pageType = 'View';
        }

        if (!$pageType) {
            $this->skipped[] = $basename . ' (unknown type)';
            return;
        }

        // Check if already using base page
        if (str_contains($content, "extends Base{$pageType}Page")) {
            $this->skipped[] = $basename . ' (already modern)';
            return;
        }

        // Step 1: Update extends clause
        $oldExtends = match($pageType) {
            'List' => 'ListRecords',
            'Create' => 'CreateRecord',
            'Edit' => 'EditRecord',
            'View' => 'ViewRecord',
        };

        $content = str_replace(
            "extends {$oldExtends}",
            "extends Base{$pageType}Page",
            $content
        );

        // Step 2: Add use statement for base page
        $useStatement = "use App\Filament\Pages\Base{$pageType}Page;";
        
        if (!str_contains($content, $useStatement)) {
            // Find the last use statement
            if (preg_match('/^use\s+[^;]+;$/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
                $lastUsePos = $matches[0][1] + strlen($matches[0][0]);
                $content = substr_replace($content, "\n" . $useStatement, $lastUsePos, 0);
            }
        }

        // Step 3: Remove old Filament use statements (they're in base classes now)
        $oldUses = [
            "use Filament\Resources\Pages\\{$oldExtends};",
            "use Filament\Actions\CreateAction;",
            "use Filament\Actions\DeleteAction;",
            "use Filament\Actions\EditAction;",
            "use Filament\Actions\ViewAction;",
        ];

        foreach ($oldUses as $oldUse) {
            $content = str_replace("\n" . $oldUse, '', $content);
        }

        // Step 4: Remove getHeaderActions if it's just the default
        if ($pageType === 'List') {
            // Remove default create action
            $content = preg_replace(
                '/protected function getHeaderActions\(\): array\s*\{\s*return\s*\[\s*\\\\?Filament\\\\Actions\\\\CreateAction::make\(\)\s*\];\s*\}/s',
                '',
                $content
            );
            $content = preg_replace(
                '/protected function getHeaderActions\(\): array\s*\{\s*return\s*\[\s*CreateAction::make\(\)\s*\];\s*\}/s',
                '',
                $content
            );
        } elseif ($pageType === 'Edit') {
            // Remove default delete action
            $content = preg_replace(
                '/protected function getHeaderActions\(\): array\s*\{\s*return\s*\[\s*\\\\?Filament\\\\Actions\\\\DeleteAction::make\(\)\s*\];\s*\}/s',
                '',
                $content
            );
            $content = preg_replace(
                '/protected function getHeaderActions\(\): array\s*\{\s*return\s*\[\s*DeleteAction::make\(\)\s*\];\s*\}/s',
                '',
                $content
            );
        }

        // Clean up extra blank lines
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        // Only write if changed
        if ($content !== $original) {
            File::put($file, $content);
            $this->updated[] = $basename;
        } else {
            $this->skipped[] = $basename;
        }
    }

    private function displayResults(): void
    {
        if (count($this->updated) > 0) {
            $this->info('✅ Updated ' . count($this->updated) . ' pages:');
            foreach ($this->updated as $file) {
                $this->line('   • ' . $file);
            }
            $this->newLine();
        }

        if (count($this->skipped) > 0) {
            $this->warn('⏭️  Skipped ' . count($this->skipped) . ' pages (already modern or no changes needed)');
        }

        $this->newLine();
        $this->info('🎉 Modern page UI application complete!');
        $this->line('');
        $this->line('Next steps:');
        $this->line('1. Run: npm run build');
        $this->line('2. Run: php artisan filament:optimize-clear');
        $this->line('3. Test each page type in the browser');
    }
}
