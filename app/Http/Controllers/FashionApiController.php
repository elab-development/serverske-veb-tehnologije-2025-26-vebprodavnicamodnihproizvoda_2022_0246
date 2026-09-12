<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use OpenApi\Attributes as OA;

class FashionApiController extends Controller
{
    #[OA\Get(
    path: "/external-fashion",
    summary: "Preuzimanje eksternih modnih podataka",
    description: "Preuzima podatke o ženskoj modi sa javnog eksternog REST servisa.",
    tags: ["Spoljašnji servisi"],
    responses: [
        new OA\Response(
            response: 200,
            description: "Eksterni modni proizvodi uspešno učitani"
        ),
        new OA\Response(
            response: 502,
            description: "Nije moguće preuzeti podatke sa eksternog servisa"
        ),
        new OA\Response(
            response: 500,
            description: "Greška u komunikaciji sa javnim servisom"
        )
    ]
)]

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