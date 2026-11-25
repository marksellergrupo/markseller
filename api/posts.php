<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/layout.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');
header('X-Content-Type-Options: nosniff');

$limit = max(1, min(12, (int) ($_GET['limit'] ?? 3)));
try {
    $st = db()->prepare('SELECT slug, title, excerpt, category, cover, read_minutes, published_at FROM posts WHERE '
        . public_post_where() . ' ORDER BY published_at DESC, id DESC LIMIT ' . $limit);
    $st->execute();
    $out = array_map(static fn(array $p): array => [
        'url' => 'artigo.php?slug=' . rawurlencode($p['slug']),
        'title' => $p['title'],
        'excerpt' => $p['excerpt'],
        'category' => $p['category'],
        'cover' => $p['cover'],
        'minutes' => (int) $p['read_minutes'],
        'date' => data_pt($p['published_at'], true),
    ], $st->fetchAll());
    echo json_encode(['posts' => $out], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('api/posts.php: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['posts' => []]);
}
