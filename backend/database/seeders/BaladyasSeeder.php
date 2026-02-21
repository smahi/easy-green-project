<?php

namespace Database\Seeders;

use App\Models\Baladya;
use App\Models\Wilaya;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class BaladyasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = File::get(database_path('seeders/data/Commune_Of_Algeria.json'));
        $baladyas = json_decode($json, true);

        // Check for JSON errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON decode error: ' . json_last_error_msg());
        }

        if (! is_array($baladyas)) {
            throw new \RuntimeException('Invalid JSON data format: expected an array.');
        }

        foreach ($baladyas as $baladya) {
            if (! isset($baladya['wilaya_id'])) {
                continue;
            }
            $wilayaId = (int) $baladya['wilaya_id'];
            
            // In WilayasSeeder we created Wilayas using 'code' which matches 01, 02...
            // But the ID in database might be different if sequences were used (though we used 1-58).
            // Let's rely on the wilaya_id from JSON being the code of the Wilaya.
            // We should find the wilaya by code.
            
            // Format code to be 2 digits (e.g. 1 -> 01) if necessary
            $wilayaCode = str_pad($baladya['wilaya_id'], 2, '0', STR_PAD_LEFT);
            
            $wilaya = Wilaya::where('code', $wilayaCode)->first();

            if ($wilaya) {
                Baladya::firstOrCreate(
                    ['code' => $baladya['post_code']],
                    [
                        'wilaya_id' => $wilaya->id,
                        'name' => [
                            'en' => $baladya['name'],
                            'fr' => $baladya['name'],
                            'ar' => $baladya['ar_name'],
                        ],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
