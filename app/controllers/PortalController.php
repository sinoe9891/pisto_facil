<?php
// app/controllers/PortalController.php

namespace App\Controllers;

use App\Core\{Controller, Auth, DB};
use App\Models\Client;

class PortalController extends Controller
{
    public function index(): void
    {
        // Only 'cliente' role uses portal; others redirect to dashboard
        if (!Auth::hasRole('cliente')) {
            $this->redirect('/dashboard');
        }

        // Find the client linked to this user
        $client = DB::row(
            "SELECT * FROM clients WHERE user_id = ? AND is_active = 1",
            [Auth::id()]
        );

        if (!$client) {
            $this->render('portal/index', [
                'title'  => 'Mis Préstamos',
                'client' => null,
                'loans'  => [],
            ]);
            return;
        }

        $loans = DB::all(
            "SELECT l.*, u.name as advisor_name FROM loans l
             LEFT JOIN users u ON u.id = l.assigned_to
             WHERE l.client_id = ? ORDER BY l.status, l.created_at DESC",
            [$client['id']]
        );

        // Upcoming installments
        $upcoming = DB::all(
            "SELECT li.*, l.loan_number
             FROM loan_installments li
             JOIN loans l ON l.id = li.loan_id
             WHERE l.client_id = ? AND li.status IN ('pending','partial') AND li.due_date >= CURDATE()
             ORDER BY li.due_date LIMIT 10",
            [$client['id']]
        );

        // Documents (downloadable)
        $documents = Client::getDocuments($client['id']);

        $this->render('portal/index', [
            'title'     => 'Mis Préstamos',
            'client'    => $client,
            'loans'     => $loans,
            'upcoming'  => $upcoming,
            'documents' => $documents,
        ]);
    }
}
