<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
    path: "/register",
    summary: "Registracija korisnika",
    description: "Registruje novog korisnika, čuva njegove podatke u bazi i generiše Laravel Sanctum pristupni token.",
    tags: ["Autentifikacija"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["name", "email", "password"],
            properties: [
                new OA\Property(
                    property: "name",
                    type: "string",
                    example: "Saska Savic"
                ),
                new OA\Property(
                    property: "email",
                    type: "string",
                    format: "email",
                    example: "saska@example.com"
                ),
                new OA\Property(
                    property: "password",
                    type: "string",
                    format: "password",
                    example: "password123"
                )
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: "Korisnik uspešno registrovan"
        ),
        new OA\Response(
            response: 422,
            description: "Greška validacije"
        )
    ]
)]
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Korisnik uspešno registrovan.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 201);
    }

#[OA\Post(
    path: "/login",
    summary: "Prijava korisnika",
    description: "Proverava email i lozinku korisnika i nakon uspešne prijave generiše novi Laravel Sanctum pristupni token.",
    tags: ["Autentifikacija"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email", "password"],
            properties: [
                new OA\Property(
                    property: "email",
                    type: "string",
                    format: "email",
                    example: "saska@example.com"
                ),
                new OA\Property(
                    property: "password",
                    type: "string",
                    format: "password",
                    example: "password123"
                )
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Uspešna prijava"
        ),
        new OA\Response(
            response: 401,
            description: "Pogrešni kredencijali"
        ),
        new OA\Response(
            response: 422,
            description: "Greška validacije"
        )
    ]
)]

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Pogrešni kredencijali za logovanje.'
            ], 401);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Uspešno ste se ulogovali.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 200);
    }

#[OA\Post(
    path: "/logout",
    summary: "Odjava korisnika",
    description: "Odjavljuje autentifikovanog korisnika brisanjem trenutnog Laravel Sanctum pristupnog tokena.",
    tags: ["Autentifikacija"],
    security: [["sanctum" => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: "Uspešna odjava"
        ),
        new OA\Response(
            response: 401,
            description: "Korisnik nije autentifikovan"
        )
    ]
)]

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Uspešno ste se izlogovali. Token je obrisan.'
        ], 200);
    }
}