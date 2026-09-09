<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
            'total_price' => 'required|numeric|min:0',
            'status' => 'nullable|string|max:50',
            'delivery_address' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['user_id'] = $request->user()->id;

        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }

        $order = Order::create($data);
        $order->load(['user', 'items.product']);

        return response()->json([
            'success' => true,
            'message' => 'Porudžbina uspešno kreirana.',
            'data' => new OrderResource($order)
        ], 201);
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
}