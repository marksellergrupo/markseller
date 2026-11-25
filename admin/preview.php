<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/markdown.php';

security_headers();
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !current_user()) {
    http_response_code(403);
    exit;
}
csrf_check();
// Caminhos de imagens são relativos à raiz do site; o admin está um nível abaixo.
$html = md_render(mb_substr((string) ($_POST['content'] ?? ''), 0, 200000));
echo preg_replace('#(<img[^>]+src=")(?!https?://|/)#', '$1../', $html);
