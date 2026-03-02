<?php
// app/controllers/AvalController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Aval;

class AvalController extends Controller
{
    /**
     * GET /avales/by-client?client_id=123
     * Devuelve JSON con avales del cliente para el select del préstamo.
     */
    public function byClient(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $clientId = (int)($_GET['client_id'] ?? 0);
        if ($clientId <= 0) {
            echo json_encode([]);
            exit;
        }

        try {
            $avales = Aval::allByClient($clientId);
            echo json_encode($avales);
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([]);
            exit;
        }
    }
}