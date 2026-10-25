<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\User;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RestaurantTableQrCodeController extends Controller
{
    public function show(Request $request, Restaurant $restaurant, int $table): View
    {
        $user = $request->user();

        abort_unless(
            $user instanceof User
                && $user->restaurants()
                    ->whereKey($restaurant->getKey())
                    ->wherePivotIn('role', ['owner', 'manager'])
                    ->exists(),
            403,
        );

        abort_unless($restaurant->is_active, 404);

        $restaurantTable = $restaurant->tables()
            ->whereKey($table)
            ->where('is_active', true)
            ->firstOrFail();

        $menuUrl = route('public.menu.table', [
            'restaurant' => $restaurant->slug,
            'uuid' => $restaurantTable->uuid,
        ]);
        $qrCodeDataUri = (string) (new QRCode(new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'outputBase64' => true,
        ])))->render($menuUrl);

        return view('admin.restaurant-tables.qr-code', [
            'restaurant' => $restaurant,
            'table' => $restaurantTable,
            'menuUrl' => $menuUrl,
            'qrCodeDataUri' => $qrCodeDataUri,
        ]);
    }
}
