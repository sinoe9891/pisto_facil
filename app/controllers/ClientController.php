<?php
// app/controllers/ClientController.php

namespace App\Controllers;

use App\Core\{Controller, Auth, CSRF, Validator, View, DB};
use App\Models\{Client, User};
use App\Services\DocumentService;

class ClientController extends Controller
{
    private DocumentService $docService;

    // Allowed image mime types
    private const IMAGE_MIME = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_IMG_SIZE = 5 * 1024 * 1024; // 5 MB

    public function __construct()
    {
        parent::__construct();
        $this->docService = new DocumentService();
    }

    // ── LIST ─────────────────────────────────────────────────
    public function index(): void
    {
        $filters = [
            'search'      => $this->get('search', ''),
            'is_active'   => $this->get('status', ''),
            'assigned_to' => Auth::role() === 'asesor' ? Auth::id() : $this->get('assigned_to', ''),
        ];
        $perPage  = (int)setting('items_per_page', 20);
        $page     = max(1, (int)$this->get('page', 1));
        $paged    = Client::all($filters, $perPage, $page);
        $advisors = User::allAdvisors();

        $this->render('clients/index', [
            'title'    => 'Clientes',
            'paged'    => $paged,
            'filters'  => $filters,
            'advisors' => $advisors,
        ]);
    }

    // ── SHOW ─────────────────────────────────────────────────
    public function show(string $id): void
    {
        $client = Client::find((int)$id);
        if (!$client) {
            $this->redirect('/clients');
        }

        if (Auth::role() === 'asesor' && $client['assigned_to'] != Auth::id()) {
            $this->flashRedirect('/clients', 'error', 'No tiene acceso a este cliente.');
        }

        $loans     = Client::getLoans((int)$id);
        $documents = Client::getDocuments((int)$id);
        $aval      = Client::getAval((int)$id);

        $docsByType = [];
        foreach ($documents as $doc) {
            $docsByType[$doc['doc_type']][] = $doc;
        }

        $this->render('clients/show', [
            'title'      => $client['full_name'],
            'client'     => $client,
            'loans'      => $loans,
            'docsByType' => $docsByType,
            'aval'       => $aval,
        ]);
    }

    // ── CREATE FORM ──────────────────────────────────────────
    public function create(): void
    {
        $advisors = User::allAdvisors();
        // Clientes existentes para seleccionar como aval
        $allClients = DB::all("SELECT id, CONCAT(first_name,' ',last_name) as full_name, identity_number
                                FROM clients WHERE is_active=1 ORDER BY last_name, first_name");
        $this->render('clients/form', [
            'title'      => 'Nuevo Cliente',
            'client'     => [],
            'aval'       => [],
            'advisors'   => $advisors,
            'allClients' => $allClients,
            'editMode'   => false,
        ]);
    }

    // ── STORE ────────────────────────────────────────────────
    public function store(): void
    {
        CSRF::check();

        $data = $_POST;
        $v = Validator::make($data, [
            'first_name'      => 'required|max:100',
            'last_name'       => 'required|max:100',
            'phone'           => 'required|max:20',
            'identity_number' => 'required|max:30',
            'email'           => 'email|max:180',
        ]);

        if ($v->fails()) {
            View::flash('error', $v->firstError());
            $this->redirect('/clients/create');
        }

        // Procesar imágenes de identidad del cliente
        $data['identity_front_path'] = $this->saveIdentityImage('identity_front', 'clients');
        $data['identity_back_path']  = $this->saveIdentityImage('identity_back',  'clients');

        $id = Client::create($data, Auth::id());

        // Guardar aval si se proporcionó
        if (!empty(trim($data['aval_full_name'] ?? ''))) {
            $data['aval_identity_front_path'] = $this->saveIdentityImage('aval_identity_front', 'avales');
            $data['aval_identity_back_path']  = $this->saveIdentityImage('aval_identity_back',  'avales');
            Client::saveAval($id, $data);
        }

        DB::insert('audit_log', [
            'user_id'   => Auth::id(),
            'action'    => 'create',
            'entity'    => 'clients',
            'entity_id' => $id,
            'new_data'  => json_encode(['first_name' => $data['first_name'], 'last_name' => $data['last_name']]),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        $this->flashRedirect("/clients/$id", 'success', 'Cliente creado exitosamente.');
    }

    // ── EDIT FORM ────────────────────────────────────────────
    public function edit(string $id): void
    {
        $client = Client::find((int)$id);
        if (!$client) $this->redirect('/clients');

        $advisors   = User::allAdvisors();
        $aval       = Client::getAval((int)$id);
        $allClients = DB::all(
            "SELECT id, CONCAT(first_name,' ',last_name) as full_name, identity_number
                                FROM clients WHERE is_active=1 AND id != ? ORDER BY last_name, first_name",
            [(int)$id]
        );

        $this->render('clients/form', [
            'title'      => 'Editar: ' . $client['full_name'],
            'client'     => $client,
            'aval'       => $aval ?? [],
            'advisors'   => $advisors,
            'allClients' => $allClients,
            'editMode'   => true,
        ]);
    }

    // ── UPDATE ───────────────────────────────────────────────
    public function update(string $id): void
    {
        CSRF::check();

        $data = $_POST;
        $v = Validator::make($data, [
            'first_name'      => 'required|max:100',
            'last_name'       => 'required|max:100',
            'phone'           => 'required|max:20',
            'identity_number' => 'required|max:30',
            'email'           => 'email|max:180',
        ]);

        if ($v->fails()) {
            View::flash('error', $v->firstError());
            $this->redirect("/clients/$id/edit");
        }

        $data['is_active'] = isset($data['is_active']) ? 1 : 0;

        // Actualizar imágenes solo si se subieron nuevas
        $existing = Client::find((int)$id);
        $newFront = $this->saveIdentityImage('identity_front', 'clients');
        $newBack  = $this->saveIdentityImage('identity_back',  'clients');
        if ($newFront) $data['identity_front_path'] = $newFront;
        else           $data['identity_front_path'] = $existing['identity_front_path'];
        if ($newBack)  $data['identity_back_path']  = $newBack;
        else           $data['identity_back_path']  = $existing['identity_back_path'];

        Client::update((int)$id, $data);

        // Aval
        if (!empty(trim($data['aval_full_name'] ?? ''))) {
            $avalExisting = Client::getAval((int)$id);
            $newAvalFront = $this->saveIdentityImage('aval_identity_front', 'avales');
            $newAvalBack  = $this->saveIdentityImage('aval_identity_back',  'avales');
            if ($newAvalFront) $data['aval_identity_front_path'] = $newAvalFront;
            else               $data['aval_identity_front_path'] = $avalExisting['identity_front_path'] ?? null;
            if ($newAvalBack)  $data['aval_identity_back_path']  = $newAvalBack;
            else               $data['aval_identity_back_path']  = $avalExisting['identity_back_path'] ?? null;
            Client::saveAval((int)$id, $data);
        } elseif (isset($data['remove_aval']) && $data['remove_aval']) {
            Client::deleteAval((int)$id);
        }

        DB::insert('audit_log', [
            'user_id'   => Auth::id(),
            'action' => 'update',
            'entity' => 'clients',
            'entity_id' => (int)$id,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        $this->flashRedirect("/clients/$id", 'success', 'Cliente actualizado.');
    }

    // ── DELETE (soft) ────────────────────────────────────────
    public function delete(string $id): void
    {
        $ok = Client::delete((int)$id);
        if (!$ok) {
            $this->flashRedirect('/clients', 'error', 'No se puede desactivar un cliente con préstamos activos.');
        }
        $this->flashRedirect('/clients', 'success', 'Cliente desactivado.');
    }

    // ── UPLOAD DOCUMENT ──────────────────────────────────────
    public function uploadDoc(string $id): void
    {
        CSRF::check();
        $docType     = $_POST['doc_type'] ?? '';
        $description = $_POST['description'] ?? '';
        $allowedTypes = ['letra_cambio', 'pagare', 'identidad', 'contrato', 'evidencia', 'otro'];

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

    // ── DELETE DOCUMENT ──────────────────────────────────────
    public function deleteDoc(string $id, string $docId): void
    {
        $this->docService->delete((int)$docId);
        $this->flashRedirect("/clients/$id", 'success', 'Documento eliminado.');
    }

    // ── DOWNLOAD DOCUMENT ────────────────────────────────────
    public function downloadDoc(string $id, string $docId): void
    {
        if (Auth::role() === 'cliente') {
            $doc    = DB::row("SELECT cd.client_id FROM client_documents cd WHERE cd.id = ?", [(int)$docId]);
            $client = Client::find((int)$id);
            if (!$doc || !$client || $client['user_id'] != Auth::id()) {
                http_response_code(403);
                exit;
            }
        }
        $this->docService->download((int)$docId);
    }

    // ── PRIVATE: save identity image ─────────────────────────
    private function saveIdentityImage(string $fieldName, string $folder): ?string
    {
        if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        $file = $_FILES[$fieldName];
        if ($file['size'] > self::MAX_IMG_SIZE) return null;

        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, self::IMAGE_MIME, true)) return null;

        $ext     = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg'
        };
        $projectBase = is_dir(ROOT_PATH . '/app') ? ROOT_PATH : dirname(ROOT_PATH);
        $dir = $projectBase . '/storage/identities/' . $folder . '/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException("No se pudo crear el directorio: {$dir}");
        }

        $filename = uniqid($fieldName . '_', true) . '.' . $ext;
        $dest     = $dir . $filename;

        // Redimensionar si es muy grande (max 1200px ancho)
        $this->resizeImage($file['tmp_name'], $dest, $mime, 1200);

        return 'storage/identities/' . $folder . '/' . $filename;
    }

    private function resizeImage(string $src, string $dest, string $mime, int $maxW): void
    {
        $img = match ($mime) {
            'image/png'  => imagecreatefrompng($src),
            'image/webp' => imagecreatefromwebp($src),
            default      => imagecreatefromjpeg($src),
        };
        if (!$img) {
            copy($src, $dest);
            return;
        }

        [$w, $h] = [imagesx($img), imagesy($img)];
        if ($w <= $maxW) {
            // Solo guardar sin redimensionar
            match ($mime) {
                'image/png'  => imagepng($img, $dest),
                'image/webp' => imagewebp($img, $dest),
                default      => imagejpeg($img, $dest, 88),
            };
        } else {
            $newH  = (int)round($h * ($maxW / $w));
            $thumb = imagecreatetruecolor($maxW, $newH);
            // Fondo blanco para PNG
            if ($mime === 'image/png') {
                imagefill($thumb, 0, 0, imagecolorallocate($thumb, 255, 255, 255));
            }
            imagecopyresampled($thumb, $img, 0, 0, 0, 0, $maxW, $newH, $w, $h);
            match ($mime) {
                'image/png'  => imagepng($thumb, $dest),
                'image/webp' => imagewebp($thumb, $dest),
                default      => imagejpeg($thumb, $dest, 88),
            };
            imagedestroy($thumb);
        }
        imagedestroy($img);
    }
}
