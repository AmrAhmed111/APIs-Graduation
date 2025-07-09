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
        Schema::create('medical_test_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('pat_id')->nullable();
            $table->unsignedBigInteger('doc_id');
            $table->string('result_file'); // Path to the PDF file
            $table->text('notes')->nullable(); // Optional notes about the result
            $table->timestamps();

            $table->foreign('appointment_id')->references('id')->on('medical_test_appointments')->onDelete('cascade');
            $table->foreign('pat_id')->references('id')->on('patients')->onDelete('set null');
            $table->foreign('doc_id')->references('id')->on('doctors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_test_results');
    }
};
