<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('credential_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credential_id')->constrained()->onDelete('cascade');
            $table->foreignId('shared_by_user_id')->constrained('users')->onDelete('cascade');
            $table->string('shared_with_type');
            $table->unsignedBigInteger('shared_with_id');
            $table->enum('permission', ['read', 'write'])->default('read');
            $table->timestamps();
            $table->index(['shared_with_type', 'shared_with_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credential_shares');
    }
};
