<?php
declare(strict_types=1);

/**
 * Cria as tabelas (database/schema.sql) e importa os artigos de exemplo (database/seed/*.md).
 */
function run_schema(PDO $pdo): void
{
    $sql = (string) file_get_contents(ROOT_DIR . '/database/schema.sql');
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        $pdo->exec($stmt);
    }
}

function import_seed_posts(PDO $pdo): int
{
    $n = 0;
    foreach (glob(ROOT_DIR . '/database/seed/*.md') ?: [] as $file) {
        $raw = (string) file_get_contents($file);
        if (!preg_match('/\A---\R(.*?)\R---\R(.*)\z/s', $raw, $m)) {
            continue;
        }
        $meta = [];
        foreach (preg_split('/\R/', $m[1]) as $line) {
            if (str_contains($line, ':')) {
                [$k, $v] = explode(':', $line, 2);
                $meta[trim($k)] = trim($v);
            }
        }
        $slug = basename($file, '.md');
        $exists = $pdo->prepare('SELECT 1 FROM posts WHERE slug = ?');
        $exists->execute([$slug]);
        if ($exists->fetchColumn()) {
            continue;
        }
        $content = trim($m[2]);
        $st = $pdo->prepare('INSERT INTO posts (slug, title, excerpt, content, category, cover, read_minutes, status, published_at)
                             VALUES (?, ?, ?, ?, ?, ?, ?, \'published\', ?)');
        $st->execute([
            $slug,
            $meta['title'] ?? $slug,
            $meta['excerpt'] ?? '',
            $content,
            $meta['category'] ?? 'Geral',
            $meta['cover'] ?? '',
            reading_minutes($content),
            ($meta['date'] ?? date('Y-m-d')) . ' 09:00:00',
        ]);
        $n++;
    }
    return $n;
}
