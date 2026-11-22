<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $coupon = Coupon::where('code', strtoupper(trim($data['code'])))->first();
        if (! $coupon) {
            return response()->json(['message' => 'Invalid code.'], 422);
        }

        [$discount, $error] = $coupon->discountFor((float) $data['subtotal']);
        if ($error) {
            return response()->json(['message' => $error], 422);
        }

        return response()->json(['code' => $coupon->code, 'discount' => $discount]);
    }
}
