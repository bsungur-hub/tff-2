<x-admin::layouts>
    <x-slot:title>
        Packing Slip #{{ $order->id }} Items
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap mb-4">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Packing Slip for Order #{{ $order->id }}
        </p>

        <div class="flex gap-2">
            <a href="{{ route('admin.sales.orders.view', $order->id) }}"
               class="inline-block px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded shadow hover:bg-gray-100 dark:text-white dark:border-gray-600 dark:hover:bg-gray-800 transition">
                <span class="icon-order-back mr-2" style="font-size: 1.2em;"></span> Back to Order
            </a>

            <a href="#"
                    onclick="printTable('packing-slip-table')"
               class="inline-block px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded shadow hover:bg-gray-100 dark:text-white dark:border-gray-600 dark:hover:bg-gray-800 transition">
                <span class="icon-printer mr-2" style="font-size: 1.2em;"></span> Print
            </a>
        </div>
    </div>


    <!-- Packing Slip Table -->
    <div id="packing-slip-table" class="overflow-x-auto max-w-6xl border rounded">
        <table class="min-w-full border-collapse">
            <thead>

            <div class="header max-w-6xl mb-4">
                <div class="order-info">
                    <div>Order Date: {{ $order->created_at->format('Y-m-d H:i') }}</div>
                    <div><strong>Requested Date: </strong> {{ $cart->customer_note ?? '-' }}</div>
                </div>
            </div>
            <tr class="bg-blue-50 dark:bg-blue-800">
                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 dark:text-white">#</th>
                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 dark:text-white">SKU</th>
                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 dark:text-white">Product</th>
                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 dark:text-white">Description</th>
                <th class="px-4 py-2 text-center text-sm font-semibold text-gray-700 dark:text-white">Qty</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($order->items as $index => $item)
                <tr class="{{ $index % 2 === 0 ? 'bg-blue-50 dark:bg-blue-800' : 'bg-white dark:bg-gray-700' }}">
                    <td class="px-4 py-2 text-sm text-gray-800 dark:text-white">{{ $index + 1 }}</td>
                    <td class="px-4 py-2 text-sm text-gray-800 dark:text-white">{{ $item->sku }}</td>
                    <td class="px-4 py-2 text-sm text-gray-800 dark:text-white">{{ $item->name }}</td>
                    <td class="px-4 py-2 text-sm text-gray-800 dark:text-white">
                        @php
                            $options = $item->additional['attributes'] ?? $item->additional['options'] ?? [];
                            $qty = $item->qty_ordered ?? $item->quantity ?? $item->qty ?? 1;
                            $qty = is_numeric($qty) ? (int)$qty : trim($qty);

                            // initialize values
                            $type = '';
                            $size = '';
                            $pleaseQuantity = '';
                            $scaleType = '';
                            $hasCustom = false;

                            foreach ($options as $option) {
                                $label = strtolower($option['label'] ?? $option['attribute_name'] ?? '');
                                $value = trim($option['value'] ?? $option['option_value'] ?? $option['option_label'] ?? '');

                                if ($label) $hasCustom = true;

                                if (str_contains($label, 'type')) $type = $value;
                                if (str_contains($label, 'size') || str_contains($label, 'avaliable') || str_contains($label, 'available')) $size = $value;
                                if (str_contains($label, 'please specify')) $pleaseQuantity = $value;
                                if (str_contains($label, 'scale type')) $scaleType = strtoupper($value);
                            }

                            $formatted = ''; // description text

                            // 1️⃣ ÖZEL DURUM — SADECE TRILECE
                            if (strtolower(trim($item->name)) === 'trilece') {
                                // 5 Adet 2 Kg Trilece
                                $formatted = "{$size}X{$qty} Trilece";
                            }

                            // 2️⃣ Miscellaneous/Other
                            elseif (trim($item->name) === 'Miscellaneous/Other') {

                                $title = '';
                                $detail = '';

                                foreach ($options as $option) {
                                    $label = strtolower($option['label'] ?? $option['attribute_name'] ?? '');
                                    $value = trim($option['value'] ?? $option['option_value'] ?? $option['option_label'] ?? '');

                                    if (str_contains($label, 'title') || str_contains($label, 'size') || str_contains($label, 'type')) {
                                        $title = $value;
                                    }

                                    if (str_contains($label, 'detail')) {
                                        $detail = $value;
                                    }
                                }

                                $formatted = trim($title) . '/' . trim($detail);
                            }

                        // 3️⃣ ÖZEL DURUM — Hem PleaseSpecify hem ScaleType varsa
                            elseif ($pleaseQuantity && $scaleType) {

                                // sayı içinde virgül varsa düzelt
                                $num = str_replace(',', '.', $pleaseQuantity);

                                // Format: 35.44 KG Sucuk Kangal
                                $formatted = "{$num} {$scaleType} " . trim($item->name);

                            }

                            // 4️⃣ Normal case - TYPE varsa
                            elseif ($type) {
                                $formatted = "{$qty} Adet " . trim($item->name) . " - " . trim($type);
                                if ($size) $formatted .= " (" . trim($size) . ")";
                            }

                            // 5️⃣ Eğer size kg/lb içeriyorsa
                            elseif ($size && preg_match('/\b\d+\s*(kg|lb)\b/i', $size)) {
                                $formatted = trim($size) . ' ' . trim($item->name);
                            }

                            // 6️⃣ Standart ürün
                            elseif (!$hasCustom) {
                                $formatted = "{$qty} Adet " . trim($item->name);
                            }

                            // 7️⃣ Size var type yok
                            elseif ($size && !$type) {
                                $formatted = "{$qty} Adet " . trim($item->name) . " (" . trim($size) . ")";
                            }

                            else {
                                $formatted = "{$qty} Adet " . trim($item->name);
                                if ($type) $formatted .= ' - ' . trim($type);
                                if ($size) $formatted .= ' (' . trim($size) . ')';
                            }

                        @endphp
                        {{ $formatted }}
                    </td>
                    <td class="px-4 py-2 text-center text-sm text-gray-800 dark:text-white">{{ $qty }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="note max-w-6xl mt-4 p-2 bg-white dark:bg-gray-700 rounded shadow">
        <strong>Not:</strong> {{ $order->customer_note ?? '-' }}
    </div>

    <script>
        function printTable(divId) {
            const content = document.getElementById(divId).innerHTML;

            const printWindow = window.open('', '_blank', 'width=900,height=600,scrollbars=yes');

            if (!printWindow) {
                alert('Popup engelleyici nedeniyle yazdırma penceresi açılamadı.');
                return;
            }

            printWindow.document.open();
            printWindow.document.write(`
        <html>
        <head>
            <title>Packing Slip</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 10px; }
                table { border-collapse: collapse; width: 100%; table-layout: fixed; }
                th, td { border: 1px solid #ccc; padding: 4px; text-align: left; font-size: 12px; }
                th { background-color: #e6f7ff; }  /* Çok açık mavi */
                tr:nth-child(even) { background-color: #f0fbff; }  /* Zebra strip açık mavi */

                /* Yazdırma için tüm tabloyu tek sayfaya sığdır */
                @media print {
                    body { padding: 0; margin: 0; }
                    table { transform: scale(0.85); transform-origin: top left; }
                    th, td { font-size: 10px; padding: 3px; }
                    @page { size: auto; margin: 5mm; }
                }
            </style>
        </head>
        <body>
            ${content}
        </body>
        </html>
    `);
            printWindow.document.close();

            printWindow.onload = function () {
                printWindow.focus();
                printWindow.print();
            };
        }
    </script>

</x-admin::layouts>
