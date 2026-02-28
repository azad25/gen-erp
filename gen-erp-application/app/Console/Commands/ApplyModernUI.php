<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ApplyModernUI extends Command
{
    protected $signature = 'ui:apply-modern';
    protected $description = 'Apply modern UI to all Filament resources';

    private array $updated = [];
    private array $skipped = [];

    public function handle()
    {
        $this->info('🎨 Applying Modern UI to all resources...');
        $this->newLine();

        $resourcePath = app_path('Filament/Resources');
        $files = File::glob($resourcePath . '/*Resource.php');

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $file) {
            $basename = basename($file);
            
            if ($basename === 'BaseResource.php') {
                $bar->advance();
                continue;
            }

            $this->updateResource($file);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->displayResults();
    }

    private function updateResource(string $file): void
    {
        $content = File::get($file);
        $basename = basename($file);
        $original = $content;

        // Step 1: Update extends clause
        if (str_contains($content, 'extends Resource') && !str_contains($content, 'extends BaseResource')) {
            $content = preg_replace(
                '/class\s+(\w+Resource)\s+extends\s+Resource/',
                'class $1 extends BaseResource',
                $content
            );
        }

        // Step 2: Add use statements
        if (!str_contains($content, 'use App\Filament\Resources\BaseResource')) {
            // Find the last use statement
            if (preg_match('/^use\s+[^;]+;$/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
                $lastUsePos = $matches[0][1] + strlen($matches[0][0]);
                
                $newUses = "\nuse App\Filament\Resources\BaseResource;";
                
                if (!str_contains($content, 'use App\Filament\Support\FormStyles')) {
                    $newUses .= "\nuse App\Filament\Support\FormStyles;";
                }
                
                if (!str_contains($content, 'use App\Filament\Support\TableStyles')) {
                    $newUses .= "\nuse App\Filament\Support\TableStyles;";
                }
                
                $content = substr_replace($content, $newUses, $lastUsePos, 0);
            }
        }

        // Step 3: Update table method to use modernTable
        if (preg_match('/public static function table\(Table \$table\): Table\s*\{/', $content)) {
            // Replace "return $table" with "return static::modernTable($table)"
            $content = preg_replace(
                '/return\s+\$table\s*\n\s*->columns/',
                'return static::modernTable($table)' . "\n            ->columns",
                $content
            );
        }

        // Step 4: Update actions to use modern actions
        if (str_contains($content, '->actions([') && !str_contains($content, 'getModernTableActions')) {
            $content = preg_replace(
                '/->actions\(\[\s*EditAction::make\(\)[^\]]*\]\)/',
                '->actions(static::getModernTableActions())',
                $content
            );
        }

        // Step 5: Add bulk actions if missing
        if (!str_contains($content, '->bulkActions(')) {
            $content = preg_replace(
                '/(->actions\([^\)]+\))(\s*;)/',
                '$1' . "\n            ->bulkActions(static::getModernBulkActions())" . '$2',
                $content
            );
        }

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
            $this->info('✅ Updated ' . count($this->updated) . ' resources:');
            foreach ($this->updated as $file) {
                $this->line('   • ' . $file);
            }
            $this->newLine();
        }

        if (count($this->skipped) > 0) {
            $this->warn('⏭️  Skipped ' . count($this->skipped) . ' resources (already modern or no changes needed)');
        }

        $this->newLine();
        $this->info('🎉 Modern UI application complete!');
        $this->line('');
        $this->line('Next steps:');
        $this->line('1. Run: npm run build');
        $this->line('2. Run: php artisan filament:optimize-clear');
        $this->line('3. Test each resource in the browser');
    }
}
