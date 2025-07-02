<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Webhooks Shopify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h1 class="mb-4">Webhooks de {{ $shopDomain }}</h1>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Tema</th>
                    <th>URL</th>
                    <th>Acción</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($webhooks as $webhook)
                    <tr>
                        <td>{{ $webhook['id'] }}</td>
                        <td>{{ $webhook['topic'] }}</td>
                        <td>{{ $webhook['address'] }}</td>
                        <td>
                            <form method="POST" action="{{ route('shopify.deleteWebhook') }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="shop_domain" value="{{ $shopDomain }}">
                                <input type="hidden" name="api_token" value="{{ $apiToken }}">
                                <input type="hidden" name="webhook_id" value="{{ $webhook['id'] }}">
                                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No hay webhooks configurados.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>

            <form method="GET" action="{{ route('shopify.webhooks_create_form') }}" class="d-inline">
                <input type="hidden" name="shop_domain" value="{{ $shopDomain }}">
                <input type="hidden" name="api_token" value="{{ $apiToken }}">
                <button type="submit" class="btn btn-success">Agregar Webhook de Órdenes Pagadas</button>
            </form>

            <a href="{{ route('shopify.webhooksForm') }}?shop_domain={{ $shopDomain }}&api_token={{ $apiToken }}" class="btn btn-secondary mt-3">Volver</a>
        </div>
    </div>
</div>

</body>
</html>
