<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_upload_mapping_presets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('importer_key');
            $table->json('mapping');
            $table->timestamps();

            $table->unique(['owner_id', 'importer_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_upload_mapping_presets');
    }
};
