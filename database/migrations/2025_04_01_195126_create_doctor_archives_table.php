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
        Schema::create('doctor_archives', function (Blueprint $table) {
            $table->id();
            $table->string('firstName');
            $table->string('lastName');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('gender', ['male', 'female']);
            $table->string('phoneNumber')->nullable();
            $table->decimal('rating', 3, 1)->nullable()->default(0);
            $table->unsignedBigInteger('spec_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('spec_id')->references('id')->on('specializations')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_archives');
    }
};
