<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general')
                  ->comment('general | mpesa | notifications | appearance');
            $table->boolean('is_secret')->default(false)
                  ->comment('Mask value in API responses if true');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('settings'); }
};