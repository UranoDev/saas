<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Webhook en Shopify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h1 class="mb-4">Crear Webhook de Shopify</h1>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('shopify.createWebhook') }}">
                @csrf

                <div class="mb-3">
                    <label for="shop_domain" class="form-label">Dominio de la Tienda Shopify:</label>
                    <input type="text" name="shop_domain" id="shop_domain" class="form-control"
                           placeholder="mitienda.myshopify.com"
                           value="{{ request('shop_domain') }}" required>
                </div>

                <div class="mb-3">
                    <label for="api_token" class="form-label">Token de Acceso:</label>
                    <input type="text" name="api_token" id="api_token" class="form-control"
                           placeholder="Token privado de la App"
                           value="{{ request('api_token') }}" required>
                </div>

                <div class="mb-3">
                    <label for="callback_url" class="form-label">URL de Recepción del Webhook:</label>
                    <input type="url" name="callback_url" id="callback_url" class="form-control"
                           placeholder="https://tuservidor.com/webhook/orders/paid" required>
                </div>

                <button type="submit" class="btn btn-success">Crear Webhook</button>
            </form>
            <a href="{{ route('shopify.listWebhooks') }}?shop_domain={{ request('shop_domain') }}&api_token={{ request('api_token') }}" class="btn btn-secondary mt-3">Volver al Listado</a>
        </div>
    </div>

    <a href="{{ route('shopify.webhooksForm') }}" class="btn btn-secondary mt-3">Volver</a>
</div>

</body>
</html>

