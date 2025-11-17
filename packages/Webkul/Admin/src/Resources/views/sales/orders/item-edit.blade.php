<x-admin::layouts>
    <x-slot:title>
        Edit Order #{{ $order->id }} Items
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap mb-4">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Editing Items for Order #{{ $order->id }}
        </p>

        <a href="{{ route('admin.sales.orders.view', $order->id) }}"
           class="inline-block px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded shadow hover:bg-gray-100 dark:text-white dark:border-gray-600 dark:hover:bg-gray-800 transition">
            <span class="icon-order-back mr-2" style="font-size: 1.2em;"></span> Back to Order
        </a>
    </div>

    <form method="POST" action="{{ route('admin.sales.orders.items.update-multiple', $order->id) }}">
        @csrf

        @foreach($order->items as $item)
            <div class="border rounded p-4 mb-4 flex gap-4">
                <!-- Product Image -->
                <div class="w-10 h-10 flex-shrink-0">
                    @if($item->product)
                        @php
                            $image = $item->product->getTypeInstance()->getBaseImage($item);
                        @endphp
                        <img src="{{ $image['small_image_url'] ?? '' }}"
                             alt="{{ $item->name }}"
                             class="w-full h-full object-cover rounded">
                    @endif
                </div>

                <div class="flex-1">
                    <h3 class="font-semibold text-lg mb-2">
                        Item #{{ $item->id }}: {{ $item->name }}
                    </h3>

                    <!-- Quantity -->
                    <label class="block font-medium text-gray-700 dark:text-white">Quantity</label>
                    <input type="number"
                           name="items[{{ $item->id }}][quantity]"
                           value="{{ data_get($item->additional, 'quantity', 0) }}"
                           min="1"
                           class="border rounded p-2 w-full mb-2">

                    <!-- Additional JSON (sadece attributes kısmı) -->
                    <label class="block font-medium text-gray-700 dark:text-white">Additional Attributes (JSON)</label>
                    <textarea name="items[{{ $item->id }}][additional]" rows="5"
                              class="border rounded p-2 w-full">{{ json_encode($item->additional['attributes'] ?? [], JSON_PRETTY_PRINT) }}</textarea>
                </div>
            </div>
        @endforeach

        <button type="submit" class="primary-button">Update All Items</button>
    </form>
</x-admin::layouts>
