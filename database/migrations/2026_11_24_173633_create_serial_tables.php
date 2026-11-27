<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Serial numbers of the scales RSG imported. Each admin upload is a batch; every serial
 * number is an item of its batch. A serial number is unique across all batches.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serial_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();   // who uploaded it
            $table->string('file_name');
            $table->unsignedInteger('rows_total');
            $table->unsignedInteger('rows_added');
            $table->unsignedInteger('rows_skipped');
            $table->json('skipped')->nullable();    // first rows that were not loaded: [{line, serial, reason}]
            $table->timestamps();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serial_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scale_model_id')->constrained()->restrictOnDelete();
            $table->string('serial', 64)->unique();
            $table->string('model_text', 80);       // the model as written in the file, e.g. "RLS1530"
            $table->date('imported_on');
            $table->timestamps();

            $table->index(['scale_model_id', 'imported_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serial_numbers');
        Schema::dropIfExists('serial_batches');
    }
};
