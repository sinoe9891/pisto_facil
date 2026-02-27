<?php
// app/core/Controller.php

namespace App\Core;

class Controller
{
    protected View $view;

    public function __construct()
    {
        $this->view = new View();
    }

    /** Shorthand render */
    protected function render(string $view, array $data = [], ?string $layout = 'main'): void
    {
        $this->view->render($view, $data, $layout);
    }

    /** Redirect */
    protected function redirect(string $path, int $code = 302): void
    {
        header('Location: ' . url($path), true, $code);
        exit;
    }

    /** JSON response */
    protected function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Flash and redirect */
    protected function flashRedirect(string $path, string $type, string $msg): void
    {
        View::flash($type, $msg);
        $this->redirect($path);
    }

    /** Get POST data safely */
    protected function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /** Get GET data safely */
    protected function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /** Paginate query results */
    protected function paginate(string $sql, array $params, string $countSql = '', array $countParams = []): array
    {
        $perPage = (int)(setting('items_per_page') ?? 20);
        $page    = max(1, (int)($this->get('page', 1)));
        $offset  = ($page - 1) * $perPage;

        // Count total
        if ($countSql) {
            $total = (int)array_values(DB::row($countSql, $countParams) ?? [0])[0];
        } else {
            $countQuery = "SELECT COUNT(*) as total FROM ({$sql}) sub";
            $total      = (int)(DB::row($countQuery, $params)['total'] ?? 0);
        }

        // Paginated results
        $rows = DB::all($sql . " LIMIT $perPage OFFSET $offset", $params);

        return [
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int)ceil($total / $perPage),
        ];
    }
}