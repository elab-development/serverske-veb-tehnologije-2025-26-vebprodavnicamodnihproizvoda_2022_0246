<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    
    public function index()
    {
        $products = Product::with('category')->get();

        return response()->json([
            'success' => true,
            'message' => 'Proizvodi uspešno učitani.',
            'data' => $products
        ], 200);
    }

    
    public function show($id)
    {
        $product = Product::with('category')->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Proizvod nije pronađen.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product
        ], 200);
    }

   
    public function search(Request $request)
    {
        $term = $request->query('term'); 

        $products = Product::with('category')
            ->where('name', 'LIKE', "%{$term}%")
            ->orWhere('brand', 'LIKE', "%{$term}%")
            ->get();

        return response()->json([
            'success' => true,
            'search_term' => $term,
            'count' => $products->count(),
            'data' => $products
        ], 200);
    }

    
    public function filterByCategory($categoryId)
    {
        $products = Product::with('category')
            ->where('category_id', $categoryId)
            ->get();

        return response()->json([
            'success' => true,
            'category_id' => $categoryId,
            'count' => $products->count(),
            'data' => $products
        ], 200);
    }

    
    public function stockStats()
    {
        $totalItems = Product::sum('stock');
        $averagePrice = Product::avg('price');
        $outOfStock = Product::where('stock', 0)->count();

        return response()->json([
            'success' => true,
            'stats' => [
                'total_products_in_stock' => $totalItems,
                'average_product_price' => round($averagePrice, 2),
                'out_of_stock_count' => $outOfStock
            ]
        ], 200);
    }

    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Proizvod uspešno kreiran.',
            'data' => $product
        ], 201);
    }

    
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Proizvod nije pronađen.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'sometimes|required|exists:categories,id',
            'name' => 'sometimes|required|string|max:255',
            'brand' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'image' => 'sometimes|nullable|string',
            ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $product->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Proizvod uspešno izmenjen.',
            'data' => $product
        ], 200);
    }

    
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Proizvod nije pronađen.'
            ], 404);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Proizvod uspešno obrisan iz baze.'
        ], 200);
    }
}