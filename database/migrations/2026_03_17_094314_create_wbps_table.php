<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wbps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upt_id')->constrained('upts')->cascadeOnDelete(); // Relasi ke Lapas
            $table->string('no_reg_instansi');
            $table->string('nama');
            $table->text('alamat')->nullable();
            $table->foreignId('jenis_kejahatan_id')->constrained('jenis_kejahatans')->cascadeOnDelete();
            $table->foreignId('sel_id')->constrained('sels')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wbps');
    }
};
