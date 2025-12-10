<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeSuratFolders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:surat-folders {--force : Force creation even if folders exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create folder structure for Surat Management features';

    /**
     * Folder structure to be created
     *
     * @var array
     */
    protected $folders = [
        // Application folders
        'app/Models',
        'app/Http/Controllers',
        'app/Http/Controllers/Api',
        'app/Http/Middleware',
        'app/Http/Requests',
        'app/Http/Requests/Surat',
        
        // Database folders
        'database/migrations',
        'database/seeders',
        'database/factories',
        
        // Storage folders - Surat Keluar
        'storage/app/public/surat_keluar',
        'storage/app/public/surat_keluar/pdf',
        'storage/app/public/surat_keluar/doc',
        'storage/app/public/surat_keluar/temp',
        
        // Storage folders - Surat Masuk
        'storage/app/public/surat_masuk',
        'storage/app/public/surat_masuk/pdf',
        'storage/app/public/surat_masuk/doc',
        'storage/app/public/surat_masuk/temp',
        
        // Storage folders - Disposisi attachments
        'storage/app/public/disposisi',
        'storage/app/public/disposisi/attachments',
        
        // Documentation folders
        'docs',
        'docs/api',
        'docs/surat',
        
        // Testing folders
        'tests/Feature/Surat',
        'tests/Unit/Surat',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->displayHeader();
        
        $created = 0;
        $existed = 0;
        $errors = 0;

        $this->info('📁 Creating folder structure...');
        $this->newLine();

        foreach ($this->folders as $folder) {
            $fullPath = base_path($folder);
            
            try {
                if (File::isDirectory($fullPath)) {
                    if ($this->option('force')) {
                        $this->line("   ↻ Exists (kept): <comment>{$folder}</comment>");
                        $existed++;
                    } else {
                        $this->line("   ✓ Already exists: <comment>{$folder}</comment>");
                        $existed++;
                    }
                } else {
                    File::makeDirectory($fullPath, 0755, true);
                    $this->line("   ✓ Created: <info>{$folder}</info>");
                    $created++;
                    
                    // Create .gitkeep for storage folders
                    if (strpos($folder, 'storage/app/public') !== false) {
                        File::put($fullPath . '/.gitkeep', '');
                    }
                }
            } catch (\Exception $e) {
                $this->line("   ✗ Failed: <error>{$folder}</error>");
                $this->line("     Error: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        $this->displaySummary($created, $existed, $errors);
        $this->displayNextSteps();

        return $errors > 0 ? 1 : 0;
    }

    /**
     * Display header
     */
    protected function displayHeader()
    {
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('   AMSAT-KP: Folder Structure Generator');
        $this->info('   Surat Management Feature');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
    }

    /**
     * Display summary
     */
    protected function displaySummary($created, $existed, $errors)
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('   Summary');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line("   📊 Total folders: <info>" . count($this->folders) . "</info>");
        $this->line("   ✓ Created: <info>{$created}</info>");
        $this->line("   ↻ Already existed: <comment>{$existed}</comment>");
        
        if ($errors > 0) {
            $this->line("   ✗ Errors: <error>{$errors}</error>");
        }
        
        $this->newLine();
        
        if ($errors === 0) {
            $this->info('✅ Folder structure created successfully!');
        } else {
            $this->warn('⚠️  Some folders could not be created. Check permissions.');
        }
    }

    /**
     * Display next steps
     */
    protected function displayNextSteps()
    {
        $this->newLine();
        $this->info('📚 Next steps:');
        $this->line('   1. Copy migration files to: <comment>database/migrations/</comment>');
        $this->line('   2. Copy model files to: <comment>app/Models/</comment>');
        $this->line('   3. Copy controller files to: <comment>app/Http/Controllers/Api/</comment>');
        $this->line('   4. Run migrations: <comment>php artisan migrate</comment>');
        $this->line('   5. Create storage link: <comment>php artisan storage:link</comment>');
        $this->newLine();
        
        $this->info('💡 Tip: Use the full installer for automatic file creation:');
        $this->line('   <comment>php artisan amsat:install-surat</comment>');
        $this->newLine();
        
        $this->info('📁 Folder structure:');
        $this->displayFolderTree();
        $this->newLine();
    }

    /**
     * Display folder tree structure
     */
    protected function displayFolderTree()
    {
        $tree = [
            'app/' => [
                'Models/',
                'Http/' => [
                    'Controllers/' => [
                        'Api/',
                    ],
                    'Middleware/',
                    'Requests/' => [
                        'Surat/',
                    ],
                ],
            ],
            'database/' => [
                'migrations/',
                'seeders/',
                'factories/',
            ],
            'storage/app/public/' => [
                'surat_keluar/' => [
                    'pdf/',
                    'doc/',
                    'temp/',
                ],
                'surat_masuk/' => [
                    'pdf/',
                    'doc/',
                    'temp/',
                ],
                'disposisi/' => [
                    'attachments/',
                ],
            ],
            'docs/' => [
                'api/',
                'surat/',
            ],
            'tests/' => [
                'Feature/' => [
                    'Surat/',
                ],
                'Unit/' => [
                    'Surat/',
                ],
            ],
        ];

        $this->renderTree($tree, '   ');
    }

    /**
     * Render tree structure recursively
     */
    protected function renderTree($items, $indent = '')
    {
        foreach ($items as $key => $value) {
            if (is_array($value)) {
                $this->line("{$indent}📁 <comment>{$key}</comment>");
                $this->renderTree($value, $indent . '   ');
            } else {
                $this->line("{$indent}📁 <comment>{$value}</comment>");
            }
        }
    }
}