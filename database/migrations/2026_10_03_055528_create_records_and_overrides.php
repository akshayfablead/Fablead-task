<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('permission_overrides')->nullable();
        });

        Schema::create('records', function (Blueprint $table) {
            $table->id();

            $table->string('title', 150);

            $table->text('description')->nullable();

            $table->string('status', 20)
                ->default('draft')
                ->index();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('records');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('permission_overrides');
        });
    }
};