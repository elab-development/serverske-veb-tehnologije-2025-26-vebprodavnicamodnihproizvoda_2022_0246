<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class OrderController extends Controller
{

    #[OA\Get(
    path: "/orders",
    summary: "Pregled porudžbina",
    description: "Registrovani korisnik dobija svoje porudžbine, dok administrator dobija sve porudžbine u sistemu.",
    tags: ["Porudžbine"],
    security: [["sanctum" => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: "Porudžbine uspešno učitane"
        ),
        new OA\Response(
            response: 401,
            description: "Korisnik nije autentifikovan"
        )
    ]
    )]

    public function index(Request $request)
    {
        if ($request->user()->role === 'admin') {
            $orders = Order::with(['user', 'items.product'])->get();
        } else {
            $orders = Order::with(['user', 'items.product'])
                ->where('user_id', $request->user()->id)
                ->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'Porudžbine uspešno učitane.',
            'data' => OrderResource::collection($orders)
        ], 200);
    }

#[OA\Get(
    path: "/orders/{id}",
    summary: "Pregled jedne porudžbine",
    description: "Vraća određenu porudžbinu. Korisnik može pregledati samo svoju porudžbinu, dok administrator može pregledati bilo koju.",
    tags: ["Porudžbine"],
    security: [["sanctum" => []]],
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            description: "ID porudžbine",
            schema: new OA\Schema(type: "integer")
        )
    ],
    responses: [
        new OA\Response(response: 200, description: "Porudžbina uspešno učitana"),
        new OA\Response(response: 403, description: "Korisnik nema dozvolu za pristup porudžbini"),
        new OA\Response(response: 404, description: "Porudžbina nije pronađena")
    ]
)]

    public function show(Request $request, $id)
    {
        $order = Order::with(['user', 'items.product'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Porudžbina nije pronađena.'
            ], 404);
        }

        if (
            $request->user()->role !== 'admin' &&
            $order->user_id !== $request->user()->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Nemate dozvolu za pristup ovoj porudžbini.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order)
        ], 200);
    }

#[OA\Post(
    path: "/orders",
    summary: "Kreiranje porudžbine",
    description: "Kreira novu porudžbinu sa jednom ili više stavki, proverava stanje proizvoda, umanjuje zalihe i izračunava ukupnu cenu u okviru transakcije.",
    tags: ["Porudžbine"],
    security: [["sanctum" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["delivery_address", "items"],
            properties: [
                new OA\Property(
                    property: "delivery_address",
                    type: "string",
                    example: "Bulevar oslobođenja 10, Beograd"
                ),
                new OA\Property(
                    property: "items",
                    type: "array",
                    items: new OA\Items(
                        required: ["product_id", "quantity"],
                        properties: [
                            new OA\Property(
                                property: "product_id",
                                type: "integer",
                                example: 1
                            ),
                            new OA\Property(
                                property: "quantity",
                                type: "integer",
                                example: 2
                            )
                        ]
                    )
                )
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: "Porudžbina uspešno kreirana"
        ),
        new OA\Response(
            response: 400,
            description: "Greška tokom kreiranja porudžbine ili nedovoljno proizvoda na stanju"
        ),
        new OA\Response(
            response: 401,
            description: "Korisnik nije autentifikovan"
        ),
        new OA\Response(
            response: 422,
            description: "Greška validacije"
        )
    ]
)]

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'delivery_address' => 'required|string|max:255',

            'items' => 'required|array|min:1',

            'items.*.product_id' => 'required|integer|exists:products,id',

            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Greška pri validaciji.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {

            $order = DB::transaction(function () use ($request) {

                $totalPrice = 0;

                $order = Order::create([
                    'user_id' => $request->user()->id,
                    'total_price' => 0,
                    'status' => 'pending',
                    'delivery_address' => $request->delivery_address,
                ]);

                foreach ($request->items as $item) {

                    $product = Product::findOrFail($item['product_id']);

                    if ($product->stock < $item['quantity']) {
                        throw new \Exception(
                            'Nema dovoljno proizvoda na stanju: ' . $product->name
                        );
                    }

                    $itemTotal = $product->price * $item['quantity'];

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'price' => $product->price,
                    ]);

                    $product->stock -= $item['quantity'];
                    $product->save();

                    $totalPrice += $itemTotal;
                }

                $order->total_price = $totalPrice;
                $order->save();

                return $order;
            });

            $order->load(['user', 'items.product']);

            return response()->json([
                'success' => true,
                'message' => 'Porudžbina uspešno kreirana.',
                'data' => new OrderResource($order)
            ], 201);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

#[OA\Put(
    path: "/orders/{id}",
    summary: "Izmena porudžbine",
    description: "Menja podatke postojeće porudžbine. Korisnik može menjati samo svoju porudžbinu, dok administrator može menjati bilo koju.",
    tags: ["Porudžbine"],
    security: [["sanctum" => []]],
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            description: "ID porudžbine",
            schema: new OA\Schema(type: "integer")
        )
    ],
    requestBody: new OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: "total_price",
                    type: "number",
                    format: "float",
                    example: 120.50
                ),
                new OA\Property(
                    property: "status",
                    type: "string",
                    example: "completed"
                ),
                new OA\Property(
                    property: "delivery_address",
                    type: "string",
                    example: "Nova adresa 15, Beograd"
                )
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Porudžbina uspešno izmenjena"),
        new OA\Response(response: 403, description: "Korisnik nema dozvolu za izmenu"),
        new OA\Response(response: 404, description: "Porudžbina nije pronađena"),
        new OA\Response(response: 422, description: "Greška validacije")
    ]
)]

    public function update(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Porudžbina nije pronađena.'
            ], 404);
        }

        if (
            $request->user()->role !== 'admin' &&
            $order->user_id !== $request->user()->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Nemate dozvolu za izmenu ove porudžbine.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'total_price' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|required|string|max:50',
            'delivery_address' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $order->update($validator->validated());
        $order->load(['user', 'items.product']);

        return response()->json([
            'success' => true,
            'message' => 'Porudžbina uspešno izmenjena.',
            'data' => new OrderResource($order)
        ], 200);
    }

#[OA\Delete(
    path: "/orders/{id}",
    summary: "Brisanje porudžbine",
    description: "Briše porudžbinu ako je korisnik njen vlasnik ili administrator.",
    tags: ["Porudžbine"],
    security: [["sanctum" => []]],
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            description: "ID porudžbine",
            schema: new OA\Schema(type: "integer")
        )
    ],
    responses: [
        new OA\Response(response: 200, description: "Porudžbina uspešno obrisana"),
        new OA\Response(response: 403, description: "Korisnik nema dozvolu za brisanje"),
        new OA\Response(response: 404, description: "Porudžbina nije pronađena")
    ]
)]

    public function destroy(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Porudžbina nije pronađena.'
            ], 404);
        }

        if (
            $request->user()->role !== 'admin' &&
            $order->user_id !== $request->user()->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Nemate dozvolu za brisanje ove porudžbine.'
            ], 403);
        }

        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Porudžbina uspešno obrisana.'
        ], 200);
    }

#[OA\Get(
    path: "/users/{id}/orders",
    summary: "Pregled porudžbina korisnika",
    description: "Vraća sve porudžbine određenog korisnika. Korisnik može pregledati samo svoje porudžbine, dok administrator može pregledati porudžbine bilo kog korisnika.",
    tags: ["Porudžbine"],
    security: [["sanctum" => []]],
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            description: "ID korisnika",
            schema: new OA\Schema(type: "integer")
        )
    ],
    responses: [
        new OA\Response(response: 200, description: "Porudžbine korisnika uspešno učitane"),
        new OA\Response(response: 403, description: "Korisnik nema dozvolu za pregled")
    ]
)]

    public function userOrders(Request $request, $id)
    {
        if ($request->user()->role !== 'admin' && $request->user()->id != $id) {
            return response()->json([
                'success' => false,
                'message' => 'Nemate dozvolu za pregled porudžbina ovog korisnika.'
            ], 403);
        }

        $orders = Order::with(['items.product'])
            ->where('user_id', $id)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Porudžbine korisnika uspešno učitane.',
            'data' => OrderResource::collection($orders)
        ], 200);
    }

#[OA\Get(
    path: "/orders/{id}/items",
    summary: "Pregled stavki porudžbine",
    description: "Vraća stavke izabrane porudžbine zajedno sa podacima o proizvodima.",
    tags: ["Porudžbine"],
    security: [["sanctum" => []]],
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            description: "ID porudžbine",
            schema: new OA\Schema(type: "integer")
        )
    ],
    responses: [
        new OA\Response(response: 200, description: "Stavke porudžbine uspešno učitane"),
        new OA\Response(response: 403, description: "Korisnik nema dozvolu za pregled stavki"),
        new OA\Response(response: 404, description: "Porudžbina nije pronađena")
    ]
)]

    public function orderItems(Request $request, $id)
    {
        $order = Order::with(['items.product'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Porudžbina nije pronađena.'
            ], 404);
        }

        if (
            $request->user()->role !== 'admin' &&
            $order->user_id !== $request->user()->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Nemate dozvolu za pregled stavki ove porudžbine.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Stavke porudžbine uspešno učitane.',
            'data' => $order->items
        ], 200);
    }

#[OA\Get(
    path: "/reports/orders",
    summary: "Izveštaj o porudžbinama",
    description: "Administrator dobija detaljan izveštaj o porudžbinama formiran povezivanjem tabela orders, users, order_items, products i categories.",
    tags: ["Porudžbine"],
    security: [["sanctum" => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: "Izveštaj o porudžbinama uspešno generisan"
        ),
        new OA\Response(
            response: 403,
            description: "Pristup je dozvoljen samo administratoru"
        )
    ]
)]

    public function ordersReport(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Pristup je dozvoljen samo administratoru.'
            ], 403);
        }

        $report = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'orders.id as order_id',
                'orders.status',
                'orders.total_price',
                'orders.delivery_address',
                'users.id as user_id',
                'users.name as user_name',
                'users.email as user_email',
                'order_items.quantity',
                'order_items.price as item_price',
                'products.id as product_id',
                'products.name as product_name',
                'products.brand',
                'categories.id as category_id',
                'categories.name as category_name'
            )
            ->orderBy('orders.id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Izveštaj o porudžbinama uspešno generisan.',
            'data' => $report
        ], 200);
    }
}