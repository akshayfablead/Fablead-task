<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relationship_load_test_batches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('status', 20)->index();
            $table->unsignedInteger('requested_users');
            $table->unsignedInteger('requested_records');
            $table->unsignedInteger('created_users')->default(0);
            $table->unsignedInteger('created_records')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignUuid('load_test_batch_id')
                ->nullable()
                ->constrained('relationship_load_test_batches')
                ->restrictOnDelete();
        });

        Schema::table('records', function (Blueprint $table): void {
            $table->foreignUuid('load_test_batch_id')
                ->nullable()
                ->constrained('relationship_load_test_batches')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table): void {
            $table->dropForeign(['load_test_batch_id']);
            $table->dropColumn('load_test_batch_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['load_test_batch_id']);
            $table->dropColumn('load_test_batch_id');
        });

        Schema::dropIfExists('relationship_load_test_batches');
    }
};
