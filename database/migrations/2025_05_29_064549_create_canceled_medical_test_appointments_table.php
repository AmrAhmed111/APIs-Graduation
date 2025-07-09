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
        Schema::create('canceled_medical_test_appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pat_id');
            $table->unsignedBigInteger('test_id');
            $table->unsignedBigInteger('doc_id');
            $table->date('appoint_date');
            $table->time('appoint_time');
            $table->timestamp('canceled_at')->useCurrent(); // When the appointment was canceled
            $table->timestamps();

            $table->foreign('pat_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('test_id')->references('id')->on('medical_tests')->onDelete('cascade');
            $table->foreign('doc_id')->references('id')->on('doctors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('canceled_medical_test_appointments');
    }
};
