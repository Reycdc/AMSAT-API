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