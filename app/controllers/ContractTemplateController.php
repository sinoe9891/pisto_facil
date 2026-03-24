<?php

namespace App\Controllers;

use App\Core\{Controller, Auth, CSRF, View};
use App\Models\ContractTemplate;

class ContractTemplateController extends Controller
{
    public function index(): void
    {
        $templates = ContractTemplate::all();
        $this->render('contract_templates/index', [
            'title'     => 'Plantillas de Documentos',
            'templates' => $templates,
        ]);
    }

    public function create(): void
    {
        $this->render('contract_templates/form', [
            'title'    => 'Nueva Plantilla',
            'template' => null,
        ]);
    }

    public function store(): void
    {
        CSRF::check();  // ← fix: era CSRF::verify()

        $name    = trim($_POST['name']          ?? '');
        $type    = trim($_POST['template_type'] ?? 'contrato');
        $content = $_POST['content']            ?? '';

        if ($name === '' || trim($content) === '') {
            $this->flashRedirect('/contract-templates/create', 'error', 'Nombre y contenido son obligatorios.');
        }

        ContractTemplate::create([
            'name'          => $name,
            'template_type' => $type,
            'content'       => $content,
            'is_active'     => 1,
            'created_by'    => Auth::id(),
        ]);

        $this->flashRedirect('/contract-templates', 'success', 'Plantilla creada correctamente.');
    }

    public function edit(string $id): void
    {
        $template = ContractTemplate::find((int)$id);
        if (!$template) {
            $this->flashRedirect('/contract-templates', 'error', 'Plantilla no encontrada.');
        }

        $this->render('contract_templates/form', [
            'title'    => 'Editar Plantilla',
            'template' => $template,
        ]);
    }

    public function update(string $id): void
    {
        CSRF::check();  // ← fix: era CSRF::verify()

        $template = ContractTemplate::find((int)$id);
        if (!$template) {
            $this->flashRedirect('/contract-templates', 'error', 'Plantilla no encontrada.');
        }

        $name     = trim($_POST['name']          ?? '');
        $type     = trim($_POST['template_type'] ?? 'contrato');
        $content  = $_POST['content']            ?? '';
        $isActive = (int)($_POST['is_active']    ?? 1);

        if ($name === '' || trim($content) === '') {
            $this->flashRedirect('/contract-templates/' . $id . '/edit', 'error', 'Nombre y contenido son obligatorios.');
        }

        ContractTemplate::update((int)$id, [
            'name'          => $name,
            'template_type' => $type,
            'content'       => $content,
            'is_active'     => $isActive,
        ]);

        $this->flashRedirect('/contract-templates', 'success', 'Plantilla actualizada correctamente.');
    }

    public function toggle(string $id): void
    {
        $template = ContractTemplate::find((int)$id);
        if (!$template) {
            $this->flashRedirect('/contract-templates', 'error', 'Plantilla no encontrada.');
        }

        $new = ((int)$template['is_active'] === 1) ? 0 : 1;
        ContractTemplate::update((int)$id, ['is_active' => $new]);

        $msg = $new ? 'Plantilla activada.' : 'Plantilla desactivada.';
        $this->flashRedirect('/contract-templates', 'success', $msg);
    }

    public function preview(string $id): void
    {
        $template = ContractTemplate::find((int)$id);
        if (!$template) {
            $this->flashRedirect('/contract-templates', 'error', 'Plantilla no encontrada.');
        }

        $this->render('contract_templates/render', [
            'title' => $template['name'] ?? 'Documento',
            'html'  => $template['content'] ?? '',
        ], null); // null = sin layout
    }
}
