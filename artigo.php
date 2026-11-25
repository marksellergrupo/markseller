<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/markdown.php';

$slug = (string) ($_GET['slug'] ?? '');
$post = null;
if (preg_match('/^[a-z0-9-]{1,190}$/', $slug)) {
    try {
        // Rascunhos e artigos agendados só se vêem com sessão de administrador (?preview=1).
        $preview = isset($_GET['preview']) && current_user() !== null;
        $st = db()->prepare('SELECT * FROM posts WHERE slug = ?' . ($preview ? '' : ' AND ' . public_post_where()));
        $st->execute([$slug]);
        $post = $st->fetch() ?: null;
    } catch (Throwable $e) {
        error_log('artigo.php: ' . $e->getMessage());
    }
}

if (!$post) {
    $body = page_hero('Blog', 'Artigo <span class="accent">não encontrado</span>', 'O artigo que procura não existe ou já não está disponível.')
        . '<section class="sec"><div class="wrap center narrow"><div class="btn-row"><a class="btn primary" href="blog.php">'
        . roll_label('Ver todos os artigos') . '<i aria-hidden="true">↗</i></a></div></div></section>';
    render_layout('Artigo não encontrado — Mark Seller', 'Artigo não encontrado.', $body, 404);
    return;
}

$st = db()->prepare('SELECT slug, title, excerpt, category, cover, read_minutes, published_at FROM posts WHERE '
    . public_post_where() . ' AND id <> ? ORDER BY (category = ?) DESC, published_at DESC LIMIT 3');
$st->execute([$post['id'], $post['category']]);
$related = $st->fetchAll();

$notLive = $post['status'] !== 'published' || ($post['published_at'] && strtotime((string) $post['published_at']) > time());
$date = $post['published_at'] ? data_pt($post['published_at']) : 'Rascunho';

$body = '<section class="page-hero art-hero"><div class="ph-bg" aria-hidden="true"></div><div class="wrap">'
    . '<p class="crumb"><a href="index.html">Início</a><span>/</span><a href="blog.php">Blog</a><span>/</span>' . e($post['category']) . '</p>'
    . '<div class="chips light"><span>' . e($post['category']) . '</span><span>' . e($date) . '</span><span>' . (int) $post['read_minutes'] . ' min de leitura</span>'
    . ($notLive ? '<span>Pré-visualização (não publicado)</span>' : '') . '</div>'
    . '<h1 class="title reveal-h">' . e($post['title']) . '</h1></div></section>'
    . '<div class="read-bar" aria-hidden="true"><i></i></div>'
    . '<section class="sec art"><div class="wrap narrow">'
    . ($post['cover'] !== '' ? '<figure class="art-cover"><img alt="' . e($post['title']) . '" src="' . e($post['cover']) . '"/></figure>' : '')
    . '<div class="art-body">' . md_render((string) $post['content']) . '</div>'
    . '<div class="art-foot"><div><b>' . e($post['author']) . '</b><span>Comunicação, marketing e publicidade em Angola</span></div>'
    . '<button class="btn ghost-dark" type="button" data-copy>Copiar ligação<i aria-hidden="true">↗</i></button></div>'
    . '</div></section>';

if ($related) {
    $body .= '<section class="sec related"><div class="wrap"><div class="sec-head row"><div><p class="eyebrow">Continue a ler</p>'
        . '<h2 class="title reveal-h">Outros <span class="accent">artigos</span></h2></div>'
        . '<a class="btn ghost-dark" href="blog.php">' . roll_label('Todos os artigos') . '<i aria-hidden="true">↗</i></a></div><div class="posts">';
    foreach ($related as $r) {
        $body .= post_card($r);
    }
    $body .= '</div></div></section>';
}

render_layout($post['title'] . ' — Blog Mark Seller', (string) $post['excerpt'], $body);
