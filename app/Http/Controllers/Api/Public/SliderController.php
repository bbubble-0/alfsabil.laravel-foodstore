<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Http\Request;

class SliderController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        // get sliders
        $sliders = Slider::latest()->get();

        // return response JSON sliders
        return response()->json([
            'success' => true,
            'message' => 'List Slider',
            'data' => $sliders
        ]);
    }
}