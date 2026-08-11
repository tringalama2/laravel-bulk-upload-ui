<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_upload_batches', function (Blueprint $table) {
            $table->id();
            $table->string('importer_key')->index();
            $table->unsignedBigInteger('uploaded_by')->nullable()->index();
            $table->string('original_filename');
            $table->string('disk');
            $table->string('path');
            $table->string('status')->default('uploaded')->index();
            $table->json('headings')->nullable();
            $table->json('sample_rows')->nullable();
            $table->json('column_mapping')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_upload_batches');
    }
};
