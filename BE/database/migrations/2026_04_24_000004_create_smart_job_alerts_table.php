<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_job_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_dung_id')->constrained('nguoi_dungs')->cascadeOnDelete();
            $table->foreignId('tin_tuyen_dung_id')->constrained('tin_tuyen_dungs')->cascadeOnDelete();
            $table->foreignId('cong_ty_id')->constrained('cong_tys')->cascadeOnDelete();
            $table->foreignId('ho_so_id')->nullable()->constrained('ho_sos')->nullOnDelete();
            $table->decimal('match_score', 5, 2)->default(0);
            $table->string('match_level', 30)->default('medium');
            $table->json('matched_skills_json')->nullable();
            $table->json('missing_skills_json')->nullable();
            $table->json('matched_industries_json')->nullable();
            $table->json('reasons_json')->nullable();
            $table->json('metadata_json')->nullable();
            $table->string('trang_thai', 30)->default('new');
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->unique(['nguoi_dung_id', 'tin_tuyen_dung_id'], 'smart_alert_user_job_unique');
            $table->index(['nguoi_dung_id', 'trang_thai', 'created_at']);
            $table->index(['tin_tuyen_dung_id', 'match_score']);
            $table->index(['cong_ty_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_job_alerts');
    }
};
