<?php

namespace App\Http\Controllers\Api\Public;

use Midtrans\Snap;
use App\Models\Cart;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CheckoutController extends Controller
{
    public $response;

    public static function middleware(): array
    {
        return [
            'auth:api',
        ];
    }

    public function __construct()
    {
        \Midtrans\Config::$serverKey = config('midtrans.server_key');
        \Midtrans\Config::$isProduction = config('midtrans.is_production');
        \Midtrans\Config::$isSanitized = config('midtrans.is_sanitized');
        \Midtrans\Config::$is3ds = config('midtrans.is_3ds');
    }

    public function store(Request $request)
    {
        $customer = auth()->guard('api')->user();

        // Kode pos fallback untuk testing lokasi mock
        $zipCode = $request->input('zip_code') ?? '00000';

        // Buat invoice
        $invoice = 'INV-' . mt_rand(1000, 9999);

        // Buat transaksi
        $transaction = Transaction::create([
            'customer_id'      => $customer->id,
            'invoice'          => $invoice,
            'province_name'    => $request->province_name,
            'city_name'       => $request->city_name,
            'district_name'    => $request->district_name,
            'subdistrict_name' => $request->subdistrict_name,
            'zip_code'        => $zipCode,
            'full_address'     => $request->full_address,
            'weight'           => $request->weight,
            'total'            => $request->total,
            'status'           => 'PENDING',
        ]);

        // Simpan data shipping
        $transaction->shipping()->create([
            'transaction_id'    => $transaction->id,
            'shipping_courier'  => $request->shipping_courier,
            'shipping_service'   => $request->shipping_service,
            'shipping_cost'     => $request->shipping_cost,
        ]);

        // Buat detail transaksi dan item Midtrans
        $item_details = [];

        $carts = Cart::with('product')
            ->where('customer_id', $customer->id)
            ->get();

        foreach ($carts as $cart) {
            $product = $cart->product;
            $subtotal = $product->price * $cart->qty;

            $transaction->transactionDetails()->create([
                'transaction_id' => $transaction->id,
                'product_id'     => $product->id,
                'qty'            => $cart->qty,
                'price'          => $subtotal,
            ]);

            $item_details[] = [
                'id'       => $product->id,
                'price'    => (int) $product->price,
                'quantity' => (int) $cart->qty,
                'name'     => $product->title,
            ];
        }

        // Detail ongkir
        $item_details[] = [
            'id'       => 'SHIPPING',
            'price'    => (int) $request->shipping_cost,
            'quantity' => 1,
            'name'     => 'Shipping Cost',
        ];

        // Payload Midtrans
        $payload = [
            'transaction_details' => [
                'order_id'    => $transaction->invoice,
                'gross_amount' => (int) $transaction->total,
            ],
            'customer_details' => [
                'first_name' => $customer->name,
                'email'      => $customer->email,
            ],
            'item_details' => $item_details,
        ];

        // Buat Snap Token Midtrans
        $snapToken = Snap::getSnapToken($payload);

        // Simpan token ke transaksi
        $transaction->snap_token = $snapToken;
        $transaction->save();

        // Hapus keranjang setelah token berhasil dibuat
        Cart::where('customer_id', $customer->id)->delete();

        // Response
        return response()->json([
            'success' => true,
            'message' => 'Checkout Berhasil',
            'data' => [
                'snap_token' => $snapToken,
            ],
        ]);
    }
}
