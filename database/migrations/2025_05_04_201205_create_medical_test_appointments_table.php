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
        Schema::create('medical_test_appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pat_id')->nullable();
            $table->unsignedBigInteger('test_id');
            $table->unsignedBigInteger('doc_id');
            $table->time('appoint_time');
            $table->date('appoint_date');
            $table->timestamps();

            $table->foreign('pat_id')->references('id')->on('patients')->onDelete('set null');
            $table->foreign('test_id')->references('id')->on('medical_tests')->onDelete('cascade');
            $table->foreign('doc_id')->references('id')->on('doctors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_test_appointments');
    }
};
