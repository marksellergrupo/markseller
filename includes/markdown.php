<?php
declare(strict_types=1);

/**
 * Conversor de Markdown simples e seguro para os artigos.
 * Suporta: ## título, ### subtítulo, parágrafos, listas (- / 1.), > citação,
 * **negrito**, *itálico*, [ligação](url), ![imagem](url).
 * Todo o texto é escapado antes de converter, por isso não passa HTML do autor.
 */
function md_safe_url(string $u): ?string
{
    $u = html_entity_decode($u, ENT_QUOTES, 'UTF-8');
    if (preg_match('#^(https?://[^\s"<>]+|mailto:[^\s"<>]+|/?[A-Za-z0-9_./%-]+(\?[^\s"<>]*)?(\#[^\s"<>]*)?)$#', $u) !== 1) {
        return null;
    }
    return htmlspecialchars($u, ENT_QUOTES, 'UTF-8');
}

function md_inline(string $t): string
{
    $t = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/u', function ($m) {
        $url = md_safe_url($m[2]);
        if ($url === null) {
            return $m[1];
        }
        $ext = str_starts_with($url, 'http') ? ' target="_blank" rel="noopener noreferrer"' : '';
        return '<a href="' . $url . '"' . $ext . '>' . $m[1] . '</a>';
    }, $t) ?? $t;
    $t = preg_replace('/\*\*(.+?)\*\*/us', '<strong>$1</strong>', $t) ?? $t;
    $t = preg_replace('/(?<![\*\w])\*(?!\s)(.+?)(?<!\s)\*(?![\*\w])/us', '<em>$1</em>', $t) ?? $t;
    return $t;
}

function md_render(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $out = [];
    $para = [];
    $list = [];
    $listType = 'ul';

    $flush = function () use (&$out, &$para, &$list, &$listType) {
        if ($para) {
            $out[] = '<p>' . md_inline(implode(' ', $para)) . '</p>';
            $para = [];
        }
        if ($list) {
            $out[] = "<$listType>" . implode('', array_map(fn($i) => '<li>' . md_inline($i) . '</li>', $list)) . "</$listType>";
            $list = [];
        }
    };

    foreach (explode("\n", trim($text)) as $line) {
        $s = trim($line);
        if ($s === '') {
            $flush();
        } elseif (preg_match('/^###\s+(.+)$/u', $s, $m)) {
            $flush();
            $out[] = '<h3>' . md_inline($m[1]) . '</h3>';
        } elseif (preg_match('/^##\s+(.+)$/u', $s, $m)) {
            $flush();
            $out[] = '<h2>' . md_inline($m[1]) . '</h2>';
        } elseif (preg_match('/^!\[([^\]]*)\]\(([^)\s]+)\)$/u', $s, $m) && ($url = md_safe_url($m[2])) !== null) {
            $flush();
            $cap = $m[1] !== '' ? '<figcaption>' . $m[1] . '</figcaption>' : '';
            $out[] = '<figure class="art-fig"><img loading="lazy" alt="' . $m[1] . '" src="' . $url . '">' . $cap . '</figure>';
        } elseif (preg_match('/^(?:-|\*)\s+(.+)$/u', $s, $m)) {
            if ($para || ($list && $listType !== 'ul')) {
                $flush();
            }
            $listType = 'ul';
            $list[] = $m[1];
        } elseif (preg_match('/^\d+[.)]\s+(.+)$/u', $s, $m)) {
            if ($para || ($list && $listType !== 'ol')) {
                $flush();
            }
            $listType = 'ol';
            $list[] = $m[1];
        } elseif (preg_match('/^&gt;\s?(.+)$/u', $s, $m)) {
            $flush();
            $out[] = '<blockquote>' . md_inline($m[1]) . '</blockquote>';
        } else {
            if ($list) {
                $flush();
            }
            $para[] = $s;
        }
    }
    $flush();
    return implode("\n", $out);
}
