<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_layout.php';

$user = require_login();

/** Apaga a capa enviada pelo admin quando nenhum outro artigo a usa. */
function delete_cover_file(string $path, int $exceptId): void
{
    if (!str_starts_with($path, 'uploads/blog/') || !valid_cover_path($path)) {
        return;
    }
    $st = db()->prepare('SELECT COUNT(*) FROM posts WHERE cover = ? AND id <> ?');
    $st->execute([$path, $exceptId]);
    $file = realpath(ROOT_DIR . '/' . $path);
    $base = realpath(ROOT_DIR . '/uploads/blog');
    if ((int) $st->fetchColumn() === 0 && $file && $base && str_starts_with($file, $base . DIRECTORY_SEPARATOR)) {
        @unlink($file);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $act = (string) ($_POST['action'] ?? '');
    $st = db()->prepare('SELECT * FROM posts WHERE id = ?');
    $st->execute([$id]);
    $p = $st->fetch();

    if (!$p) {
        flash('Artigo não encontrado.', 'err');
    } elseif ($act === 'delete') {
        db()->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
        delete_cover_file((string) $p['cover'], $id);
        flash('Artigo eliminado.');
    } elseif ($act === 'toggle') {
        if ($p['status'] === 'published') {
            db()->prepare("UPDATE posts SET status = 'draft' WHERE id = ?")->execute([$id]);
            flash('O artigo passou a rascunho e já não aparece no site.');
        } elseif ($p['cover'] === '' || trim((string) $p['content']) === '') {
            flash('Para publicar, o artigo precisa de capa e de texto. Abra-o e complete-o.', 'err');
        } else {
            db()->prepare("UPDATE posts SET status = 'published', published_at = COALESCE(published_at, NOW()) WHERE id = ?")->execute([$id]);
            flash('Artigo publicado.');
        }
    }
    redirect('index.php' . (!empty($_POST['back']) ? '?' . preg_replace('/[^A-Za-z0-9=&_%.-]/', '', (string) $_POST['back']) : ''));
}

$q = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$page = max(1, (int) ($_GET['p'] ?? 1));
$per = 12;

$where = ['1=1'];
$args = [];
if ($q !== '') {
    $where[] = '(title LIKE ? OR category LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    array_push($args, $like, $like);
}
if ($status === 'published') {
    $where[] = "status = 'published' AND published_at <= NOW()";
} elseif ($status === 'draft') {
    $where[] = "status = 'draft'";
} elseif ($status === 'scheduled') {
    $where[] = "status = 'published' AND published_at > NOW()";
}
$W = implode(' AND ', $where);

$st = db()->prepare("SELECT COUNT(*) FROM posts WHERE $W");
$st->execute($args);
$total = (int) $st->fetchColumn();
$pages = max(1, (int) ceil($total / $per));
$page = min($page, $pages);

$st = db()->prepare("SELECT id, slug, title, category, cover, status, published_at, updated_at FROM posts WHERE $W ORDER BY COALESCE(published_at, updated_at) DESC, id DESC LIMIT $per OFFSET " . (($page - 1) * $per));
$st->execute($args);
$rows = $st->fetchAll();

$counts = db()->query("SELECT COUNT(*) total, SUM(status='published' AND published_at <= NOW()) live, SUM(status='draft') draft, SUM(status='published' AND published_at > NOW()) sched FROM posts")->fetch();

function state_of(array $r): array
{
    if ($r['status'] === 'draft') {
        return ['Rascunho', 'draft'];
    }
    return strtotime((string) $r['published_at']) > time() ? ['Agendado', 'sched'] : ['Publicado', 'live'];
}

$qs = http_build_query(array_filter(['q' => $q, 'status' => $status]));
admin_header('Artigos', $user, 'index.php');
?>
<div class="head">
  <div><h1>Artigos</h1><p class="muted">Crie, edite e publique os artigos do blog.</p></div>
  <a class="btn" href="post.php">+ Novo artigo</a>
</div>

<div class="stats">
  <a href="index.php"><b><?= (int) $counts['total'] ?></b><span>Total</span></a>
  <a href="index.php?status=published"><b><?= (int) $counts['live'] ?></b><span>Publicados</span></a>
  <a href="index.php?status=scheduled"><b><?= (int) $counts['sched'] ?></b><span>Agendados</span></a>
  <a href="index.php?status=draft"><b><?= (int) $counts['draft'] ?></b><span>Rascunhos</span></a>
</div>

<form class="search" method="get">
  <input type="search" name="q" placeholder="Pesquisar por título ou categoria…" value="<?= e($q) ?>">
  <select name="status" aria-label="Estado">
    <option value="">Todos os estados</option>
    <?php foreach (['published' => 'Publicados', 'scheduled' => 'Agendados', 'draft' => 'Rascunhos'] as $k => $l): ?>
      <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $l ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn ghost" type="submit">Filtrar</button>
</form>

<?php if (!$rows): ?>
  <div class="empty"><p>Nenhum artigo encontrado.</p><a class="btn" href="post.php">Escrever o primeiro artigo</a></div>
<?php else: ?>
<div class="table">
  <?php foreach ($rows as $r): [$label, $cls] = state_of($r); ?>
  <article class="row">
    <div class="thumb"><?php if ($r['cover']): ?><img src="../<?= e($r['cover']) ?>" alt="" loading="lazy"><?php endif; ?></div>
    <div class="info">
      <a class="title" href="post.php?id=<?= (int) $r['id'] ?>"><?= e($r['title']) ?></a>
      <div class="meta"><span class="badge <?= $cls ?>"><?= $label ?></span><span><?= e($r['category']) ?></span>
        <span><?= $r['published_at'] ? e(data_pt($r['published_at'], true)) : 'Sem data' ?></span></div>
    </div>
    <div class="actions">
      <a class="mini" href="post.php?id=<?= (int) $r['id'] ?>">Editar</a>
      <a class="mini" href="../artigo.php?slug=<?= e(rawurlencode($r['slug'])) ?>&preview=1" target="_blank" rel="noopener">Ver</a>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="back" value="<?= e($qs) ?>">
        <button class="mini" name="action" value="toggle" type="submit"><?= $r['status'] === 'published' ? 'Despublicar' : 'Publicar' ?></button>
        <button class="mini danger" name="action" value="delete" type="submit" data-confirm="Eliminar este artigo? Esta acção não pode ser desfeita.">Eliminar</button>
      </form>
    </div>
  </article>
  <?php endforeach; ?>
</div>

<?php if ($pages > 1): ?>
<nav class="pager" aria-label="Páginas">
  <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a class="<?= $i === $page ? 'on' : '' ?>" href="index.php?<?= e(http_build_query(array_filter(['q' => $q, 'status' => $status, 'p' => $i > 1 ? $i : null]))) ?>"><?= $i ?></a>
  <?php endfor; ?>
</nav>
<?php endif; endif; ?>
<?php admin_footer();
