<!-- resources/views/shopify/orders.blade.php -->

<!DOCTYPE html>
<html>
    <head>
        <title>Shopify Orders</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    </head>
    <style>
        /* Fondo gris intermedio para pedidos parcialmente pagados o pendientes */
        .bg-financial-warning {
            background-color: #d6d8db !important;
        }

        /* Fondo rojo claro para pedidos refundados, cancelados o voided */
        .bg-financial-danger {
            background-color: #f8d7da !important;
        }

        /* Fondo verde claro para pagos completados (opcional, si deseas destacar pagos correctos) */
        .bg-financial-success {
            background-color: #d4edda !important;
        }
    </style>
    <body class="p-4">
        <h1>Shopify Orders</h1>

        @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" class="mb-4">
            @csrf
            <div class="mb-3">
                <label class="form-label">Shop Domain</label>
                <input
                    type="text"
                    name="shop_domain"
                    class="form-control"
                    placeholder="midominio.myshopify.com"
                    value="{{ $shopDomain ?? '' }}"
                    required
                />
            </div>
            <div class="mb-3">
                <label class="form-label">API Token</label>
                <input type="text" name="api_token" class="form-control" value="{{ $apiToken ?? '' }}" required />
            </div>
            <button type="submit" class="btn btn-primary">Fetch Orders</button>
        </form>

        @isset($orders)
            <h2>Recent Orders ({{ count($orders) }})</h2>

            <button id="qmf-refresh-btn" class="btn btn-primary mb-3">Refresh data en QMF</button>
            <div id="alert-container"></div>

            <table class="table-bordered table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all"></th>
                        <th>ID</th>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        @php
                            $status = strtolower($order['financial_status']);
                        @endphp

                        <tr
                            @class([
                                'bg-financial-warning' => in_array($status, ['pending', 'partially_paid', 'unpaid']),
                                'bg-financial-danger' => in_array($status, ['refunded', 'voided', 'cancelled']),
                                'bg-financial-success' => $status === 'paid',
                            ])
                        ></tr>
                        <tr>
                            <td>
                                @if (strtolower($order['financial_status']) === 'paid')
                                    <input type="checkbox" name="order_ids[]" value="{{ $order['id'] }}">
                                @endif
                            </td>
                            <td>{{ $order['id'] }}</td>
                            <td>{{$order['order_number']}}</td>
                            <td>
                                {{ $order['customer']['first_name'] ?? '' }}
                                {{ $order['customer']['last_name'] ?? '' }}
                            </td>
                            <td>{{ $order['financial_status'] }}</td>
                            <td>${{ $order['total_price'] }}</td>
                            <td>{{ \Carbon\Carbon::parse($order['created_at'])->diffForHumans() }}</td>
                            <td>
                                <a href="{{ route('shopify.orderDetail', ['id' => $order['id'], 'shop_domain' => $shopDomain, 'api_token' => $apiToken]) }}" class="btn btn-sm btn-primary">
                                    Detalle
                                </a>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endisset
    </body>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="order_ids[]"]');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        $('#qmf-refresh-btn').on('click', function(e) {
            e.preventDefault();

            const selectedIds = [];
            $('input[name="order_ids[]"]:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                showAlert('Debes seleccionar al menos un pedido.', 'danger');
                return;
            }

            $.ajax({
                url: "{{ route('shopify.qmfRefresh') }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    order_ids: selectedIds
                },
                success: function(response) {
                    showAlert(response.message, 'success');
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Ocurrió un error.';
                    showAlert(message, 'danger');
                }
            });
        });

        function showAlert(message, type) {
            $('#alert-container').html(`
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    `);
        }
    </script>

</html>
