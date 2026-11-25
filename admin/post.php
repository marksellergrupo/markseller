<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_layout.php';

$user = require_login();
$id = (int) ($_GET['id'] ?? 0);

$post = [
    'id' => 0, 'title' => '', 'slug' => '', 'excerpt' => '', 'content' => '', 'category' => '', 'cover' => '',
    'author' => 'Equipa Mark Seller', 'status' => 'draft', 'published_at' => null,
];
if ($id) {
    $st = db()->prepare('SELECT * FROM posts WHERE id = ?');
    $st->execute([$id]);
    $found = $st->fetch();
    if (!$found) {
        flash('Artigo não encontrado.', 'err');
        redirect('index.php');
    }
    $post = $found;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $in = [
        'title' => trim((string) ($_POST['title'] ?? '')),
        'slug' => trim((string) ($_POST['slug'] ?? '')),
        'category' => trim((string) ($_POST['category'] ?? '')),
        'excerpt' => trim((string) ($_POST['excerpt'] ?? '')),
        'content' => trim(str_replace("\r\n", "\n", (string) ($_POST['content'] ?? ''))),
        'author' => trim((string) ($_POST['author'] ?? '')) ?: 'Equipa Mark Seller',
        'status' => ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft',
        'published_at' => trim((string) ($_POST['published_at'] ?? '')),
    ];
    $cover = (string) $post['cover'];
    $oldCover = $cover;

    if ($in['title'] === '' || mb_strlen($in['title']) > 200) {
        $errors[] = 'Indique um título (até 200 caracteres).';
    }
    if ($in['category'] === '' || mb_strlen($in['category']) > 80) {
        $errors[] = 'Indique uma categoria (até 80 caracteres).';
    }
    if (mb_strlen($in['excerpt']) > 400) {
        $errors[] = 'O resumo pode ter no máximo 400 caracteres.';
    }
    if (mb_strlen($in['author']) > 120) {
        $errors[] = 'O nome do autor é demasiado longo.';
    }

    // Capa: ficheiro novo, caminho existente do site, ou remover.
    $uploaded = null;
    if (!empty($_FILES['cover_file']['name'])) {
        try {
            $uploaded = save_upload($_FILES['cover_file']);
            $cover = $uploaded;
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    } elseif (!empty($_POST['cover_path'])) {
        $cp = trim((string) $_POST['cover_path']);
        if (valid_cover_path($cp) && is_file(ROOT_DIR . '/' . $cp)) {
            $cover = $cp;
        } else {
            $errors[] = 'Caminho de capa inválido.';
        }
    } elseif (!empty($_POST['cover_remove'])) {
        $cover = '';
    }

    $when = null;
    if ($in['published_at'] !== '') {
        $d = DateTime::createFromFormat('Y-m-d\TH:i', $in['published_at']);
        if (!$d || $d->format('Y-m-d\TH:i') !== $in['published_at']) {
            $errors[] = 'Data de publicação inválida.';
        } else {
            $when = $d->format('Y-m-d H:i:00');
        }
    }

    if ($in['status'] === 'published') {
        if ($in['content'] === '') {
            $errors[] = 'Escreva o texto do artigo antes de publicar.';
        }
        if ($in['excerpt'] === '') {
            $errors[] = 'Escreva um resumo antes de publicar (aparece na listagem).';
        }
        if ($cover === '') {
            $errors[] = 'Escolha uma imagem de capa antes de publicar.';
        }
        $when = $when ?? date('Y-m-d H:i:00');
    }

    if (!$errors) {
        $slug = slugify($in['slug'] !== '' ? $in['slug'] : $in['title']);
        $slug = unique_slug($slug, $post['id'] ?: null);
        $mins = reading_minutes($in['content']);
        if ($post['id']) {
            db()->prepare('UPDATE posts SET slug=?, title=?, excerpt=?, content=?, category=?, cover=?, author=?, read_minutes=?, status=?, published_at=? WHERE id=?')
                ->execute([$slug, $in['title'], $in['excerpt'], $in['content'], $in['category'], $cover, $in['author'], $mins, $in['status'], $when, $post['id']]);
            $newId = (int) $post['id'];
        } else {
            db()->prepare('INSERT INTO posts (slug, title, excerpt, content, category, cover, author, read_minutes, status, published_at) VALUES (?,?,?,?,?,?,?,?,?,?)')
                ->execute([$slug, $in['title'], $in['excerpt'], $in['content'], $in['category'], $cover, $in['author'], $mins, $in['status'], $when]);
            $newId = (int) db()->lastInsertId();
        }
        // Capa antiga enviada pelo admin e já não usada: remover o ficheiro.
        if ($oldCover !== '' && $oldCover !== $cover && str_starts_with($oldCover, 'uploads/blog/') && valid_cover_path($oldCover)) {
            $st = db()->prepare('SELECT COUNT(*) FROM posts WHERE cover = ?');
            $st->execute([$oldCover]);
            $file = realpath(ROOT_DIR . '/' . $oldCover);
            $base = realpath(ROOT_DIR . '/uploads/blog');
            if ((int) $st->fetchColumn() === 0 && $file && $base && str_starts_with($file, $base . DIRECTORY_SEPARATOR)) {
                @unlink($file);
            }
        }
        flash($in['status'] === 'published' ? 'Artigo guardado e publicado.' : 'Rascunho guardado.');
        redirect('post.php?id=' . $newId);
    }

    // Em caso de erro, não deixa ficheiros órfãos e volta a mostrar o que o utilizador escreveu.
    if ($uploaded) {
        @unlink(ROOT_DIR . '/' . $uploaded);
        $errors[] = 'A imagem de capa não foi guardada. Seleccione-a de novo depois de corrigir.';
        $cover = $oldCover;
    }
    $post = array_merge($post, $in, ['cover' => $cover]);
    $post['published_at'] = $when ?? null;
}

// Categorias já usadas (sugestões) e imagens do site disponíveis como capa.
$cats = db()->query('SELECT DISTINCT category FROM posts ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);
$imgs = [];
foreach (glob(ROOT_DIR . '/img/*.webp') ?: [] as $f) {
    $imgs[] = 'img/' . basename($f);
}
$when = $post['published_at'] ? date('Y-m-d\TH:i', strtotime((string) $post['published_at'])) : '';

admin_header($post['id'] ? 'Editar artigo' : 'Novo artigo', $user, $post['id'] ? '' : 'post.php');
?>
<div class="head">
  <div><h1><?= $post['id'] ? 'Editar artigo' : 'Novo artigo' ?></h1>
    <p class="muted"><?= $post['id'] ? 'Última actualização: ' . e(data_pt($post['updated_at'] ?? null)) : 'Preencha os campos e guarde como rascunho ou publique.' ?></p></div>
  <div class="head-actions">
    <?php if ($post['id']): ?><a class="btn ghost" href="../artigo.php?slug=<?= e(rawurlencode((string) $post['slug'])) ?>&preview=1" target="_blank" rel="noopener">Pré-visualizar no site ↗</a><?php endif; ?>
    <a class="btn ghost" href="index.php">← Voltar</a>
  </div>
</div>

<?php if ($errors): ?>
  <div class="notice err" role="alert"><strong>Corrija antes de guardar:</strong><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="editor" id="post-form" data-upload-url="upload.php" data-preview-url="preview.php" data-new="<?= $post['id'] ? '0' : '1' ?>">
  <?= csrf_field() ?>
  <div class="col-main">
    <label>Título
      <input type="text" name="title" id="f-title" maxlength="200" required value="<?= e((string) $post['title']) ?>" placeholder="Ex.: Como definir o posicionamento da sua marca">
    </label>
    <label>Endereço (slug)
      <input type="text" name="slug" id="f-slug" maxlength="120" value="<?= e((string) $post['slug']) ?>" placeholder="gerado automaticamente a partir do título">
      <small class="muted">Aparece no link do artigo. Só letras minúsculas, números e hífens.</small>
    </label>
    <label>Resumo <span class="count" id="ex-count">0/400</span>
      <textarea name="excerpt" id="f-excerpt" rows="3" maxlength="400" placeholder="Duas frases que aparecem na listagem do blog."><?= e((string) $post['excerpt']) ?></textarea>
    </label>

    <div class="field">
      <div class="tabs" role="tablist"><button type="button" class="tab on" data-tab="write" role="tab">Escrever</button><button type="button" class="tab" data-tab="preview" role="tab">Pré-visualizar</button></div>
      <div class="toolbar" id="toolbar" aria-label="Formatação">
        <button type="button" data-md="h2" title="Título de secção">H2</button>
        <button type="button" data-md="h3" title="Subtítulo">H3</button>
        <button type="button" data-md="bold" title="Negrito"><b>B</b></button>
        <button type="button" data-md="italic" title="Itálico"><i>I</i></button>
        <button type="button" data-md="ul" title="Lista">• Lista</button>
        <button type="button" data-md="ol" title="Lista numerada">1. Lista</button>
        <button type="button" data-md="quote" title="Citação em destaque">“ Citação</button>
        <button type="button" data-md="link" title="Ligação">Ligação</button>
        <button type="button" id="btn-img" title="Inserir imagem no texto">Imagem</button>
        <input type="file" id="img-file" accept="image/jpeg,image/png,image/webp" hidden>
      </div>
      <textarea name="content" id="f-content" rows="22" placeholder="Escreva o artigo. Use ## para títulos de secção, - para listas, **negrito** e *itálico*."><?= e((string) $post['content']) ?></textarea>
      <div class="preview art-body" id="preview" hidden></div>
      <small class="muted" id="read-info"></small>
    </div>
  </div>

  <aside class="col-side">
    <div class="card">
      <h3>Publicação</h3>
      <label>Estado
        <select name="status" id="f-status">
          <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Rascunho</option>
          <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Publicado</option>
        </select>
      </label>
      <label>Data de publicação
        <input type="datetime-local" name="published_at" value="<?= e($when) ?>">
        <small class="muted">Vazio = agora. Uma data futura agenda o artigo.</small>
      </label>
      <button class="btn block" type="submit">Guardar</button>
    </div>

    <div class="card">
      <h3>Organização</h3>
      <label>Categoria
        <input type="text" name="category" list="cats" maxlength="80" required value="<?= e((string) $post['category']) ?>" placeholder="Ex.: Branding">
        <datalist id="cats"><?php foreach ($cats as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
      </label>
      <label>Autor
        <input type="text" name="author" maxlength="120" value="<?= e((string) $post['author']) ?>">
      </label>
    </div>

    <div class="card">
      <h3>Imagem de capa</h3>
      <div class="cover-prev" id="cover-prev"><?php if ($post['cover']): ?><img src="../<?= e((string) $post['cover']) ?>" alt=""><?php else: ?><span class="muted">Sem capa</span><?php endif; ?></div>
      <label>Enviar nova imagem (JPG, PNG ou WebP, até 5 MB)
        <input type="file" name="cover_file" id="cover-file" accept="image/jpeg,image/png,image/webp">
      </label>
      <label>…ou usar uma imagem do site
        <select name="cover_path" id="cover-path">
          <option value="">— manter a actual —</option>
          <?php foreach ($imgs as $im): ?><option value="<?= e($im) ?>"><?= e(basename($im)) ?></option><?php endforeach; ?>
        </select>
      </label>
      <?php if ($post['cover']): ?><label class="check"><input type="checkbox" name="cover_remove" value="1"> Remover capa</label><?php endif; ?>
    </div>
  </aside>
</form>
<?php admin_footer();
