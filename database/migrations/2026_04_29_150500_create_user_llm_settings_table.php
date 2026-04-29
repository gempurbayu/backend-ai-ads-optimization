<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_llm_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('openai_compatible');
            $table->text('api_key_encrypted');
            $table->string('base_url')->default('https://api.openai.com/v1');
            $table->string('default_model')->default('gpt-4o-mini');
            $table->integer('timeout')->default(30);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_llm_settings');
    }
};
