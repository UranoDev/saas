<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar Webhooks Shopify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h1 class="mb-4">Administrar Webhooks de Shopify</h1>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('shopify.listWebhooks') }}">
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

                <button type="submit" class="btn btn-primary">Consultar Webhooks</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
