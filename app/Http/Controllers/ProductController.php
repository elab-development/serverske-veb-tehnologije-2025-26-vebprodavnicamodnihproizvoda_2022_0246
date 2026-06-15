<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

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
}