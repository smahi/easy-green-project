<?php

namespace Database\Seeders;

use App\Models\Wilaya;
use Illuminate\Database\Seeder;

class WilayasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wilayas = [
            ['code' => '01', 'name' => ['ar' => 'أدرار', 'en' => 'Adrar', 'fr' => 'Adrar']],
            ['code' => '02', 'name' => ['ar' => 'الشلف', 'en' => 'Chlef', 'fr' => 'Chlef']],
            ['code' => '03', 'name' => ['ar' => 'الأغواط', 'en' => 'Laghouat', 'fr' => 'Laghouat']],
            ['code' => '04', 'name' => ['ar' => 'أم البواقي', 'en' => 'Oum El Bouaghi', 'fr' => 'Oum El Bouaghi']],
            ['code' => '05', 'name' => ['ar' => 'باتنة', 'en' => 'Batna', 'fr' => 'Batna']],
            ['code' => '06', 'name' => ['ar' => 'بجاية', 'en' => 'Béjaïa', 'fr' => 'Béjaïa']],
            ['code' => '07', 'name' => ['ar' => 'بسكرة', 'en' => 'Biskra', 'fr' => 'Biskra']],
            ['code' => '08', 'name' => ['ar' => 'بشار', 'en' => 'Béchar', 'fr' => 'Béchar']],
            ['code' => '09', 'name' => ['ar' => 'البليدة', 'en' => 'Blida', 'fr' => 'Blida']],
            ['code' => '10', 'name' => ['ar' => 'البويرة', 'en' => 'Bouira', 'fr' => 'Bouira']],
            ['code' => '11', 'name' => ['ar' => 'تمنراست', 'en' => 'Tamanrasset', 'fr' => 'Tamanrasset']],
            ['code' => '12', 'name' => ['ar' => 'تبسة', 'en' => 'Tébessa', 'fr' => 'Tébessa']],
            ['code' => '13', 'name' => ['ar' => 'تلمسان', 'en' => 'Tlemcen', 'fr' => 'Tlemcen']],
            ['code' => '14', 'name' => ['ar' => 'تيارت', 'en' => 'Tiaret', 'fr' => 'Tiaret']],
            ['code' => '15', 'name' => ['ar' => 'تيزي وزو', 'en' => 'Tizi Ouzou', 'fr' => 'Tizi Ouzou']],
            ['code' => '16', 'name' => ['ar' => 'الجزائر', 'en' => 'Algiers', 'fr' => 'Alger']],
            ['code' => '17', 'name' => ['ar' => 'الجلفة', 'en' => 'Djelfa', 'fr' => 'Djelfa']],
            ['code' => '18', 'name' => ['ar' => 'جيجل', 'en' => 'Jijel', 'fr' => 'Jijel']],
            ['code' => '19', 'name' => ['ar' => 'سطيف', 'en' => 'Sétif', 'fr' => 'Sétif']],
            ['code' => '20', 'name' => ['ar' => 'سعيدة', 'en' => 'Saïda', 'fr' => 'Saïda']],
            ['code' => '21', 'name' => ['ar' => 'سكيكدة', 'en' => 'Skikda', 'fr' => 'Skikda']],
            ['code' => '22', 'name' => ['ar' => 'سيدي بلعباس', 'en' => 'Sidi Bel Abbès', 'fr' => 'Sidi Bel Abbès']],
            ['code' => '23', 'name' => ['ar' => 'عنابة', 'en' => 'Annaba', 'fr' => 'Annaba']],
            ['code' => '24', 'name' => ['ar' => 'قالمة', 'en' => 'Guelma', 'fr' => 'Guelma']],
            ['code' => '25', 'name' => ['ar' => 'قسنطينة', 'en' => 'Constantine', 'fr' => 'Constantine']],
            ['code' => '26', 'name' => ['ar' => 'المدية', 'en' => 'Médéa', 'fr' => 'Médéa']],
            ['code' => '27', 'name' => ['ar' => 'مستغانم', 'en' => 'Mostaganem', 'fr' => 'Mostaganem']],
            ['code' => '28', 'name' => ['ar' => 'المسيلة', 'en' => 'M\'Sila', 'fr' => 'M\'Sila']],
            ['code' => '29', 'name' => ['ar' => 'معسكر', 'en' => 'Mascara', 'fr' => 'Mascara']],
            ['code' => '30', 'name' => ['ar' => 'ورقلة', 'en' => 'Ouargla', 'fr' => 'Ouargla']],
            ['code' => '31', 'name' => ['ar' => 'وهران', 'en' => 'Oran', 'fr' => 'Oran']],
            ['code' => '32', 'name' => ['ar' => 'البيض', 'en' => 'El Bayadh', 'fr' => 'El Bayadh']],
            ['code' => '33', 'name' => ['ar' => 'إليزي', 'en' => 'Illizi', 'fr' => 'Illizi']],
            ['code' => '34', 'name' => ['ar' => 'برج بوعريريج', 'en' => 'Bordj Bou Arréridj', 'fr' => 'Bordj Bou Arréridj']],
            ['code' => '35', 'name' => ['ar' => 'بومرداس', 'en' => 'Boumerdès', 'fr' => 'Boumerdès']],
            ['code' => '36', 'name' => ['ar' => 'الطرف', 'en' => 'El Tarf', 'fr' => 'El Tarf']],
            ['code' => '37', 'name' => ['ar' => 'تندوف', 'en' => 'Tindouf', 'fr' => 'Tindouf']],
            ['code' => '38', 'name' => ['ar' => 'تيسمسيلت', 'en' => 'Tissemsilt', 'fr' => 'Tissemsilt']],
            ['code' => '39', 'name' => ['ar' => 'الوادي', 'en' => 'El Oued', 'fr' => 'El Oued']],
            ['code' => '40', 'name' => ['ar' => 'خنشلة', 'en' => 'Khenchela', 'fr' => 'Khenchela']],
            ['code' => '41', 'name' => ['ar' => 'سوق أهراس', 'en' => 'Souk Ahras', 'fr' => 'Souk Ahras']],
            ['code' => '42', 'name' => ['ar' => 'تيبازة', 'en' => 'Tipaza', 'fr' => 'Tipaza']],
            ['code' => '43', 'name' => ['ar' => 'ميلة', 'en' => 'Mila', 'fr' => 'Mila']],
            ['code' => '44', 'name' => ['ar' => 'عين الدفلى', 'en' => 'Aïn Defla', 'fr' => 'Aïn Defla']],
            ['code' => '45', 'name' => ['ar' => 'النعامة', 'en' => 'Naâma', 'fr' => 'Naâma']],
            ['code' => '46', 'name' => ['ar' => 'عين تموشنت', 'en' => 'Aïn Témouchent', 'fr' => 'Aïn Témouchent']],
            ['code' => '47', 'name' => ['ar' => 'غرداية', 'en' => 'Ghardaïa', 'fr' => 'Ghardaïa']],
            ['code' => '48', 'name' => ['ar' => 'غليزان', 'en' => 'Relizane', 'fr' => 'Relizane']],
            ['code' => '49', 'name' => ['ar' => 'تيميمون', 'en' => 'Timimoun', 'fr' => 'Timimoun']],
            ['code' => '50', 'name' => ['ar' => 'برج باجي مختار', 'en' => 'Bordj Badji Mokhtar', 'fr' => 'Bordj Badji Mokhtar']],
            ['code' => '51', 'name' => ['ar' => 'أولاد جلال', 'en' => 'Ouled Djellal', 'fr' => 'Ouled Djellal']],
            ['code' => '52', 'name' => ['ar' => 'بني عباس', 'en' => 'Béni Abbès', 'fr' => 'Béni Abbès']],
            ['code' => '53', 'name' => ['ar' => 'عين صالح', 'en' => 'In Salah', 'fr' => 'In Salah']],
            ['code' => '54', 'name' => ['ar' => 'عين قزام', 'en' => 'In Guezzam', 'fr' => 'In Guezzam']],
            ['code' => '55', 'name' => ['ar' => 'تقرت', 'en' => 'Touggourt', 'fr' => 'Touggourt']],
            ['code' => '56', 'name' => ['ar' => 'جانـت', 'en' => 'Djanet', 'fr' => 'Djanet']],
            ['code' => '57', 'name' => ['ar' => 'المغير', 'en' => 'El M\'Ghair', 'fr' => 'El M\'Ghair']],
            ['code' => '58', 'name' => ['ar' => 'المنيعة', 'en' => 'El Meniaa', 'fr' => 'El Meniaa']],
        ];

        foreach ($wilayas as $wilaya) {
            Wilaya::firstOrCreate(
                ['code' => $wilaya['code']],
                ['name' => $wilaya['name'], 'is_active' => true]
            );
        }
    }
}
