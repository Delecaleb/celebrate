<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| reaction_type was an enum of like/love/clap/fire. The wall now offers more
| (celebrate, laugh, touched), and an enum would need a migration for every
| new one — so it becomes a short string, and the controller keeps the list.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reactions', function (Blueprint $table) {
            $table->string('reaction_type', 30)->default('love')->change();
        });
    }

    public function down(): void
    {
        Schema::table('reactions', function (Blueprint $table) {
            $table->enum('reaction_type', ['like', 'love', 'clap', 'fire'])->default('like')->change();
        });
    }
};
