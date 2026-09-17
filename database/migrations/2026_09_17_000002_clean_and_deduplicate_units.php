<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Unit;

return new class extends Migration {
    public function up(): void
    {
        $targetUnits = [
            'Direktorat Jenderal Pembinaan Pelatihan Vokasi & Produktivitas',
            'Direktorat Jenderal Pembinaan Penempatan Tenaga Kerja & PKK',
            'Direktorat Jenderal Pembinaan Hubungan Industrial & Jamsosnaker',
            'Direktorat Jenderal Pembinaan Pengawasan Ketenagakerjaan & K3',
            'Inspektorat Jenderal',
            'Badan Perencanaan Pengembangan Ketenagakerjaan',
            'Staf Ahli Menteri Bidang Ekonomi Ketenagakerjaan',
            'Staf Ahli Menteri Bidang Hubungan Internasional',
            'Staf Ahli Menteri Bidang Hubungan Antarlembaga',
            'Staff Ahli Menteri Sosial, Politik, dan Kebijakan Publik',
            'PPSDM Ketenagakerjaan',
            'Pusat Pasar Kerja',
            'Biro Perencanaan & Manj. Kinerja',
            'Biro Keuangan & BMN',
            'Biro Organisasi & SDM Aparatur',
            'Biro Hukum',
            'Biro Umum',
            'Biro Kerja Sama',
            'Biro Hubungan Masyarakat',
            'Politeknik Ketenagakerjaan',
            'Bagian TU Pimpinan dan Protokol',
            'Subbagian TU Sekjen, SAM dan SKM',
        ];

        // 1. Normalize and deduplicate units by lowercase name
        $allUnits = DB::table('units')->orderBy('id', 'asc')->get();
        $grouped = [];

        foreach ($allUnits as $unit) {
            $normalized = trim(mb_strtolower($unit->unit_name));
            $grouped[$normalized][] = $unit;
        }

        foreach ($grouped as $normalizedName => $unitsList) {
            if (count($unitsList) > 1) {
                $primary = $unitsList[0];
                $duplicateIds = array_map(fn($u) => $u->id, array_slice($unitsList, 1));

                // Re-point all references to primary unit ID
                DB::table('letters')->whereIn('recipient_unit_id', $duplicateIds)->update(['recipient_unit_id' => $primary->id]);
                DB::table('letter_numbers')->whereIn('unit_id', $duplicateIds)->update(['unit_id' => $primary->id]);
                DB::table('dispositions')->whereIn('to_unit_id', $duplicateIds)->update(['to_unit_id' => $primary->id]);
                DB::table('letter_number_availability_batches')->whereIn('unit_id', $duplicateIds)->update(['unit_id' => $primary->id]);

                // Delete duplicates
                DB::table('units')->whereIn('id', $duplicateIds)->delete();
            }
        }

        // 2. Deactivate obsolete units not in official list
        DB::table('units')->whereNotIn('unit_name', $targetUnits)->update(['is_active' => false]);

        // 3. Ensure official units exist and are active
        foreach ($targetUnits as $unitName) {
            $existing = DB::table('units')->where('unit_name', $unitName)->first();
            if ($existing) {
                DB::table('units')->where('id', $existing->id)->update([
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('units')->insert([
                    'unit_name' => $unitName,
                    'pic_name' => 'Tata Usaha ' . $unitName,
                    'phone' => '081234567800',
                    'email' => strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $unitName)) . '@kemnaker.go.id',
                    'address' => 'Kementerian Ketenagakerjaan RI',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
    }
};
