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
        Schema::create('doctor_informations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('experience')->nullable();
            $table->unsignedInteger('number_of_patients')->default(0);
            $table->text('about')->nullable();
            $table->json('schedule')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->unsignedBigInteger('doctor_id');
            $table->timestamps();

            // Foreign Key Constraint
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_informations');
    }
};
