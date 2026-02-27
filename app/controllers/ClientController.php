<?php
// app/controllers/ClientController.php

namespace App\Controllers;

use App\Core\{Controller, Auth, CSRF, Validator, View, DB};
use App\Models\{Client, User};
use App\Services\DocumentService;

class ClientController extends Controller
{
    private DocumentService $docService;

    public function __construct()
    {
        parent::__construct();
        $this->docService = new DocumentService();
    }

    // LIST
    public function index(): void
    {
        $filters = [
            'search'      => $this->get('search', ''),
            'is_active'   => $this->get('status', ''),
            'assigned_to' => Auth::role() === 'asesor' ? Auth::id() : $this->get('assigned_to', ''),
        ];
        $perPage = (int)setting('items_per_page', 20);
        $page    = max(1, (int)$this->get('page', 1));
        $paged   = Client::all($filters, $perPage, $page);
        $advisors = User::allAdvisors();

        $this->render('clients/index', [
            'title'    => 'Clientes',
            'paged'    => $paged,
            'filters'  => $filters,
            'advisors' => $advisors,
        ]);
    }

    // SHOW
    public function show(string $id): void
    {
        $client = Client::find((int)$id);
        if (!$client) { $this->redirect('/clients'); }

        // Role check: asesor only sees assigned clients
        if (Auth::role() === 'asesor' && $client['assigned_to'] != Auth::id()) {
            $this->flashRedirect('/clients', 'error', 'No tiene acceso a este cliente.');
        }

        $loans     = Client::getLoans((int)$id);
        $documents = Client::getDocuments((int)$id);

        // Group docs by type
        $docsByType = [];
        foreach ($documents as $doc) {
            $docsByType[$doc['doc_type']][] = $doc;
        }

        $this->render('clients/show', [
            'title'      => $client['full_name'],
            'client'     => $client,
            'loans'      => $loans,
            'docsByType' => $docsByType,
        ]);
    }

    // CREATE FORM
    public function create(): void
    {
        $advisors = User::allAdvisors();
        $this->render('clients/form', [
            'title'    => 'Nuevo Cliente',
            'client'   => [],
            'advisors' => $advisors,
            'editMode' => false,
        ]);
    }

    // STORE
    public function store(): void
    {
        CSRF::check();

        $data = $_POST;
        $v = Validator::make($data, [
            'first_name' => 'required|max:100',
            'last_name'  => 'required|max:100',
            'phone'      => 'required|max:20',
            'email'      => 'email|max:180',
        ]);

        if ($v->fails()) {
            View::flash('error', $v->firstError());
            $this->redirect('/clients/create');
        }

        $id = Client::create($data, Auth::id());

        // Audit
        DB::insert('audit_log', [
            'user_id'   => Auth::id(),
            'action'    => 'create',
            'entity'    => 'clients',
            'entity_id' => $id,
            'new_data'  => json_encode(['first_name' => $data['first_name'], 'last_name' => $data['last_name']]),
            'ip_address'=> $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        $this->flashRedirect("/clients/$id", 'success', 'Cliente creado exitosamente.');
    }

    // EDIT FORM
    public function edit(string $id): void
    {
        $client = Client::find((int)$id);
        if (!$client) $this->redirect('/clients');

        $advisors = User::allAdvisors();
        $this->render('clients/form', [
            'title'    => 'Editar: ' . $client['full_name'],
            'client'   => $client,
            'advisors' => $advisors,
            'editMode' => true,
        ]);
    }

    // UPDATE
    public function update(string $id): void
    {
        CSRF::check();

        $data = $_POST;
        $v = Validator::make($data, [
            'first_name' => 'required|max:100',
            'last_name'  => 'required|max:100',
            'phone'      => 'required|max:20',
            'email'      => 'email|max:180',
        ]);

        if ($v->fails()) {
            View::flash('error', $v->firstError());
            $this->redirect("/clients/$id/edit");
        }

        $data['is_active'] = isset($data['is_active']) ? 1 : 0;
        Client::update((int)$id, $data);

        DB::insert('audit_log', [
            'user_id'   => Auth::id(), 'action' => 'update',
            'entity' => 'clients', 'entity_id' => (int)$id,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        $this->flashRedirect("/clients/$id", 'success', 'Cliente actualizado.');
    }

    // DELETE (soft)
    public function delete(string $id): void
    {
        $ok = Client::delete((int)$id);
        if (!$ok) {
            $this->flashRedirect('/clients', 'error', 'No se puede desactivar un cliente con préstamos activos.');
        }
        $this->flashRedirect('/clients', 'success', 'Cliente desactivado.');
    }

    // UPLOAD DOCUMENT
    public function uploadDoc(string $id): void
    {
        CSRF::check();

        $docType     = $_POST['doc_type'] ?? '';
        $description = $_POST['description'] ?? '';
        $allowedTypes = ['letra_cambio','pagare','identidad','contrato','evidencia','otro'];

        if (!in_array($docType, $allowedTypes, true)) {
            $this->flashRedirect("/clients/$id", 'error', 'Tipo de documento inválido.');
        }

        if (empty($_FILES['document']) || $_FILES['document']['error'] === UPLOAD_ERR_NO_FILE) {
            $this->flashRedirect("/clients/$id", 'error', 'Debe seleccionar un archivo.');
        }

        try {
            $this->docService->upload($_FILES['document'], (int)$id, $docType, $description);
            $this->flashRedirect("/clients/$id", 'success', 'Documento subido exitosamente.');
        } catch (\RuntimeException $e) {
            $this->flashRedirect("/clients/$id", 'error', $e->getMessage());
        }
    }

    // DELETE DOCUMENT
    public function deleteDoc(string $id, string $docId): void
    {
        $this->docService->delete((int)$docId);
        $this->flashRedirect("/clients/$id", 'success', 'Documento eliminado.');
    }

    // DOWNLOAD DOCUMENT
    public function downloadDoc(string $id, string $docId): void
    {
        // If cliente role, verify ownership
        if (Auth::role() === 'cliente') {
            $doc = DB::row("SELECT cd.client_id FROM client_documents cd WHERE cd.id = ?", [(int)$docId]);
            $client = Client::find((int)$id);
            if (!$doc || !$client || $client['user_id'] != Auth::id()) {
                http_response_code(403); exit;
            }
        }
        $this->docService->download((int)$docId);
    }
}