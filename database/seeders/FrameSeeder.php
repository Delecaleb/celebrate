<?php

namespace Database\Seeders;

use App\Models\Frame;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class FrameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign keys to truncate safely
        Schema::disableForeignKeyConstraints();
        Frame::truncate();
        Schema::enableForeignKeyConstraints();

        $frames = [
            [
                'name' => 'Royal Gilded Pattern',
                'type' => 'css',
                'css_content' => 'padding: 16px; background-color: #1a1a1a; background-image: repeating-linear-gradient(45deg, #d4af37 0px, #d4af37 2px, transparent 0px, transparent 10px), repeating-linear-gradient(-45deg, #d4af37 0px, #d4af37 2px, #1a1a1a 0px, #1a1a1a 10px); border-radius: 28px; box-shadow: 0 10px 30px rgba(0,0,0,0.4);',
                'svg_content' => null,
                'preview_image' => 'royal_gilded.png'
            ],
            [
                'name' => 'Midnight Stars Damask',
                'type' => 'css',
                'css_content' => 'padding: 14px; background-color: #0b132b; background-image: radial-gradient(circle, #1c2541 10%, transparent 11%), radial-gradient(circle, #1c2541 10%, transparent 11%); background-size: 20px 20px; background-position: 0 0, 10px 10px; border-radius: 28px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 2px solid #5bc0be;',
                'svg_content' => null,
                'preview_image' => 'midnight_damask.png'
            ],
            [
                'name' => 'Baroque Mahogany',
                'type' => 'css',
                'css_content' => 'padding: 16px; background-color: #2c1d11; background-image: repeating-linear-gradient(45deg, #3d2a19 0px, #3d2a19 12px, #2c1d11 12px, #2c1d11 24px); border-radius: 28px; box-shadow: inset 0 0 20px rgba(0,0,0,0.8), 0 8px 24px rgba(0,0,0,0.3); border: 3px double #d4af37;',
                'svg_content' => null,
                'preview_image' => 'baroque_mahogany.png'
            ],
            [
                'name' => 'Premium White Lace',
                'type' => 'css',
                'css_content' => 'padding: 12px; background-color: #ffffff; background-image: radial-gradient(circle at 100% 150%, #f3e9dc 24%, #ebd8c3 25%, #ebd8c3 28%, #ebd8c3 29%, #ffffff 30%); background-size: 20px 20px; border-radius: 28px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); border: 2px solid #e0c3fc;',
                'svg_content' => null,
                'preview_image' => 'white_lace.png'
            ],
            [
                'name' => 'Vintage Paper scroll',
                'type' => 'css',
                'css_content' => 'padding: 15px; background-color: #f4ecd8; background-image: radial-gradient(#dfd3b6 20%, transparent 20%), radial-gradient(#dfd3b6 20%, transparent 20%); background-size: 16px 16px; background-position: 0 0, 8px 8px; border-radius: 28px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); border: 2px solid #c7b897;',
                'svg_content' => null,
                'preview_image' => 'vintage_paper.png'
            ],
            [
                'name' => 'Imperial Gold Lattice',
                'type' => 'svg',
                'css_content' => 'border-radius: 28px;',
                'svg_content' => '<svg viewBox="0 0 400 500" fill="none" xmlns="http://www.w3.org/2000/svg" class="absolute inset-0 w-full h-full pointer-events-none z-10"><path d="M 0 50 C 0 20, 20 0, 50 0 L 80 0 C 70 15, 55 25, 35 35 C 25 55, 15 70, 0 80 Z" fill="#d4af37" opacity="0.95"/><path d="M 400 50 C 400 20, 380 0, 350 0 L 320 0 C 330 15, 345 25, 365 35 C 375 55, 385 70, 400 80 Z" fill="#d4af37" opacity="0.95"/><path d="M 0 450 C 0 480, 20 500, 50 500 L 80 500 C 70 485, 55 475, 35 465 C 25 445, 15 430, 0 420 Z" fill="#d4af37" opacity="0.95"/><path d="M 400 450 C 400 480, 380 500, 350 500 L 320 500 C 330 485, 345 475, 365 465 C 375 445, 385 430, 400 420 Z" fill="#d4af37" opacity="0.95"/><rect x="14" y="14" width="372" height="472" rx="18" stroke="#d4af37" stroke-width="2.5" opacity="0.85"/><rect x="18" y="18" width="364" height="464" rx="14" stroke="#d4af37" stroke-width="1" stroke-dasharray="5 5" opacity="0.6"/></svg>',
                'preview_image' => 'gold_lattice.png'
            ],
            [
                'name' => 'Royal Ribbon Bow',
                'type' => 'svg',
                'css_content' => 'border-radius: 28px;',
                'svg_content' => '<svg viewBox="0 0 400 500" fill="none" xmlns="http://www.w3.org/2000/svg" class="absolute inset-0 w-full h-full pointer-events-none z-10"><rect x="12" y="12" width="376" height="476" rx="20" stroke="#d4af37" stroke-width="6" opacity="0.95"/><path d="M 175 12 C 150 -12, 140 25, 200 25 C 260 25, 250 -12, 225 12 Z" fill="#d4af37"/><circle cx="200" cy="20" r="8" fill="#aa7c11"/><path d="M 185 24 Q 150 50, 160 80" stroke="#d4af37" stroke-width="3" fill="none"/><path d="M 215 24 Q 250 50, 240 80" stroke="#d4af37" stroke-width="3" fill="none"/></svg>',
                'preview_image' => 'ribbon_bow.png'
            ],
            [
                'name' => 'Chic Floral Vine',
                'type' => 'svg',
                'css_content' => 'border-radius: 28px;',
                'svg_content' => '<svg viewBox="0 0 400 500" fill="none" xmlns="http://www.w3.org/2000/svg" class="absolute inset-0 w-full h-full pointer-events-none z-10"><path d="M 12 12 Q 100 -5, 200 12 T 388 12" stroke="#2e7d32" stroke-width="3.5" fill="none" opacity="0.85"/><circle cx="60" cy="11" r="7" fill="#d81b60"/><circle cx="160" cy="13" r="6" fill="#d81b60"/><circle cx="260" cy="13" r="6" fill="#d81b60"/><circle cx="340" cy="11" r="7" fill="#d81b60"/><path d="M 12 488 Q 100 505, 200 488 T 388 488" stroke="#2e7d32" stroke-width="3.5" fill="none" opacity="0.85"/><circle cx="80" cy="491" r="7" fill="#d81b60"/><circle cx="320" cy="491" r="7" fill="#d81b60"/></svg>',
                'preview_image' => 'floral_vine.png'
            ],
            [
                'name' => 'Art Deco Corners',
                'type' => 'svg',
                'css_content' => 'border-radius: 28px;',
                'svg_content' => '<svg viewBox="0 0 400 500" fill="none" xmlns="http://www.w3.org/2000/svg" class="absolute inset-0 w-full h-full pointer-events-none z-10"><path d="M 8 8 L 45 8 L 45 45 L 8 45 Z M 16 16 L 37 16 L 37 37 L 16 37 Z" fill="#d4af37"/><path d="M 392 8 L 355 8 L 355 45 L 392 45 Z M 384 16 L 363 16 L 363 37 L 384 37 Z" fill="#d4af37"/><path d="M 8 492 L 45 492 L 45 455 L 8 455 Z M 16 484 L 37 484 L 37 463 L 16 463 Z" fill="#d4af37"/><path d="M 392 492 L 355 492 L 355 455 L 392 455 Z M 384 484 L 363 484 L 363 463 L 384 463 Z" fill="#d4af37"/><rect x="12" y="12" width="376" height="476" rx="20" stroke="#d4af37" stroke-width="2" opacity="0.75"/></svg>',
                'preview_image' => 'art_deco.png'
            ],
            [
                'name' => 'Elegant Golden Sparkles',
                'type' => 'svg',
                'css_content' => 'border-radius: 28px;',
                'svg_content' => '<svg viewBox="0 0 400 500" fill="none" xmlns="http://www.w3.org/2000/svg" class="absolute inset-0 w-full h-full pointer-events-none z-10"><path d="M40 30 L43 39 L52 39 L45 45 L48 54 L40 48 L32 54 L35 45 L28 39 L37 39 Z" fill="#FBBF24"/><path d="M360 40 L362 47 L370 47 L364 52 L366 60 L360 55 L354 60 L356 52 L350 47 L358 47 Z" fill="#FBBF24" opacity="0.85"/><path d="M50 440 L52 447 L60 447 L54 452 L56 460 L50 455 L44 460 L46 452 L40 447 L48 447 Z" fill="#FBBF24" opacity="0.9"/><path d="M350 430 L353 439 L362 439 L355 445 L358 454 L350 448 L342 454 L345 445 L338 439 L347 439 Z" fill="#FBBF24"/><rect x="15" y="15" width="370" height="470" rx="20" stroke="#FBBF24" stroke-width="1.5" stroke-dasharray="8 6" opacity="0.7"/></svg>',
                'preview_image' => 'golden_sparkles.png'
            ]
        ];

        foreach ($frames as $frame) {
            Frame::updateOrCreate(['name' => $frame['name']], $frame);
        }
    }
}
?>
