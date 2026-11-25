<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

security_headers();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Método não permitido.']));
}
if (!current_user()) {
    http_response_code(401);
    exit(json_encode(['error' => 'Sessão expirada. Volte a entrar.']));
}
csrf_check();

try {
    if (empty($_FILES['file'])) {
        throw new RuntimeException('Nenhum ficheiro recebido.');
    }
    echo json_encode(['url' => save_upload($_FILES['file'])], JSON_UNESCAPED_SLASHES);
} catch (RuntimeException $e) {
    http_response_code(422);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
