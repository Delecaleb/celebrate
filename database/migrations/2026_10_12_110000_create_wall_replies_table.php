<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Replies to a post on the wishes wall. comment_replies cannot hold these — its
| wish_id is a foreign key to the registry — so wall posts get their own table,
| and comments.reply_count finally has something to count.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wall_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_name', 120)->nullable();
            $table->text('message');
            $table->timestamps();

            $table->index(['comment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wall_replies');
    }
};
