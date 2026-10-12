<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_imports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('original_name');
            $table->string('file_path');
            $table->string('status', 20)->index();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('total_rows')->default(0);
            $table->unsignedBigInteger('processed_rows')->default(0);
            $table->unsignedBigInteger('imported_rows')->default(0);
            $table->unsignedBigInteger('skipped_rows')->default(0);
            $table->unsignedBigInteger('failed_rows')->default(0);
            $table->unsignedBigInteger('next_byte_offset')->default(0);
            $table->unsignedBigInteger('next_row_number')->default(2);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->timestamps();
        });

        Schema::create('user_import_failures', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('user_import_id')
                ->constrained('user_imports')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('row_number');
            $table->string('reason');
            $table->timestamps();
            $table->unique(['user_import_id', 'row_number']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('import_batch_id')
                ->nullable()
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['import_batch_id']);
            $table->dropColumn('import_batch_id');
        });

        Schema::dropIfExists('user_import_failures');
        Schema::dropIfExists('user_imports');
    }
};
