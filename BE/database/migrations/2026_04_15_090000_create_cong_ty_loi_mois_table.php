<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cong_ty_loi_mois', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cong_ty_id')->constrained('cong_tys')->cascadeOnDelete();
            $table->foreignId('nguoi_dung_id')->nullable()->constrained('nguoi_dungs')->nullOnDelete();
            $table->string('email', 150);
            $table->string('vai_tro_noi_bo', 50);
            $table->string('trang_thai', 20)->default('pending');
            $table->foreignId('duoc_moi_boi')->nullable()->constrained('nguoi_dungs')->nullOnDelete();
            $table->timestamp('phan_hoi_luc')->nullable();
            $table->timestamps();

            $table->index(['cong_ty_id', 'trang_thai']);
            $table->index(['email', 'trang_thai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cong_ty_loi_mois');
    }
};
