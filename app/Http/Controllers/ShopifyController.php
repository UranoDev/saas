<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyController extends Controller
{
    public function index(Request $request)
    {
        $orders = [];
        $shopDomain = $request->shop_domain;
        $apiToken = $request->api_token;

        if ($shopDomain && $apiToken) {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $apiToken,
            ])->get("https://{$shopDomain}/admin/api/2023-07/orders.json", [
                'limit' => 50,
                'order' => 'created_at desc'
            ]);

            if ($response->ok()) {
                $orders = $response->json()['orders'] ?? [];
            }
        }

        return view('shopify.orders', compact('orders', 'shopDomain', 'apiToken'));
    }


    public function fetchOrders(Request $request)
    {
        $request->validate([
            'shop_domain' => 'required|string',
            'api_token'   => 'required|string',
        ]);

        $shopDomain = $request->shop_domain;
        $apiToken   = $request->api_token;

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $apiToken,
        ])->get("https://{$shopDomain}/admin/api/2023-07/orders.json?status=any", [
            'limit' => 100,
            'order' => 'created_at desc'
        ]);

        $orders = $response->json();
        log::debug('Result from API Orders: (' . count ($orders['orders']) . ') ' . $response->body());
        if ($response->failed()) {
            return back()->withErrors(['error' => 'Error connecting to Shopify API.', 'message' => $response->body()]);
        }

        $orders = $response->json()['orders'] ?? [];

        return view('shopify.orders', compact('orders', 'shopDomain', 'apiToken'));
    }

    public function orderDetail(Request $request, $id)
    {
        $request->validate([
            'shop_domain' => 'required|string',
            'api_token'   => 'required|string',
        ]);

        $shopDomain = $request->shop_domain;
        $apiToken = $request->api_token;

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $apiToken,
        ])->get("https://{$shopDomain}/admin/api/2023-07/orders/{$id}.json");

        if ($response->failed()) {
            abort(404, 'Error al obtener el detalle de la orden.');
        }

        $order = $response->json()['order'] ?? [];

        return view('shopify.order_detail', [
            'order'      => $order,
            'shopDomain' => $shopDomain,
            'apiToken'   => $apiToken,
        ]);
    }

    function get_order (string $id, Request $request){
        $request->validate([
            'shop_domain' => 'required|string',
            'api_token'   => 'required|string',
        ]);

        $shopDomain = $request->shop_domain;
        $apiToken = $request->api_token;

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $apiToken,
        ])->get("https://{$shopDomain}/admin/api/2023-07/orders/{$id}.json");

        if ($response->failed()) {
            abort(404, 'Error al obtener el detalle de la orden.');
        }
        return $response->json()['order'] ?? [];
    }

    /**
     * Genera el XML para QMF usando la misma estructura que process_order.php
     */
    private function generateQMFXML(array $orderData, array $shopConfig): string
    {
        Log::info("Generando XML para orden ID: " . $orderData['id']);

        // Obtener y actualizar el serial de orden
        //$prefix = $shopConfig['prefix_order'];
        $prefix = random_int(1000, 999999);
        //$this->updateOrderSerial($shopConfig['shop']);

        // Generar XML con DOM - Estructura exacta de process_order.php
        $doc = new DOMDocument('1.0', 'ISO-8859-1');
        $doc->formatOutput = true;

        // Envelope y namespaces
        $env = $doc->createElementNS("http://schemas.xmlsoap.org/soap/envelope/", "SOAP-ENV:Envelope");
        $env->setAttribute("SOAP-ENV:encodingStyle", "http://schemas.xmlsoap.org/soap/encoding/");
        $doc->appendChild($env);

        $body = $doc->createElement("SOAP-ENV:Body");
        $env->appendChild($body);

        // Método que espera el WSDL
        $r = random_int(1001, 9999);
        $request = $doc->createElement("ns$r:RequestXMLCFDIimpuestos");
        $request->setAttribute("xmlns:ns$r", "http://tempuri.org");
        $body->appendChild($request);

        // Parámetros de autenticación
        $request->appendChild($doc->createElement("USUARIO", $shopConfig['qmf4_user']));
        $request->appendChild($doc->createElement("STATUS", "ALMACENAR"));

        // === Datos del pedido ===
        $shopify_id = $orderData['id'];
        $orderId = $orderData['order_number'] ?? 'TEST-ORDER';
        $date = new DateTime(date('Y-n-j', strtotime($orderData['processed_at'] ?? 'now')));
        $purchaseDate = $date->format('Y-m-d');
        $orderStatus = $orderData['fulfillment_status'] ?? 'Completed';
        $salesChannel = 'Shopify';
        $totalAmount = $orderData['total_price'] ?? '0.00';
        $currency = $orderData['currency'] ?? 'MXN';
        $email = $orderData['email'] ?? 'no-email@dummy.com';
        $buyerName = ($orderData['customer']['first_name']??'' . $orderData['customer']['last_name']??'')?? 'VENTAS AL PUBLICO EN GENERAL';
        $usoCFDI = 'G03';
        $formaPago = '31';

        $order = $doc->createElement("Order");
        $order->appendChild($doc->createElement("RFC", "XAXX010101000"));

        $order->appendChild($doc->createElement("NombreReceptor", ""));
        $order->appendChild($doc->createElement("RegimenFiscalReceptor", ""));
        $order->appendChild($doc->createElement("codigoPostal", ""));

        $order->appendChild($doc->createElement("SellerOrderId", $prefix . $orderId));
        $order->appendChild($doc->createElement("PurchaseDate", $purchaseDate));
        $order->appendChild($doc->createElement("OrderStatus", ucfirst($orderStatus)));
        $order->appendChild($doc->createElement("SalesChannel", $salesChannel));

        $orderTotal = $doc->createElement("OrderTotal");
        $orderTotal->appendChild($doc->createElement("CurrencyCode", $currency));
        $orderTotal->appendChild($doc->createElement("Amount", $totalAmount));
        $order->appendChild($orderTotal);

        $order->appendChild($doc->createElement("NumberOfItemsShipped", count($orderData['line_items'] ?? [])));
        $order->appendChild($doc->createElement("BuyerEmail", $email));
        $order->appendChild($doc->createElement("BuyerName", $buyerName));
        $order->appendChild($doc->createElement("UsoCFDI", $usoCFDI));
        $order->appendChild($doc->createElement("FormaPago", $formaPago));

        $request->appendChild($order);

        // === Items del pedido ===
        $itemsResponse = $doc->createElement("ListOrderItemsResponse");
        $itemsResult = $doc->createElement("ListOrderItemsResult");
        $orderItems = $doc->createElement("OrderItems");
        $first_item = true;

        foreach ($orderData['line_items'] ?? [] as $item) {
            $product_name = mb_convert_encoding($item['title'], "UTF-8", 'ISO-8859-1');
            $orderItem = $doc->createElement("OrderItem");
            $orderItem->appendChild($doc->createElement("ASIN", $item['sku'] ?: 'SIN-SKU'));
            $orderItem->appendChild($doc->createElement("Title", $product_name));

            $tot_discount = 0.00;
            if(!empty($item['discount_allocations'])){
                foreach ($item['discount_allocations'] as $discount){
                    $tot_discount = $tot_discount +  $discount['amount'];
                }
            }
            $orderItem->appendChild($doc->createElement("QuantityShipped", $item['quantity']));
            $itemPrice = $doc->createElement("ItemPrice");
            $itemPrice->appendChild($doc->createElement("Amount", number_format($item['price'] * $item['quantity'] - $tot_discount, 2)));
            $orderItem->appendChild($itemPrice);

            $shippingPrice = $doc->createElement("ShippingPrice");
            if ($first_item) {
                $t = 0.0;
                foreach ($orderData['shipping_lines'] ?? [] as $shipping_line) {
                    $t = $t + $shipping_line['price'];
                }
                $shippingPrice->appendChild($doc->createElement("Amount", number_format($t, 2)));
            }else{
                $shippingPrice->appendChild($doc->createElement("Amount", "0.00"));
            }
            $orderItem->appendChild($shippingPrice);

            $discount = $doc->createElement("PromotionDiscount");
            $discount->appendChild($doc->createElement("Amount", "0.00"));
            $orderItem->appendChild($discount);

            $impuestos = $doc->createElement("Impuestos");
            $traslados = $impuestos->appendChild($doc->createElement("Traslados", ""));
            $traslado = $traslados->appendChild($doc->createElement("Traslado", ""));
            $traslado->appendChild($doc->createElement("Impuesto", "002"));
            $traslado->appendChild($doc->createElement("TipoFactor", "Tasa"));
            $traslado->appendChild($doc->createElement("TasaOCuota", "0.16"));
            $orderItem->appendChild($impuestos);

            $orderItems->appendChild($orderItem);
            $first_item = false;
        }

        $itemsResult->appendChild($orderItems);
        $itemsResponse->appendChild($itemsResult);
        $request->appendChild($itemsResponse);

        // Convertir DOM a string
        $xmlString = $doc->saveXML();

        // Guardar copia local del XML
        $folder = storage_path('logs');
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }
        $filename = $folder . '/qmf_order_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $orderId) . '_' . time() . '.xml';
        file_put_contents($filename, $xmlString);
        Log::info("XML guardado en: " . $filename);

        return $xmlString;
    }


    /**
     * Envía XML por POST usando Laravel HTTP Client
     */
    function postXML(string $url, string $xmlString, array $headersExtra = []): array
    {
        try {
            // Headers base
            $headers = [
                'Content-Type' => 'text/xml',
                'Content-Length' => strlen($xmlString)
            ];

            // Si el usuario pasa headers adicionales, los agregamos
            if (!empty($headersExtra)) {
                $headers = array_merge($headers, $headersExtra);
            }

            // Realizar POST con Laravel HTTP Client
            $response = Http::withHeaders($headers)
                ->withOptions([
                    'verify' => false, // Equivalente a CURLOPT_SSL_VERIFYPEER false
                    'timeout' => 30,   // Timeout de 30 segundos
                ])
                ->withBody($xmlString, 'text/xml')
                ->post($url);

            // Log de la respuesta para debugging
            Log::debug('QMF Response', [
                'http_code' => $response->status(),
                'response_body' => $response->body(),
                'headers' => $response->headers()
            ]);

            return [
                'http_code' => $response->status(),
                'response' => $response->body(),
                'error' => null,
            ];

        } catch (\Exception $e) {
            Log::error('Error en postXML', [
                'error' => $e->getMessage(),
                'url' => $url
            ]);

            return [
                'http_code' => 0,
                'response' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Obtiene una orden específica de Shopify
     */
    private function getShopifyOrder(string $orderId, string $shopDomain, string $apiToken): ?array
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $apiToken,
        ])->get("https://{$shopDomain}/admin/api/2023-07/orders/{$orderId}.json");

        if ($response->failed()) {
            Log::error("Error al obtener orden {$orderId} de Shopify", [
                'http_code' => $response->status(),
                'response' => $response->body()
            ]);
            return null;
        }

        return $response->json()['order'] ?? null;
    }

    /**
     * Procesa una orden individual: obtiene datos, genera XML y envía a QMF
     */
    private function processOrderToQMF(string $orderId, string $shopDomain, string $apiToken): array
    {
        Log::info("Procesando Order ID: {$orderId} para QMF");

        try {
            // 1. Obtener configuración de la tienda
/*            $shopConfig = $this->getShopConfig($shopDomain);
            if (!$shopConfig) {
                throw new \Exception("No se encontró configuración para la tienda: {$shopDomain}");
            }*/

            // 2. Obtener orden de Shopify
            $order = $this->getShopifyOrder($orderId, $shopDomain, $apiToken);
            if (!$order) {
                throw new \Exception("No se pudo obtener la orden {$orderId} desde Shopify");
            }

            Log::info("Orden obtenida desde Shopify", [
                'order_id' => $orderId,
                'order_number' => $order['order_number'] ?? 'N/A',
                'total_price' => $order['total_price'] ?? 'N/A'
            ]);

            // 3. Generar XML
            $xmlString = $this->generateQMFXML($order, $shopConfig);

            // 4. Enviar a QMF
            $response = $this->postXML('https://quieromifactura.mx/QA2/web_services/servidorMarket.php', $xmlString);

            Log::info("Respuesta de QMF para orden {$orderId}", $response);

            if ($response['error']) {
                throw new \Exception("Error de conexión: " . $response['error']);
            }

            if ($response['http_code'] !== 200) {
                throw new \Exception("Error HTTP {$response['http_code']}: " . $response['response']);
            }

            return [
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $order['order_number'] ?? 'N/A',
                'message' => 'Enviado exitosamente a QMF',
                'qmf_response' => $response['response']
            ];

        } catch (\Exception $e) {
            Log::error("Error procesando orden {$orderId}: " . $e->getMessage());
            return [
                'success' => false,
                'order_id' => $orderId,
                'message' => $e->getMessage()
            ];
        }
    }

    public function qmfRefresh(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array|min:1',
            'shop_domain' => 'required|string',
            'api_token' => 'required|string',
        ]);

        $orderIds = $request->input('order_ids', []);
        $shopDomain = $request->input('shop_domain');
        $apiToken = $request->input('api_token');

        if (empty($orderIds)) {
            return response()->json(['message' => 'Debe seleccionar al menos un pedido.'], 422);
        }

        Log::info("Iniciando proceso QMF para múltiples órdenes", [
            'shop_domain' => $shopDomain,
            'order_count' => count($orderIds),
            'order_ids' => $orderIds
        ]);

        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($orderIds as $orderId) {
            $result = $this->processOrderToQMF($orderId, $shopDomain, $apiToken);
            $results[] = $result;

            if ($result['success']) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        Log::info("Proceso QMF completado", [
            'total_orders' => count($orderIds),
            'success_count' => $successCount,
            'error_count' => $errorCount
        ]);

        return response()->json([
            'message' => "Proceso completado: {$successCount} órdenes enviadas exitosamente, {$errorCount} errores.",
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'results' => $results
        ]);
    }

    public function webhooksForm()
    {
        return view('shopify.webhooks_form');
    }

    public function listWebhooks(Request $request)
    {
        $request->validate([
            'shop_domain' => 'required|string',
            'api_token'   => 'required|string',
        ]);

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $request->api_token,
        ])->get("https://{$request->shop_domain}/admin/api/2023-07/webhooks.json");

        if ($response->failed()) {
            return back()->with('error', 'Error al obtener los Webhooks.');
        }

        return view('shopify.webhooks_list', [
            'webhooks'   => $response->json()['webhooks'] ?? [],
            'shopDomain' => $request->shop_domain,
            'apiToken'   => $request->api_token,
        ]);
    }

    public function deleteWebhook(Request $request)
    {
        $request->validate([
            'shop_domain' => 'required|string',
            'api_token'   => 'required|string',
            'webhook_id'  => 'required|numeric',
        ]);

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $request->api_token,
        ])->delete("https://{$request->shop_domain}/admin/api/2023-07/webhooks/{$request->webhook_id}.json");

        return back()->with('success', 'Webhook eliminado.');
    }

    public function createWebhook(Request $request)
    {
        $request->validate([
            'shop_domain'   => 'required|string',
            'api_token'     => 'required|string',
            'callback_url'  => 'required|url',
        ]);

        $graphqlQuery = <<<'GRAPHQL'
    mutation webhookSubscriptionCreate($topic: WebhookSubscriptionTopic!, $callbackUrl: URL!) {
        webhookSubscriptionCreate(topic: $topic, webhookSubscription: {callbackUrl: $callbackUrl, format: JSON}) {
            webhookSubscription {
                id
                topic
                callbackUrl
            }
            userErrors {
                field
                message
            }
        }
    }
    GRAPHQL;

        $variables = [
            'topic' => 'ORDERS_PAID',
            'callbackUrl' => $request->callback_url,
        ];

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $request->api_token,
            'Content-Type' => 'application/json',
        ])->post("https://{$request->shop_domain}/admin/api/2023-07/graphql.json", [
            'query' => $graphqlQuery,
            'variables' => $variables,
        ]);

        if ($response->failed()) {
            return back()->with('error', 'Error al conectar con Shopify.');
        }

        $data = $response->json();

        if (!empty($data['data']['webhookSubscriptionCreate']['userErrors'])) {
            $errorMessages = collect($data['data']['webhookSubscriptionCreate']['userErrors'])->pluck('message')->implode(', ');
            return back()->with('error', 'Error al crear el Webhook: ' . $errorMessages);
        }

        return back()->with('success', 'Webhook creado correctamente.');
    }

    public function webhooksCreateForm(Request $request)
    {
        return view('shopify.webhooks_create_form', [
            'shop_domain' => $request->shop_domain,
            'api_token'   => $request->api_token,
        ]);
    }

}
