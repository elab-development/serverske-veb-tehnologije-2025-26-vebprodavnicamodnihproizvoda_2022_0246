<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenApi\Attributes as OA;

class CurrencyController extends Controller
{
    #[OA\Get(
    path: "/currency/convert",
    summary: "Konverzija valuta",
    description: "Konvertuje zadati iznos iz jedne valute u drugu korišćenjem javnog Frankfurter REST servisa.",
    tags: ["Spoljašnji servisi"],
    parameters: [
        new OA\Parameter(
            name: "amount",
            in: "query",
            required: true,
            description: "Iznos za konverziju",
            schema: new OA\Schema(type: "number", format: "float"),
            example: 100
        ),
        new OA\Parameter(
            name: "from",
            in: "query",
            required: true,
            description: "Početna valuta, troslovni kod",
            schema: new OA\Schema(type: "string"),
            example: "EUR"
        ),
        new OA\Parameter(
            name: "to",
            in: "query",
            required: true,
            description: "Ciljna valuta, troslovni kod",
            schema: new OA\Schema(type: "string"),
            example: "USD"
        )
    ],
    responses: [
        new OA\Response(response: 200, description: "Konverzija valuta uspešno izvršena"),
        new OA\Response(response: 422, description: "Greška validacije"),
        new OA\Response(response: 502, description: "Nije moguće preuzeti kurs valuta"),
        new OA\Response(response: 500, description: "Greška u komunikaciji sa javnim servisom")
    ]
)]

    public function convert(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
        ]);

        $amount = $validated['amount'];
        $from = strtoupper($validated['from']);
        $to = strtoupper($validated['to']);

        try {
            $response = Http::get(
                "https://api.frankfurter.dev/v2/rate/{$from}/{$to}"
            );

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nije moguće preuzeti kurs valuta.'
                ], 502);
            }

            $data = $response->json();

            $rate = $data['rate'];

            $convertedAmount = round($amount * $rate, 2);

            return response()->json([
                'success' => true,
                'message' => 'Konverzija valuta uspešno izvršena.',
                'data' => [
                    'amount' => $amount,
                    'from' => $from,
                    'to' => $to,
                    'rate' => $rate,
                    'converted_amount' => $convertedAmount,
                    'rate_date' => $data['date'] ?? null
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Došlo je do greške prilikom komunikacije sa javnim servisom.'
            ], 500);
        }
    }
}