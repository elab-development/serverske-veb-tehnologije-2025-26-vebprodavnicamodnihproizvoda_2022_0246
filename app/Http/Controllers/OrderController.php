<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
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
}