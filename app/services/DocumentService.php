<?php
// app/services/DocumentService.php

namespace App\Services;

use App\Core\{DB, Auth};

class DocumentService
{
    private array $allowedMimes = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
    ];

    private int    $maxBytes;
    private string $uploadDir;

    public function __construct()
    {
        $cfg            = require APP_PATH . '/config/app.php';
        $this->maxBytes = $cfg['max_upload_bytes'] ?? 10 * 1024 * 1024;
        $this->uploadDir = $cfg['upload_path'];
    }

    /** Process uploaded file, save to disk, record in DB */
    public function upload(array $file, int $clientId, string $docType, string $description = ''): array
    {
        // Validate
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Error al subir archivo: código ' . $file['error']);
        }
        if ($file['size'] > $this->maxBytes) {
            throw new \RuntimeException('El archivo supera el tamaño máximo permitido (' . ($this->maxBytes / 1024 / 1024) . ' MB).');
        }

        // Detect real MIME (not trust browser)
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file['tmp_name']);

        if (!array_key_exists($realMime, $this->allowedMimes)) {
            throw new \RuntimeException('Tipo de archivo no permitido: ' . $realMime . '. Solo PDF, JPG, PNG, WEBP.');
        }

        // Build safe path
        $ext        = $this->allowedMimes[$realMime];
        $hash       = hash_file('sha256', $file['tmp_name']);
        $stored     = $hash . '_' . time() . '.' . $ext;
        $subDir     = $this->uploadDir . '/' . $clientId;

        if (!is_dir($subDir) && !mkdir($subDir, 0750, true)) {
            throw new \RuntimeException('No se pudo crear el directorio de almacenamiento.');
        }

        $dest = $subDir . '/' . $stored;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Error al mover el archivo al destino.');
        }

        // Persist in DB
        $docId = DB::insert('client_documents', [
            'client_id'     => $clientId,
            'doc_type'      => $docType,
            'original_name' => $file['name'],
            'stored_name'   => $stored,
            'path'          => $clientId . '/' . $stored,
            'mime_type'     => $realMime,
            'file_size'     => $file['size'],
            'file_hash'     => $hash,
            'description'   => $description,
            'uploaded_by'   => Auth::id(),
        ]);

        return ['id' => $docId, 'stored_name' => $stored, 'mime' => $realMime];
    }

    /** Delete document file and DB record */
    public function delete(int $docId): bool
    {
        $doc = DB::row("SELECT * FROM client_documents WHERE id = ?", [$docId]);
        if (!$doc) return false;

        $cfg  = require APP_PATH . '/config/app.php';
        $path = $cfg['upload_path'] . '/' . $doc['path'];
        if (file_exists($path)) @unlink($path);

        DB::delete('client_documents', 'id = ?', [$docId]);
        return true;
    }

    /** Return file path for download */
    public function getPath(int $docId): ?string
    {
        $doc = DB::row("SELECT * FROM client_documents WHERE id = ?", [$docId]);
        if (!$doc) return null;

        $cfg  = require APP_PATH . '/config/app.php';
        $path = $cfg['upload_path'] . '/' . $doc['path'];
        return file_exists($path) ? $path : null;
    }

    /** Serve file as download */
    public function download(int $docId): void
    {
        $doc  = DB::row("SELECT * FROM client_documents WHERE id = ?", [$docId]);
        if (!$doc) { http_response_code(404); exit; }

        $path = $this->getPath($docId);
        if (!$path) { http_response_code(404); exit; }

        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: attachment; filename="' . addslashes($doc['original_name']) . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, no-cache');
        readfile($path);
        exit;
    }

    public static function docTypeLabel(string $type): string
    {
        return match ($type) {
            'letra_cambio' => 'Letra de Cambio',
            'pagare'       => 'Pagaré',
            'identidad'    => 'Identificación',
            'contrato'     => 'Contrato',
            'evidencia'    => 'Evidencia/Foto',
            default        => 'Otro',
        };
    }
}