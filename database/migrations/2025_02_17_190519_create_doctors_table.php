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
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('firstName');
            $table->string('lastName');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('gender', ['male', 'female']);
            $table->string('phoneNumber')->nullable();
            $table->decimal('rating', 3, 1)->nullable()->default(0);
            $table->unsignedBigInteger('spec_id'); // Foreign key for specialization
            $table->unsignedBigInteger('user_id'); // Foreign key for user
            $table->unsignedBigInteger('city_id'); // Foreign key for city
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('spec_id')->references('id')->on('specializations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::dropIfExists('doctors');
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropForeign(['spec_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['city_id']);
            $table->dropColumn('spec_id');
            $table->dropColumn('user_id');
            $table->dropColumn('city_id');
        });
    }
};
