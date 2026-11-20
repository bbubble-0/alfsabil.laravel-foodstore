<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\ShippingDestination;
use Illuminate\Http\Request;

class RajaOngkirController extends Controller
{
    // PENCARIAN LOKASI
    public function searchDestination(Request $request)
    {
        $request->validate([
            'search' => ['required', 'string', 'min:2'],
        ]);

        $search = $request->search;

        $destinations = ShippingDestination::with('shippingZone')
            ->whereHas('shippingZone', function ($query) {
                $query->where('is_active', true);
            })
            ->where(function ($query) use ($search) {
                $query->where('city_name', 'LIKE', "%{$search}%")
                    ->orWhere('district_name', 'LIKE', "%{$search}%")
                    ->orWhere('subdistrict_name', 'LIKE', "%{$search}%")
                    ->orWhere('province_name', 'LIKE', "%{$search}%");
            })
            ->limit(50)
            ->get();

        $result = $destinations->map(function ($destination) {
            return [
                'id' => $destination->id,
                'label' => $destination->district_name
                    . ', ' . $destination->city_name
                    . ', ' . $destination->province_name,
                'province_name' => $destination->province_name,
                'city_name' => $destination->city_name,
                'district_name' => $destination->district_name,
                'subdistrict_name' => $destination->subdistrict_name,
                'zip_code' => $destination->zip_code,
                'shipping_cost' => $destination->shippingZone->shipping_cost,
            ];
        });

        return response()->json($result);
    }

    // MENGHITUNG ONGKIR
    public function checkOngkir(Request $request)
    {
        $request->validate([
            'destination' => ['required', 'integer', 'exists:shipping_destinations,id'],
            'weight' => ['required', 'integer', 'min:1'],
            'courier' => ['nullable', 'string'],
        ]);

        $destination = ShippingDestination::with('shippingZone')
            ->whereHas('shippingZone', function ($query) {
                $query->where('is_active', true);
            })
            ->findOrFail($request->destination);

        $shippingCost = $destination->shippingZone->shipping_cost;

        return response()->json([
            [
                'service' => 'REG',
                'description' => 'Pengiriman reguler',
                'cost' => $shippingCost,
                'etd' => '1-2 hari',
            ],
        ]);
    }
}
