<?php
declare(strict_types=1);

/** Envolve o conteúdo no cabeçalho e rodapé do site (ficheiros gerados por tools/export_layout.py). */
function render_layout(string $title, string $description, string $body, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    $top = (string) file_get_contents(__DIR__ . '/layout-top.html');
    $bottom = (string) file_get_contents(__DIR__ . '/layout-bottom.html');
    echo str_replace(['@@TITLE@@', '@@DESC@@'], [e($title), e($description)], $top), $body, $bottom;
}

/** Cartão de artigo (listagem, início e artigos relacionados). */
function post_card(array $p): string
{
    $url = 'artigo.php?slug=' . rawurlencode($p['slug']);
    return '<article class="post reveal" data-category="' . e($p['category']) . '">'
        . '<a class="post-img" href="' . e($url) . '"><img loading="lazy" alt="' . e($p['title']) . '" src="' . e($p['cover']) . '"/></a>'
        . '<div class="post-meta"><div class="chips"><span class="hot">' . e($p['category']) . '</span><span>'
        . e(data_pt($p['published_at'], true)) . ' · ' . (int) $p['read_minutes'] . ' min</span></div>'
        . '<h3><a href="' . e($url) . '">' . e($p['title']) . '</a></h3><p>' . e($p['excerpt']) . '</p>'
        . '<a class="link-arrow" href="' . e($url) . '">Ler artigo <i>↗</i></a></div></article>';
}

function public_post_where(): string
{
    return "status = 'published' AND published_at IS NOT NULL AND published_at <= NOW()";
}

function roll_label(string $text): string
{
    return '<span class="roll"><span class="r" data-t="' . e($text) . '">' . e($text) . '</span></span>';
}

function page_hero(string $label, string $h1Html, string $lead): string
{
    return '<section class="page-hero"><div class="ph-bg" aria-hidden="true"></div><div class="wrap">'
        . '<p class="crumb"><a href="index.html">Início</a><span>/</span>' . $label . '</p>'
        . '<h1 class="title reveal-h">' . $h1Html . '</h1><p class="lead">' . e($lead) . '</p></div></section>';
}
