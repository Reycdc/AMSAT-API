<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class InstallSuratFeatures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amsat:install-surat {--force : Force installation even if files exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Surat Management features (Surat Keluar, Surat Masuk, Disposisi)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('   AMSAT-KP: Surat Management Installer   ');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm('This will install Surat Management features. Continue?', true)) {
                $this->warn('Installation cancelled.');
                return 0;
            }
        }

        $this->newLine();
        $this->info('🚀 Starting installation...');
        $this->newLine();

        // Step 1: Create directories
        $this->createDirectories();

        // Step 2: Create migration files
        $this->createMigrations();

        // Step 3: Create model files
        $this->createModels();

        // Step 4: Create controller files
        $this->createControllers();

        // Step 5: Update routes
        $this->updateRoutes();

        // Step 6: Create storage directories
        $this->createStorageDirectories();

        // Step 7: Run migrations
        if ($this->confirm('Do you want to run migrations now?', true)) {
            $this->runMigrations();
        }

        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('✅ Installation completed successfully!');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $this->info('📚 Next steps:');
        $this->line('   1. Check routes: php artisan route:list | grep surat');
        $this->line('   2. Test API: Use Postman collection');
        $this->line('   3. Read documentation: SURAT_DOCUMENTATION.md');
        $this->newLine();

        return 0;
    }

    /**
     * Create necessary directories
     */
    protected function createDirectories()
    {
        $this->info('📁 Creating directories...');

        $directories = [
            app_path('Models'),
            app_path('Http/Controllers/Api'),
            database_path('migrations'),
            storage_path('app/public/surat_keluar'),
            storage_path('app/public/surat_masuk'),
        ];

        foreach ($directories as $directory) {
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->line("   ✓ Created: {$directory}");
            } else {
                $this->line("   - Exists: {$directory}");
            }
        }

        $this->newLine();
    }

    /**
     * Create migration files
     */
    protected function createMigrations()
    {
        $this->info('📝 Creating migrations...');

        $migrations = [
            '2025_11_16_000001_create_surat_keluar_table.php' => $this->getSuratKeluarMigration(),
            '2025_11_16_000002_create_surat_masuk_table.php' => $this->getSuratMasukMigration(),
            '2025_11_16_000003_create_disposisi_surat_table.php' => $this->getDisposisiMigration(),
        ];

        foreach ($migrations as $filename => $content) {
            $path = database_path("migrations/{$filename}");
            
            if (File::exists($path) && !$this->option('force')) {
                $this->warn("   ⚠ Skipped (exists): {$filename}");
                continue;
            }

            File::put($path, $content);
            $this->line("   ✓ Created: {$filename}");
        }

        $this->newLine();
    }

    /**
     * Create model files
     */
    protected function createModels()
    {
        $this->info('📦 Creating models...');

        $models = [
            'SuratKeluar.php' => $this->getSuratKeluarModel(),
            'SuratMasuk.php' => $this->getSuratMasukModel(),
            'DisposisiSurat.php' => $this->getDisposisiModel(),
        ];

        foreach ($models as $filename => $content) {
            $path = app_path("Models/{$filename}");
            
            if (File::exists($path) && !$this->option('force')) {
                $this->warn("   ⚠ Skipped (exists): {$filename}");
                continue;
            }

            File::put($path, $content);
            $this->line("   ✓ Created: {$filename}");
        }

        $this->newLine();
    }

    /**
     * Create controller files
     */
    protected function createControllers()
    {
        $this->info('🎮 Creating controllers...');

        $controllers = [
            'SuratKeluarController.php' => $this->getSuratKeluarController(),
            'SuratMasukController.php' => $this->getSuratMasukController(),
            'DisposisiSuratController.php' => $this->getDisposisiController(),
        ];

        foreach ($controllers as $filename => $content) {
            $path = app_path("Http/Controllers/Api/{$filename}");
            
            if (File::exists($path) && !$this->option('force')) {
                $this->warn("   ⚠ Skipped (exists): {$filename}");
                continue;
            }

            File::put($path, $content);
            $this->line("   ✓ Created: {$filename}");
        }

        $this->newLine();
    }

    /**
     * Update routes file
     */
    protected function updateRoutes()
    {
        $this->info('🛣️  Updating routes...');

        $routesPath = base_path('routes/api.php');
        $routesContent = File::get($routesPath);

        // Check if routes already added
        if (strpos($routesContent, 'SuratKeluarController') !== false) {
            $this->warn('   ⚠ Routes already exist, skipping...');
            $this->newLine();
            return;
        }

        $newRoutes = $this->getSuratRoutes();

        // Add routes before the last closing bracket
        $routesContent = rtrim($routesContent);
        if (substr($routesContent, -2) === '});') {
            $routesContent = substr($routesContent, 0, -2) . $newRoutes . "\n});";
        } else {
            $routesContent .= "\n" . $newRoutes;
        }

        File::put($routesPath, $routesContent);
        $this->line('   ✓ Routes added to api.php');
        $this->newLine();
    }

    /**
     * Create storage directories
     */
    protected function createStorageDirectories()
    {
        $this->info('💾 Creating storage directories...');

        $directories = [
            storage_path('app/public/surat_keluar'),
            storage_path('app/public/surat_masuk'),
        ];

        foreach ($directories as $directory) {
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->line("   ✓ Created: {$directory}");
            }
        }

        // Ensure storage link exists
        if (!File::exists(public_path('storage'))) {
            Artisan::call('storage:link');
            $this->line('   ✓ Created storage link');
        }

        $this->newLine();
    }

    /**
     * Run migrations
     */
    protected function runMigrations()
    {
        $this->info('🔄 Running migrations...');
        
        try {
            Artisan::call('migrate', ['--force' => true]);
            $this->line('   ✓ Migrations completed');
        } catch (\Exception $e) {
            $this->error("   ✗ Migration failed: {$e->getMessage()}");
        }

        $this->newLine();
    }

    /**
     * Get Surat Keluar migration content
     */
    protected function getSuratKeluarMigration()
    {
        return <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_keluar', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat')->unique();
            $table->date('tanggal_surat');
            $table->string('tujuan_surat');
            $table->text('isi');
            $table->string('file_surat')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_keluar');
    }
};
PHP;
    }

    /**
     * Get Surat Masuk migration content
     */
    protected function getSuratMasukMigration()
    {
        return <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_masuk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nomor_surat')->unique();
            $table->date('tanggal_surat');
            $table->string('pengirim');
            $table->string('perihal');
            $table->text('isi')->nullable();
            $table->string('file_surat')->nullable();
            $table->enum('status', ['unread', 'read', 'processed', 'archived'])->default('unread');
            $table->enum('prioritas', ['rendah', 'sedang', 'tinggi', 'urgent'])->default('sedang');
            $table->timestamp('read_at')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_masuk');
    }
};
PHP;
    }

    /**
     * Get Disposisi migration content
     */
    protected function getDisposisiMigration()
    {
        return <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposisi_surat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_surat_masuk')->constrained('surat_masuk')->onDelete('cascade');
            $table->foreignId('dari_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('kepada_user_id')->constrained('users')->onDelete('cascade');
            $table->text('catatan')->nullable();
            $table->enum('status', ['pending', 'diterima', 'diproses', 'selesai'])->default('pending');
            $table->timestamp('diterima_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->text('hasil_disposisi')->nullable();
            $table->timestamps();
            
            $table->unique(['id_surat_masuk', 'dari_user_id', 'kepada_user_id'], 'unique_disposisi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposisi_surat');
    }
};
PHP;
    }

    // Model and Controller content methods would be similar to migration methods
    // For brevity, I'll include the key ones

    protected function getSuratKeluarModel()
    {
        return file_get_contents(base_path('SuratKeluar.php'));
    }

    protected function getSuratMasukModel()
    {
        return file_get_contents(base_path('SuratMasuk.php'));
    }

    protected function getDisposisiModel()
    {
        return file_get_contents(base_path('DisposisiSurat.php'));
    }

    protected function getSuratKeluarController()
    {
        return file_get_contents(base_path('SuratKeluarController.php'));
    }

    protected function getSuratMasukController()
    {
        return file_get_contents(base_path('SuratMasukController.php'));
    }

    protected function getDisposisiController()
    {
        return file_get_contents(base_path('DisposisiSuratController.php'));
    }

    /**
     * Get routes content
     */
    protected function getSuratRoutes()
    {
        return <<<'PHP'

    // ========================================
    // SURAT MANAGEMENT ROUTES
    // ========================================
    
    // Surat Keluar
    Route::get('/surat-keluar', [App\Http\Controllers\Api\SuratKeluarController::class, 'index']);
    Route::post('/surat-keluar', [App\Http\Controllers\Api\SuratKeluarController::class, 'store']);
    Route::get('/surat-keluar/{id}', [App\Http\Controllers\Api\SuratKeluarController::class, 'show']);
    Route::put('/surat-keluar/{id}', [App\Http\Controllers\Api\SuratKeluarController::class, 'update']);
    Route::delete('/surat-keluar/{id}', [App\Http\Controllers\Api\SuratKeluarController::class, 'destroy']);
    Route::post('/surat-keluar/{id}/submit', [App\Http\Controllers\Api\SuratKeluarController::class, 'submit']);
    Route::post('/surat-keluar/{id}/approve', [App\Http\Controllers\Api\SuratKeluarController::class, 'approve'])->middleware('role:admin');
    Route::post('/surat-keluar/{id}/reject', [App\Http\Controllers\Api\SuratKeluarController::class, 'reject'])->middleware('role:admin');
    
    // Surat Masuk
    Route::get('/surat-masuk', [App\Http\Controllers\Api\SuratMasukController::class, 'index']);
    Route::get('/surat-masuk/all', [App\Http\Controllers\Api\SuratMasukController::class, 'all'])->middleware('role:admin|editor');
    Route::get('/surat-masuk/unread-count', [App\Http\Controllers\Api\SuratMasukController::class, 'unreadCount']);
    Route::post('/surat-masuk', [App\Http\Controllers\Api\SuratMasukController::class, 'store'])->middleware('role:admin|editor');
    Route::get('/surat-masuk/{id}', [App\Http\Controllers\Api\SuratMasukController::class, 'show']);
    Route::put('/surat-masuk/{id}', [App\Http\Controllers\Api\SuratMasukController::class, 'update'])->middleware('role:admin|editor');
    Route::delete('/surat-masuk/{id}', [App\Http\Controllers\Api\SuratMasukController::class, 'destroy'])->middleware('role:admin|editor');
    Route::patch('/surat-masuk/{id}/status', [App\Http\Controllers\Api\SuratMasukController::class, 'updateStatus']);
    
    // Disposisi
    Route::get('/disposisi', [App\Http\Controllers\Api\DisposisiSuratController::class, 'index']);
    Route::get('/disposisi/sent', [App\Http\Controllers\Api\DisposisiSuratController::class, 'sent']);
    Route::get('/disposisi/surat/{suratMasukId}', [App\Http\Controllers\Api\DisposisiSuratController::class, 'getBySurat']);
    Route::post('/disposisi', [App\Http\Controllers\Api\DisposisiSuratController::class, 'store']);
    Route::get('/disposisi/{id}', [App\Http\Controllers\Api\DisposisiSuratController::class, 'show']);
    Route::put('/disposisi/{id}', [App\Http\Controllers\Api\DisposisiSuratController::class, 'update']);
    Route::post('/disposisi/{id}/accept', [App\Http\Controllers\Api\DisposisiSuratController::class, 'accept']);
    Route::post('/disposisi/{id}/process', [App\Http\Controllers\Api\DisposisiSuratController::class, 'process']);
    Route::post('/disposisi/{id}/complete', [App\Http\Controllers\Api\DisposisiSuratController::class, 'complete']);
    Route::delete('/disposisi/{id}', [App\Http\Controllers\Api\DisposisiSuratController::class, 'destroy']);
PHP;
    }
}
