<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        if (Schema::hasTable('stilovis')) {
            $styles = [
                ['id' => 1, 'naziv' => 'Goli luk (BB)', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 2, 'naziv' => 'Zakrivljeni luk (RB)', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 3, 'naziv' => 'Tradicionalni luk (TB)', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 4, 'naziv' => 'Dugi luk (LB)', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 6, 'naziv' => 'Složeni luk (CU)', 'created_at' => $now, 'updated_at' => $now],
            ];

            DB::table('stilovis')->upsert($styles, ['id'], ['naziv', 'updated_at']);

            // Optional cleanup: remove "Standardni luk" only if not referenced.
            if (Schema::hasTable('rezultati_opcis')) {
                $isStyleUsed = DB::table('rezultati_opcis')->where('stil_id', 7)->exists();
                if (!$isStyleUsed) {
                    DB::table('stilovis')->where('id', 7)->delete();
                }
            }
        }

        if (Schema::hasTable('kategorijes')) {
            $categories = [
                ['id' => 1, 'spol' => 'M', 'naziv' => 'Seniori (M)', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 3, 'spol' => 'M', 'naziv' => 'Veterani (M50+)', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 4, 'spol' => 'Ž', 'naziv' => 'Seniorke (W)', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 5, 'spol' => 'Ž', 'naziv' => 'Veteranke (W50+)', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 6, 'spol' => 'Ž', 'naziv' => 'Kadetkinje (U18W)', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 7, 'spol' => 'M', 'naziv' => 'Kadeti (U18M)', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 8, 'spol' => 'M', 'naziv' => 'Dječaci do 14 (U15M)', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 9, 'spol' => 'Ž', 'naziv' => 'Djevojčice do 14 (U15W)', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 10, 'spol' => 'M', 'naziv' => 'Dječaci do 12 (U13M)', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 11, 'spol' => 'Ž', 'naziv' => 'Djevojčice do 12 (U13W)', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 12, 'spol' => 'M', 'naziv' => 'Juniori (U21M)', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 13, 'spol' => 'Ž', 'naziv' => 'Juniorke (U21W)', 'created_at' => $now, 'updated_at' => $now],
            ];

            DB::table('kategorijes')->upsert($categories, ['id'], ['spol', 'naziv', 'updated_at']);
        }

        if (Schema::hasTable('tipovi_turniras')) {
            $types = [
                ['id' => 1, 'naziv' => 'WA 2x18', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 2, 'naziv' => 'WA 1440', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 3, 'naziv' => 'WA 2x25+2x18', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 4, 'naziv' => 'WA 720', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 6, 'naziv' => '3D WA', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 7, 'naziv' => 'WA 900', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 8, 'naziv' => 'Field 12+12', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 9, 'naziv' => '2D', 'created_at' => $now, 'updated_at' => $now],
            ];

            DB::table('tipovi_turniras')->upsert($types, ['id'], ['naziv', 'updated_at']);
        }

        if (Schema::hasTable('polja_za_tipove_turniras')) {
            $fields = [
                ['id' => 7, 'naziv' => '1. krug', 'tipovi_turnira_id' => 1, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 8, 'naziv' => '2. krug', 'tipovi_turnira_id' => 1, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 9, 'naziv' => 'Ukupno', 'tipovi_turnira_id' => 1, 'created_at' => $now, 'updated_at' => $now],

                ['id' => 15, 'naziv' => '1. krug', 'tipovi_turnira_id' => 2, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 16, 'naziv' => '2. krug', 'tipovi_turnira_id' => 2, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 17, 'naziv' => '3. krug', 'tipovi_turnira_id' => 2, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 18, 'naziv' => '4. krug', 'tipovi_turnira_id' => 2, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 20, 'naziv' => 'Ukupno', 'tipovi_turnira_id' => 2, 'created_at' => $now, 'updated_at' => $now],

                ['id' => 26, 'naziv' => '1. krug (25)', 'tipovi_turnira_id' => 3, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 27, 'naziv' => '2. krug (25)', 'tipovi_turnira_id' => 3, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 28, 'naziv' => 'Ukupno (25)', 'tipovi_turnira_id' => 3, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 29, 'naziv' => '1. krug (18)', 'tipovi_turnira_id' => 3, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 30, 'naziv' => '2. krug (18)', 'tipovi_turnira_id' => 3, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 31, 'naziv' => 'Ukupno (18)', 'tipovi_turnira_id' => 3, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 32, 'naziv' => 'Ukupno', 'tipovi_turnira_id' => 3, 'created_at' => $now, 'updated_at' => $now],

                ['id' => 2, 'naziv' => '1. krug', 'tipovi_turnira_id' => 4, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 5, 'naziv' => '2. krug', 'tipovi_turnira_id' => 4, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 21, 'naziv' => 'Ukupno', 'tipovi_turnira_id' => 4, 'created_at' => $now, 'updated_at' => $now],

                ['id' => 10, 'naziv' => '11', 'tipovi_turnira_id' => 6, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 11, 'naziv' => '10', 'tipovi_turnira_id' => 6, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 12, 'naziv' => '8', 'tipovi_turnira_id' => 6, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 13, 'naziv' => '5', 'tipovi_turnira_id' => 6, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 14, 'naziv' => '0', 'tipovi_turnira_id' => 6, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 40, 'naziv' => 'Ukupno', 'tipovi_turnira_id' => 6, 'created_at' => $now, 'updated_at' => $now],

                ['id' => 33, 'naziv' => '1. krug', 'tipovi_turnira_id' => 7, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 34, 'naziv' => '2. krug', 'tipovi_turnira_id' => 7, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 35, 'naziv' => '3. krug', 'tipovi_turnira_id' => 7, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 37, 'naziv' => 'Ukupno', 'tipovi_turnira_id' => 7, 'created_at' => $now, 'updated_at' => $now],

                ['id' => 22, 'naziv' => 'Nepoznate', 'tipovi_turnira_id' => 8, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 24, 'naziv' => 'Poznate', 'tipovi_turnira_id' => 8, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 25, 'naziv' => 'Ukupno', 'tipovi_turnira_id' => 8, 'created_at' => $now, 'updated_at' => $now],

                ['id' => 41, 'naziv' => '15', 'tipovi_turnira_id' => 9, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 42, 'naziv' => '12', 'tipovi_turnira_id' => 9, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 43, 'naziv' => '7', 'tipovi_turnira_id' => 9, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 44, 'naziv' => '0', 'tipovi_turnira_id' => 9, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 45, 'naziv' => 'Ukupno', 'tipovi_turnira_id' => 9, 'created_at' => $now, 'updated_at' => $now],
            ];

            DB::table('polja_za_tipove_turniras')->upsert($fields, ['id'], ['naziv', 'tipovi_turnira_id', 'updated_at']);
        }
    }
}
