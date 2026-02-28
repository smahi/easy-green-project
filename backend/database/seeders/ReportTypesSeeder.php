<?php

namespace Database\Seeders;

use App\Models\ReportType;
use Illuminate\Database\Seeder;

class ReportTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => [
                    'en' => 'Locust',
                    'fr' => 'Criquet pèlerin',
                    'ar' => 'الجراد الصحراوي',
                ],
                'description' => [
                    'en' => 'Swarms of locusts threatening crops.',
                    'fr' => 'Essaims de criquets menaçant les cultures.',
                    'ar' => 'أسراب الجراد التي تهدد المحاصيل.',
                ],
                'severity_level' => 5, // Emergency
                'color' => '#ef4444', // Red
                'icon' => 'locust.png',
            ],
            [
                'name' => [
                    'en' => 'Wheat Rust',
                    'fr' => 'Rouille du blé',
                    'ar' => 'صدأ القمح',
                ],
                'description' => [
                    'en' => 'Fungal disease affecting wheat leaves.',
                    'fr' => 'Maladie fongique affectant les feuilles de blé.',
                    'ar' => 'مرض فطري يصيب أوراق القمح.',
                ],
                'severity_level' => 4, // Critical
                'color' => '#f97316', // Orange
                'icon' => 'wheat-rust.png',
            ],
            [
                'name' => [
                    'en' => 'Mediterranean Fruit Fly',
                    'fr' => 'Mouche méditerranéenne des fruits',
                    'ar' => 'ذبابة الفاكهة',
                ],
                'description' => [
                    'en' => 'Pest attacking various fruits.',
                    'fr' => 'Ravageur attaquant divers fruits.',
                    'ar' => 'آفة تهاجم الفواكه المختلفة.',
                ],
                'severity_level' => 3, // High
                'color' => '#eab308', // Yellow
                'icon' => 'fruit-fly.png',
            ],
            [
                'name' => [
                    'en' => 'Fire Blight',
                    'fr' => 'Feu bactérien',
                    'ar' => 'اللفحة النارية',
                ],
                'description' => [
                    'en' => 'Bacterial disease affecting apple and pear trees.',
                    'fr' => 'Maladie bactérienne affectant les pommiers et les poiriers.',
                    'ar' => 'مرض بكتيري يصيب أشجار التفاح والكمثرى.',
                ],
                'severity_level' => 4, // Critical
                'color' => '#dc2626', // Dark Red
                'icon' => 'fire-blight.png',
            ],
            [
                'name' => [
                    'en' => 'Tomato Leaf Miner (Tuta Absoluta)',
                    'fr' => 'Mineuse de la tomate',
                    'ar' => 'توتا أبسولوتا (حفارة أوراق الطماطم)',
                ],
                'description' => [
                    'en' => 'Moth larva damaging tomato plants.',
                    'fr' => 'Larve de papillon endommageant les plants de tomates.',
                    'ar' => 'يرقة العثة تضر نباتات الطماطم.',
                ],
                'severity_level' => 3, // High
                'color' => '#d97706', // Amber
                'icon' => 'tuta-absoluta.png',
            ],
            [
                'name' => [
                    'en' => 'Drought Stress',
                    'fr' => 'Stress hydrique',
                    'ar' => 'الإجهاد المائي',
                ],
                'description' => [
                    'en' => 'Signs of water shortage in crops.',
                    'fr' => 'Signes de pénurie d\'eau dans les cultures.',
                    'ar' => 'علامات نقص المياه في المحاصيل.',
                ],
                'severity_level' => 2, // Medium
                'color' => '#3b82f6', // Blue
                'icon' => 'drought.png',
            ],
            [
                'name' => [
                    'en' => 'Aphids',
                    'fr' => 'Pucerons',
                    'ar' => 'المن',
                ],
                'description' => [
                    'en' => 'Small sap-sucking insects.',
                    'fr' => 'Petits insectes suceurs de sève.',
                    'ar' => 'حشرات صغيرة ماصة للنسغ.',
                ],
                'severity_level' => 2, // Medium
                'color' => '#84cc16', // Lime
                'icon' => 'aphids.png',
            ],
            [
                'name' => [
                    'en' => 'Date Palm Bayoud Disease',
                    'fr' => 'Bayoud du palmier dattier',
                    'ar' => 'مرض البيوض (نخيل التمر)',
                ],
                'description' => [
                    'en' => 'Serious fungal disease of date palms.',
                    'fr' => 'Maladie fongique grave des palmiers dattiers.',
                    'ar' => 'مرض فطري خطير يصيب نخيل التمر.',
                ],
                'severity_level' => 5, // Emergency
                'color' => '#7f1d1d', // Deep Red
                'icon' => 'bayoud.png',
            ],
        ];

        foreach ($types as $type) {
            ReportType::create($type);
        }
    }
}
