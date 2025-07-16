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
        Schema::create('wrokflow_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_workflow_id')->constrained('document_workflows');
            $table->foreignId('user_id')->constrained('users');
            $table->string('action'); // approve, reject, comment
            $table->text('comments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wrokflow_actions');
    }
};
