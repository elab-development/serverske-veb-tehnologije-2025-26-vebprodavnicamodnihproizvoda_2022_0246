<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    #[OA\Get(
    path: "/products",
    summary: "Pregled proizvoda",
    description: "Vraća paginiranu listu proizvoda sa kategorijama. Podržava filtriranje po kategoriji, brendu i rasponu cena, kao i sortiranje.",
    tags: ["Proizvodi"],
    parameters: [
        new OA\Parameter(
            name: "category_id",
            in: "query",
            required: false,
            schema: new OA\Schema(type: "integer"),
            description: "ID kategorije"
        ),
        new OA\Parameter(
            name: "brand",
            in: "query",
            required: false,
            schema: new OA\Schema(type: "string"),
            description: "Naziv brenda"
        ),
        new OA\Parameter(
            name: "min_price",
            in: "query",
            required: false,
            schema: new OA\Schema(type: "number", format: "float"),
            description: "Minimalna cena"
        ),
        new OA\Parameter(
            name: "max_price",
            in: "query",
            required: false,
            schema: new OA\Schema(type: "number", format: "float"),
            description: "Maksimalna cena"
        ),
        new OA\Parameter(
            name: "sort_by",
            in: "query",
            required: false,
            schema: new OA\Schema(
                type: "string",
                enum: ["name", "price", "stock", "brand"]
            ),
            description: "Polje po kojem se sortira"
        ),
        new OA\Parameter(
            name: "sort_order",
            in: "query",
            required: false,
            schema: new OA\Schema(
                type: "string",
                enum: ["asc", "desc"]
            ),
            description: "Smer sortiranja"
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "Proizvodi uspešno učitani"
        )
    ]
)]

    public function index(Request $request)
    {

        $query = Product::with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand')) {
            $query->where('brand', 'LIKE', '%' . $request->brand . '%');
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        $allowedSortFields = ['name', 'price', 'stock', 'brand'];

$sortBy = $request->query('sort_by', 'id');
$sortOrder = strtolower($request->query('sort_order', 'asc'));

if (!in_array($sortBy, $allowedSortFields)) {
    $sortBy = 'id';
}

if (!in_array($sortOrder, ['asc', 'desc'])) {
    $sortOrder = 'asc';
}

$query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Proizvodi uspešno učitani.',
            'data' => $products
        ], 200);
    }

#[OA\Get(
    path: "/products/{id}",
    summary: "Pregled jednog proizvoda",
    description: "Vraća podatke o proizvodu sa pripadajućom kategorijom.",
    tags: ["Proizvodi"],
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer"),
            description: "ID proizvoda"
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "Proizvod uspešno pronađen"
        ),
        new OA\Response(
            response: 404,
            description: "Proizvod nije pronađen"
        )
    ]
)]

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

#[OA\Get(
    path: "/products/search",
    summary: "Pretraga proizvoda",
    description: "Pretražuje proizvode prema nazivu ili brendu.",
    tags: ["Proizvodi"],
    parameters: [
        new OA\Parameter(
            name: "term",
            in: "query",
            required: false,
            schema: new OA\Schema(type: "string"),
            description: "Termin za pretragu"
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "Rezultati pretrage"
        )
    ]
)]

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

#[OA\Get(
    path: "/products/category/{categoryId}",
    summary: "Filtriranje proizvoda po kategoriji",
    description: "Vraća proizvode koji pripadaju izabranoj kategoriji.",
    tags: ["Proizvodi"],
    parameters: [
        new OA\Parameter(
            name: "categoryId",
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer"),
            description: "ID kategorije"
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "Proizvodi uspešno filtrirani po kategoriji"
        )
    ]
)]

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

#[OA\Get(
    path: "/products/stats/stock",
    summary: "Statistika zaliha proizvoda",
    description: "Vraća ukupan broj proizvoda na stanju, prosečnu cenu i broj proizvoda bez zaliha.",
    tags: ["Proizvodi"],
    responses: [
        new OA\Response(
            response: 200,
            description: "Statistika uspešno učitana"
        )
    ]
)]

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

#[OA\Post(
    path: "/products",
    summary: "Dodavanje proizvoda",
    description: "Administrator kreira novi proizvod. Moguće je dodati i sliku proizvoda.",
    tags: ["Proizvodi"],
    security: [["sanctum" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                required: [
                    "category_id",
                    "name",
                    "brand",
                    "description",
                    "price",
                    "stock"
                ],
                properties: [
                    new OA\Property(property: "category_id", type: "integer", example: 1),
                    new OA\Property(property: "name", type: "string", example: "Nike majica"),
                    new OA\Property(property: "brand", type: "string", example: "Nike"),
                    new OA\Property(property: "description", type: "string", example: "Sportska majica"),
                    new OA\Property(property: "price", type: "number", format: "float", example: 49.99),
                    new OA\Property(property: "stock", type: "integer", example: 20),
                    new OA\Property(
                        property: "image",
                        type: "string",
                        format: "binary"
                    )
                ]
            )
        )
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: "Proizvod uspešno kreiran"
        ),
        new OA\Response(
            response: 401,
            description: "Korisnik nije autentifikovan"
        ),
        new OA\Response(
            response: 403,
            description: "Pristup je dozvoljen samo administratoru"
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
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Proizvod uspešno kreiran.',
            'data' => $product
        ], 201);
    }

#[OA\Put(
    path: "/products/{id}",
    summary: "Izmena proizvoda",
    description: "Administrator menja podatke postojećeg proizvoda.",
    tags: ["Proizvodi"],
    security: [["sanctum" => []]],
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer"),
            description: "ID proizvoda"
        )
    ],
    requestBody: new OA\RequestBody(
        required: false,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: "category_id", type: "integer"),
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "brand", type: "string"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "price", type: "number", format: "float"),
                    new OA\Property(property: "stock", type: "integer"),
                    new OA\Property(
                        property: "image",
                        type: "string",
                        format: "binary"
                    )
                ]
            )
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Proizvod uspešno izmenjen"
        ),
        new OA\Response(
            response: 403,
            description: "Pristup je dozvoljen samo administratoru"
        ),
        new OA\Response(
            response: 404,
            description: "Proizvod nije pronađen"
        ),
        new OA\Response(
            response: 422,
            description: "Greška validacije"
        )
    ]
)]

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
            'image' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Proizvod uspešno izmenjen.',
            'data' => $product
        ], 200);
    }

#[OA\Delete(
    path: "/products/{id}",
    summary: "Brisanje proizvoda",
    description: "Administrator briše proizvod iz baze. Ako proizvod ima sliku, briše se i fajl slike.",
    tags: ["Proizvodi"],
    security: [["sanctum" => []]],
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer"),
            description: "ID proizvoda"
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "Proizvod uspešno obrisan"
        ),
        new OA\Response(
            response: 403,
            description: "Pristup je dozvoljen samo administratoru"
        ),
        new OA\Response(
            response: 404,
            description: "Proizvod nije pronađen"
        )
    ]
)]

    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Proizvod nije pronađen.'
            ], 404);
        }

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Proizvod uspešno obrisan iz baze.'
        ], 200);
    }
}