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
        Schema::table('resumes', function (Blueprint $table) {
            $table->string('photo_url')->nullable()->after('template');
            $table->json('education')->nullable()->after('photo_url');
            $table->json('languages')->nullable()->after('education');
            $table->json('additional_info')->nullable()->after('languages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->dropColumn(['photo_url', 'education', 'languages', 'additional_info']);
        });
    }
};
