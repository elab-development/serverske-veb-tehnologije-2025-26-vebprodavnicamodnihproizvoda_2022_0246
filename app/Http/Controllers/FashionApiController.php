<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class FashionApiController extends Controller
{
    public function index()
    {
        try {
            $response = Http::get(
                'https://scenesku.com/api/v1/public-packs/womens-fashion'
            );

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nije moguće preuzeti podatke o modnim proizvodima.'
                ], 502);
            }

            $data = $response->json();

            return response()->json([
                'success' => true,
                'message' => 'Eksterni modni proizvodi uspešno učitani.',
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Došlo je do greške prilikom komunikacije sa javnim servisom.'
            ], 500);
        }
    }
}