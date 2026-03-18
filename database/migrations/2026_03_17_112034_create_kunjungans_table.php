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
    Schema::create('kunjungans', function (Blueprint $table) {
        $table->id();
        $table->foreignId('pengunjung_id')->constrained('pengunjungs')->cascadeOnDelete();
        $table->foreignId('upt_id')->constrained('upts')->cascadeOnDelete();
        $table->foreignId('wbp_id')->constrained('wbps')->cascadeOnDelete();
        $table->date('tanggal_kunjungan');
        $table->time('waktu_kunjungan');
        $table->integer('pengikut_laki')->default(0);
        $table->integer('pengikut_perempuan')->default(0);
        $table->integer('pengikut_anak')->default(0);
        $table->integer('total_pengikut')->default(0);
        $table->uuid('qr_code_uuid')->unique();
        $table->string('status')->default('Menunggu Kedatangan'); // Menunggu, Selesai, Batal
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kunjungans');
    }
};
