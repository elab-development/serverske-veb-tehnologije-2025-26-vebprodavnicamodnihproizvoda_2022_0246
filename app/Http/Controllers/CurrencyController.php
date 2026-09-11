<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CurrencyController extends Controller
{
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