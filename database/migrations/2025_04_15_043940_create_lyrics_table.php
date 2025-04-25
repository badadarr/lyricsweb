<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lyrics', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('artist');
            $table->text('lyric');
            $table->string('project_name'); // FK ke project_lyrics
            $table->string('language')->nullable(); // Kolom baru untuk bahasa
            $table->boolean('explicit')->default(false); // Kolom baru untuk konten dewasa
            $table->string('source')->nullable(); // Kolom baru untuk sumber lagu
            $table->string('tag')->nullable(); // Kolom baru untuk tag
            $table->boolean('priority')->default(false); // Kolom baru untuk prioritas
            $table->string('pic')->nullable(); // Kolom baru untuk nama PIC
            $table->boolean('done_publish')->default(false); // Kolom baru untuk status publish
            $table->softDeletes(); // Kolom deleted_at untuk soft delete
            $table->timestamps();
            // Foreign key constraints
            $table->foreign('project_name')->references('project_name')->on('project_lyrics')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lyrics');
    }
};