<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

try {
    $posts = db()->query('SELECT slug, title, excerpt, category, cover, read_minutes, published_at FROM posts WHERE '
        . public_post_where() . ' ORDER BY published_at DESC, id DESC')->fetchAll();
} catch (Throwable $e) {
    error_log('blog.php: ' . $e->getMessage());
    $posts = null;
}

$desc = 'Artigos sobre comunicação, publicidade e marketing para quem gere marcas e negócios em Angola.';
$hero = page_hero('Blog', 'Blog da <span class="accent">Mark Seller</span>',
    'Artigos sobre comunicação, publicidade e marketing, com ideias práticas para quem gere marcas e negócios em Angola.');

if ($posts === null || !$posts) {
    $msg = $posts === null
        ? 'O blog está temporariamente indisponível. Volte a tentar dentro de momentos.'
        : 'Enquanto preparamos os conteúdos, acompanhe os nossos projectos ou fale directamente com a nossa equipa.';
    $head = $posts === null
        ? 'Estamos a <span class="accent">actualizar o blog</span>'
        : 'Os primeiros artigos estão <span class="accent">a caminho</span>';
    $body = $hero . '<section class="sec"><div class="wrap center narrow"><h2 class="title reveal-h">' . $head . '</h2>'
        . '<p class="lead dark">' . e($msg) . '</p><div class="btn-row">'
        . '<a class="btn primary" href="contactos.html">' . roll_label('Fale connosco') . '<i aria-hidden="true">↗</i></a>'
        . '<a class="btn ghost-dark" href="projectos.html">Ver projectos</a></div></div></section>';
    render_layout('Blog — Mark Seller', $desc, $body);
    return;
}

$first = array_shift($posts);
$furl = 'artigo.php?slug=' . rawurlencode($first['slug']);
$feat = '<article class="post-feat reveal"><a class="post-feat-img" href="' . e($furl) . '"><img alt="' . e($first['title']) . '" src="' . e($first['cover']) . '"/></a>'
    . '<div><div class="chips"><span class="hot">' . e($first['category']) . '</span><span>' . e(data_pt($first['published_at'])) . ' · ' . (int) $first['read_minutes'] . ' min de leitura</span></div>'
    . '<h2 class="title">' . e($first['title']) . '</h2><p class="lead dark">' . e($first['excerpt']) . '</p>'
    . '<a class="btn primary" href="' . e($furl) . '">' . roll_label('Ler artigo') . '<i aria-hidden="true">↗</i></a></div></article>';

$body = $hero . '<section class="sec"><div class="wrap">' . $feat;
if ($posts) {
    $cats = array_values(array_unique(array_column($posts, 'category')));
    $chips = '<button class="chip on" data-filter="TODOS" type="button">Todos</button>';
    foreach ($cats as $c) {
        $chips .= '<button class="chip" data-filter="' . e($c) . '" type="button">' . e($c) . '</button>';
    }
    $body .= '<div class="filters blog-filters">' . $chips . '<span class="count"><b id="count">' . count($posts) . '</b> artigos</span></div><div class="posts" id="posts">';
    foreach ($posts as $p) {
        $body .= post_card($p);
    }
    $body .= '</div>';
}
$body .= '</div></section>';

render_layout('Blog — Mark Seller', $desc, $body);
