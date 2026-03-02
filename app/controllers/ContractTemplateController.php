<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\CSRF;
use App\Models\ContractTemplate;

class ContractTemplateController extends Controller
{
    public function index(): void
    {
        $templates = ContractTemplate::all();
        $this->render('contract_templates/index', [
            'title' => 'Plantillas',
            'templates' => $templates
        ]);
    }

    public function create(): void
    {
        $this->render('contract_templates/form', [
            'title' => 'Nueva Plantilla',
            'template' => null
        ]);
    }

    public function store(): void
    {
        CSRF::verify();

        $name    = trim($_POST['name'] ?? '');
        $type    = trim($_POST['template_type'] ?? 'contrato');
        $content = $_POST['content'] ?? '';

        if ($name === '' || trim($content) === '') {
            $this->redirect('/contract-templates/create', 'Nombre y contenido son obligatorios.', 'error');
            return;
        }

        ContractTemplate::create([
            'name' => $name,
            'template_type' => $type,
            'content' => $content,
            'is_active' => 1,
        ]);

        $this->redirect('/contract-templates', 'Plantilla creada correctamente.', 'success');
    }

    public function edit(int $id): void
    {
        $template = ContractTemplate::find($id);
        if (!$template) {
            $this->redirect('/contract-templates', 'Plantilla no encontrada.', 'error');
            return;
        }

        $this->render('contract_templates/form', [
            'title' => 'Editar Plantilla',
            'template' => $template
        ]);
    }

    public function update(int $id): void
    {
        CSRF::verify();

        $template = ContractTemplate::find($id);
        if (!$template) {
            $this->redirect('/contract-templates', 'Plantilla no encontrada.', 'error');
            return;
        }

        $name     = trim($_POST['name'] ?? '');
        $type     = trim($_POST['template_type'] ?? 'contrato');
        $content  = $_POST['content'] ?? '';
        $isActive = (int)($_POST['is_active'] ?? 1);

        if ($name === '' || trim($content) === '') {
            $this->redirect('/contract-templates/'.$id.'/edit', 'Nombre y contenido son obligatorios.', 'error');
            return;
        }

        ContractTemplate::update($id, [
            'name' => $name,
            'template_type' => $type,
            'content' => $content,
            'is_active' => $isActive,
        ]);

        $this->redirect('/contract-templates', 'Plantilla actualizada correctamente.', 'success');
    }

    public function toggle(int $id): void
    {
        $template = ContractTemplate::find($id);
        if (!$template) {
            $this->redirect('/contract-templates', 'Plantilla no encontrada.', 'error');
            return;
        }

        $new = ((int)$template['is_active'] === 1) ? 0 : 1;
        ContractTemplate::update($id, ['is_active' => $new]);

        $this->redirect('/contract-templates', 'Estado actualizado.', 'success');
    }

    // ✅ antes se llamaba render() y chocaba con Controller::render()
    public function preview(int $id): void
    {
        $template = ContractTemplate::find($id);
        if (!$template) {
            $this->redirect('/contract-templates', 'Plantilla no encontrada.', 'error');
            return;
        }

        // sin layout (pasando null)
        $this->render('contract_templates/render', [
            'title' => $template['name'] ?? 'Documento',
            'html'  => $template['content'] ?? '',
        ], null);
    }
}