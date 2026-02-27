<?php
// app/core/View.php

namespace App\Core;

class View
{
    private string $viewPath;
    private array  $shared = [];

    public function __construct()
    {
        $this->viewPath = APP_PATH . '/views';
        // Share common data
        $this->shared['auth']     = Auth::user();
        $this->shared['config']   = require APP_PATH . '/config/app.php';
        $this->shared['flash']    = $this->consumeFlash();
    }

    /** Render a view with optional layout */
    public function render(string $view, array $data = [], ?string $layout = 'main'): void
    {
        $data = array_merge($this->shared, $data);

        // Extract for use in templates
        extract($data, EXTR_SKIP);

        // Buffer view content
        ob_start();
        $viewFile = $this->viewPath . '/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($viewFile)) {
            ob_end_clean();
            http_response_code(500);
            echo "<h2>Vista no encontrada: {$view}</h2>";
            echo "<p>Archivo buscado: {$viewFile}</p>";
            return;
        }
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }

        // Render inside layout
        $layoutFile = $this->viewPath . '/layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            echo $content;
            return;
        }
        require $layoutFile;
    }

    /** Flash message */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    private function consumeFlash(): array
    {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flash;
    }

    /** Generate partial view */
    public function partial(string $partial, array $data = []): void
    {
        extract(array_merge($this->shared, $data), EXTR_SKIP);
        $file = $this->viewPath . '/partials/' . $partial . '.php';
        if (file_exists($file)) require $file;
    }
}