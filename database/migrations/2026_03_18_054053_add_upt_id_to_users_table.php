<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            // Nullable karena Super Admin / Kanwil tidak terikat 1 UPT
            $table->foreignId('upt_id')->nullable()->constrained('upts')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['upt_id']);
            $table->dropColumn('upt_id');
        });
    }
};