<?php

namespace Database\Seeders;

use App\Models\CelebrationTemplate;
use Illuminate\Database\Seeder;

class CelebrationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Remove old abstract-colour templates that are no longer used
        CelebrationTemplate::whereIn('slug', [
            'classic', 'blush', 'midnight', 'forest', 'ocean', 'sunset',
        ])->delete();

        $templates = [
            [
                'name'               => 'Birthday',
                'slug'               => 'birthday',
                'icon'               => 'mdi-cake-variant',
                'description'        => 'Festive and warm',
                'page_bg'            => '#FFF7ED',
                'card_bg'            => '#FFFFFF',
                'text_primary'       => '#1C0A00',
                'text_secondary'     => '#92400E',
                'accent_color'       => '#F97316',
                'photo_border_style' => 'solid',
                'photo_border_color' => '#FDBA74',
                'photo_border_width' => 3,
                'wishes_layout'      => 'scroll',
                'sort_order'         => 0,
            ],
            [
                'name'               => 'Wedding',
                'slug'               => 'wedding',
                'icon'               => 'mdi-ring',
                'description'        => 'Elegant ivory & gold',
                'page_bg'            => '#FFFEF9',
                'card_bg'            => '#FFFFFF',
                'text_primary'       => '#2D2D2A',
                'text_secondary'     => '#8B7D6B',
                'accent_color'       => '#B8960C',
                'photo_border_style' => 'solid',
                'photo_border_color' => '#D4AF37',
                'photo_border_width' => 3,
                'wishes_layout'      => 'grid',
                'sort_order'         => 1,
            ],
            [
                'name'               => 'Golden Jubilee',
                'slug'               => 'golden-jubilee',
                'icon'               => 'mdi-trophy',
                'description'        => '50 years of glory',
                'page_bg'            => '#FDF8E8',
                'card_bg'            => '#FFFEF5',
                'text_primary'       => '#3D2900',
                'text_secondary'     => '#8B6914',
                'accent_color'       => '#C9910A',
                'photo_border_style' => 'solid',
                'photo_border_color' => '#D4A017',
                'photo_border_width' => 4,
                'wishes_layout'      => 'grid',
                'sort_order'         => 2,
            ],
            [
                'name'               => 'Silver Jubilee',
                'slug'               => 'silver-jubilee',
                'icon'               => 'mdi-medal',
                'description'        => '25 years & counting',
                'page_bg'            => '#F4F4F6',
                'card_bg'            => '#FFFFFF',
                'text_primary'       => '#1A1A2E',
                'text_secondary'     => '#4B5563',
                'accent_color'       => '#6B7280',
                'photo_border_style' => 'solid',
                'photo_border_color' => '#9CA3AF',
                'photo_border_width' => 3,
                'wishes_layout'      => 'scroll',
                'sort_order'         => 3,
            ],
            [
                'name'               => 'Baby Shower',
                'slug'               => 'baby-shower',
                'icon'               => 'mdi-baby-carriage',
                'description'        => 'Soft, sweet & joyful',
                'page_bg'            => '#FFF0F5',
                'card_bg'            => '#FFFFFF',
                'text_primary'       => '#4A1942',
                'text_secondary'     => '#9D6B97',
                'accent_color'       => '#EC4899',
                'photo_border_style' => 'solid',
                'photo_border_color' => '#F9A8D4',
                'photo_border_width' => 3,
                'wishes_layout'      => 'grid',
                'sort_order'         => 4,
            ],
            [
                'name'               => 'Graduation',
                'slug'               => 'graduation',
                'icon'               => 'mdi-school',
                'description'        => 'Academic navy & gold',
                'page_bg'            => '#EFF3FF',
                'card_bg'            => '#FFFFFF',
                'text_primary'       => '#1E3A8A',
                'text_secondary'     => '#3B5EA6',
                'accent_color'       => '#1D4ED8',
                'photo_border_style' => 'solid',
                'photo_border_color' => '#93C5FD',
                'photo_border_width' => 3,
                'wishes_layout'      => 'grid',
                'sort_order'         => 5,
            ],
        ];

        foreach ($templates as $data) {
            CelebrationTemplate::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
