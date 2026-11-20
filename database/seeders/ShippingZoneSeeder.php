<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ShippingZone;

class ShippingZoneSeeder extends Seeder
{
    public function run(): void
    {
        $basePath = storage_path('app/data/');

        $provinceFile = fopen($basePath . 'provinces.csv', 'r');
        $regencyFile = fopen($basePath . 'regencies.csv', 'r');
        $districtFile = fopen($basePath . 'districts.csv', 'r');

        if (!$provinceFile || !$regencyFile || !$districtFile) {
            throw new \RuntimeException(
                'File CSV wilayah Indonesia tidak ditemukan.'
            );
        }

        // Membaca data provinsi
        $provinces = [];

        fgetcsv($provinceFile);

        while (($row = fgetcsv($provinceFile)) !== false) {
            $provinces[$row[0]] = $row[1];
        }

        fclose($provinceFile);

        // Membaca data kabupaten dan kota
        $regencies = [];

        fgetcsv($regencyFile);

        while (($row = fgetcsv($regencyFile)) !== false) {
            $regencies[$row[0]] = [
                'province_id' => $row[1],
                'name' => $row[2],
            ];
        }

        fclose($regencyFile);

        // Membuat satu zona untuk seluruh Indonesia
        $zone = ShippingZone::updateOrCreate(
            ['name' => 'Seluruh Indonesia'],
            [
                'shipping_cost' => 15000,
                'is_active' => true,
            ]
        );

        // Membaca dan memasukkan kecamatan
        fgetcsv($districtFile);

        $rows = [];
        $total = 0;

        while (($row = fgetcsv($districtFile)) !== false) {
            $districtId = $row[0];
            $regencyId = $row[1];
            $districtName = $row[2];

            if (!isset($regencies[$regencyId])) {
                continue;
            }

            $regency = $regencies[$regencyId];
            $provinceId = $regency['province_id'];

            if (!isset($provinces[$provinceId])) {
                continue;
            }

            $rows[] = [
                'shipping_zone_id' => $zone->id,
                'province_name' => $provinces[$provinceId],
                'city_name' => $regency['name'],
                'district_name' => $districtName,
                'subdistrict_name' => null,
                'zip_code' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($rows) >= 500) {
                DB::table('shipping_destinations')->upsert(
                    $rows,
                    [
                        'province_name',
                        'city_name',
                        'district_name',
                    ],
                    [
                        'shipping_zone_id',
                        'updated_at',
                    ]
                );

                $total += count($rows);
                $rows = [];
            }
        }

        fclose($districtFile);

        // Memasukkan sisa data
        if (!empty($rows)) {
            DB::table('shipping_destinations')->upsert(
                $rows,
                [
                    'province_name',
                    'city_name',
                    'district_name',
                ],
                [
                    'shipping_zone_id',
                    'updated_at',
                ]
            );

            $total += count($rows);
        }

        // Menghapus zona lama yang tidak digunakan
        ShippingZone::where('id', '!=', $zone->id)->delete();

        echo "\nBerhasil memasukkan {$total} data kecamatan.\n";
        echo "Zona: Seluruh Indonesia\n";
        echo "Ongkir: Rp15.000\n";
    }
}
