<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Web prodavnica modnih proizvoda API",
    description: "REST API dokumentacija za aplikaciju Web prodavnica modnih proizvoda."
)]

#[OA\Server(
    url: "http://localhost/api",
    description: "Lokalni razvojni server"
)]

#[OA\SecurityScheme(
    securityScheme: "sanctum",
    type: "http",
    scheme: "bearer",
    bearerFormat: "Token",
    description: "Uneti Laravel Sanctum pristupni token."
)]
class OpenApiSpec
{
}