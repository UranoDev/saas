<!DOCTYPE html>
<html>
    <head>
        <title>Detalle de Orden {{ $order['id'] }}</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    </head>
    <body class="bg-light p-4">
        <div class="container">
            <h1 class="mb-4">Detalle de Orden #{{ $order['id'] }}</h1>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Información General</h5>

                    <p>
                        <strong>Cliente:</strong>
                        {{ $order['customer']['first_name'] ?? '' }} {{ $order['customer']['last_name'] ?? '' }}
                    </p>
                    <p>
                        <strong>Email:</strong>
                        {{ $order['email'] ?? 'N/A' }}
                    </p>
                    <p>
                        <strong>Total:</strong>
                        ${{ $order['total_price'] }}
                    </p>
                    <p>
                        <strong>Estado de Pago:</strong>
                        {{ ucfirst($order['financial_status']) }}
                    </p>
                    <p>
                        <strong>Fecha de Creación:</strong>
                        {{ \Carbon\Carbon::parse($order['created_at'])->diffForHumans() }}
                    </p>

                    <h5 class="card-title">Resumen Económico de la Orden</h5>
                    <p>
                        <strong>Subtotal (antes de impuestos y descuentos):</strong>
                        ${{ $order['subtotal_price'] }}
                    </p>
                    <p>
                        <strong>Descuentos Totales:</strong>
                        ${{ $order['total_discounts'] }}
                    </p>
                    <p>
                        <strong>Impuestos Totales:</strong>
                        ${{ $order['total_tax'] }}
                    </p>
                    <p>
                        <strong>Envío:</strong>
                        ${{ $order['total_shipping_price_set']['shop_money']['amount'] ?? '0.00' }}
                    </p>
                    <p>
                        <strong>Total Pagado:</strong>
                        ${{ $order['total_price'] }}
                    </p>
                    <p>
                        <strong>Suma total de precios de todos los items antes de descuentos o impuestos:</strong>
                        ${{ $order['total_line_items_price'] }}
                    </p>
                    <p>
                        <strong>current_total_tax_set.shop_money.amount:</strong>
                        ${{ $order['current_total_tax_set']['shop_money']['amount'] }}
                    </p>
                    <p>
                        <strong>current_total_tax_set.presentment_money.amount:</strong>
                        ${{ $order['current_total_tax_set']['presentment_money']['amount'] }}
                    </p>

                    @if (! empty($order['discount_codes']))
                        <h6 class="mt-3">Códigos de Descuento Aplicados:</h6>
                        <ul>
                            @foreach ($order['discount_codes'] as $discount)
                                <li>
                                    Código:
                                    <strong>{{ $discount['code'] }}</strong>
                                    - Monto: ${{ $discount['amount'] }} - Tipo: {{ ucfirst($discount['type']) }}
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if (! empty($order['gift_cards']))
                        <h6 class="mt-3">Tarjetas de Regalo Aplicadas:</h6>
                        <ul>
                            @foreach ($order['gift_cards'] as $gift)
                                <li>
                                    Últimos dígitos: ****{{ $gift['last_characters'] }} - Monto Usado:
                                    ${{ $gift['amount_used'] }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Productos</h5>

                    <table class="table-striped table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>SKU</th>
                                <th>Variante</th>
                                <th>Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>Descuento</th>
                                <th>Impuestos</th>
                                <th>Total Estimado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order['line_items'] as $item)
                                <tr>
                                    <td>{{ $item['title'] }}</td>
                                    <td>{{ $item['sku'] }}</td>
                                    <td>{{ $item['variant_title'] ?? 'N/A' }}</td>
                                    <td>{{ $item['quantity'] }}</td>
                                    <td>${{ $item['price'] }}</td>
                                    <td>
                                        ${{ $item['total_discount'] ?? '0.00' }}
                                        <br />
                                        @php
                                            $tot_discount = 0;
                                            if (! empty($item['discount_allocations'])) {
                                                foreach ($item['discount_allocations'] as $discount) {
                                                    $tot_discount = $tot_discount + $discount['amount'];
                                                }
                                            }
                                        @endphp

                                        @if (! empty($item['discount_allocations']))
                                            @foreach ($item['discount_allocations'] as $discount)
                                                {{ $discount['amount'] }} - pri
                                                {{ $discount['discount_application_index'] }}
                                                <br />
                                            @endforeach
                                        @endif
                                    </td>
                                    <td>
                                        @if (! empty($item['tax_lines']))
                                            @foreach ($item['tax_lines'] as $tax)
                                                {{ $tax['title'] }} ({{ $tax['rate'] * 100 }}%): ${{ $tax['price'] }}
                                                <br />
                                            @endforeach
                                        @else
                                            $0.00
                                        @endif
                                    </td>
                                    <td>
                                        {{ number_format($item['price'] * $item['quantity'] - $tot_discount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <a
                href="{{ route('shopify.orders') }}?shop_domain={{ $shopDomain }}&api_token={{ $apiToken }}"
                class="btn btn-secondary"
            >
                &larr; Regresar a la Lista de Pedidos
            </a>
        </div>
    </body>
</html>
