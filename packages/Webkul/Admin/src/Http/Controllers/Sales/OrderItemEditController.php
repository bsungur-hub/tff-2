<?php

namespace Webkul\Admin\Http\Controllers\Sales;

use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;

class OrderItemEditController extends Controller
{
    /**
     * Show the form for editing all items of an order.
     *
     * @param  int  $orderId
     * @return \Illuminate\View\View
     */
    public function edit($orderId)
    {
        // Order ve bağlı item’ları çekiyoruz
        $order = Order::with('items')->findOrFail($orderId);

        return view('admin::sales.orders.item-edit', compact('order'));
    }

    /**
     * Update all items of the order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $orderId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateMultiple(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);

        $data = $request->input('items', []);

        foreach ($order->items as $item) {
            if (isset($data[$item->id])) {
                $itemData = $data[$item->id];

                // Qty güncelle
                if (isset($itemData['quantity'])) {
                    $item->qty_ordered = (int) $itemData['quantity'];
                }

                // Additional'da sadece attributes güncelle
                if (!empty($itemData['additional'])) {
                    $decoded = json_decode($itemData['additional'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $additional = $item->additional ?? [];
                        $additional['attributes'] = $decoded;
                        $item->additional = $additional;
                    }
                }

                $item->save();
            }
        }

        session()->flash('success', 'Order items updated successfully.');

        return redirect()->route('admin.sales.orders.items.edit', [$orderId, $order->items->first()->id]);
    }



}
