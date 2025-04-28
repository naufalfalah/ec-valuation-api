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
        Schema::create('project_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->string('area')->nullable();
            $table->string('floor_range')->nullable();
            $table->string('no_of_units')->nullable();
            $table->string('contact_date')->nullable();
            $table->string('type_of_sale')->nullable();
            $table->string('price')->nullable();
            $table->string('property_type')->nullable();
            $table->string('district')->nullable();
            $table->string('type_of_area')->nullable();
            $table->string('tenure')->nullable();
            $table->unsignedBigInteger('project_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_transactions');
    }
};
