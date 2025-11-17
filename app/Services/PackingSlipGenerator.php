<?php
namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PackingSlipGenerator
{
    /**
     * @param \Webkul\Sales\Models\Order $order
     * @return \Barryvdh\DomPDF\PDF
     */
    public static function makePdf($order)
    {
        $cart = \Webkul\Checkout\Models\Cart::find($order->cart_id ?? null);

        // Blade view'a hem order hem cart objesini gönderiyoruz
        $pdf = Pdf::loadView('admin.emails.packing_slip', [
            'order' => $order,
            'cart'  => $cart,
        ])->setPaper('a4', 'portrait');

        return $pdf;
    }
}
