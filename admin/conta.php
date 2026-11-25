<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_layout.php';

$user = require_login();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $cur = (string) ($_POST['current'] ?? '');
    $new = (string) ($_POST['new'] ?? '');
    $new2 = (string) ($_POST['new2'] ?? '');

    $st = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $st->execute([$user['id']]);
    $hash = (string) $st->fetchColumn();

    if (!password_verify($cur, $hash)) {
        $errors[] = 'A palavra-passe actual está incorrecta.';
    }
    if (mb_strlen($new) < 10) {
        $errors[] = 'A nova palavra-passe deve ter pelo menos 10 caracteres.';
    }
    if ($new !== $new2) {
        $errors[] = 'As novas palavras-passe não coincidem.';
    }
    if (!$errors) {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        session_regenerate_id(true);
        flash('Palavra-passe alterada.');
        redirect('conta.php');
    }
}

admin_header('Conta', $user, 'conta.php');
?>
<div class="head"><div><h1>Conta</h1><p class="muted"><?= e($user['name']) ?> · <?= e($user['email']) ?></p></div></div>
<?php if ($errors): ?>
  <div class="notice err" role="alert"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<form method="post" class="form card narrow" autocomplete="off">
  <h3>Alterar palavra-passe</h3>
  <?= csrf_field() ?>
  <label>Palavra-passe actual<input type="password" name="current" required autocomplete="current-password"></label>
  <label>Nova palavra-passe (mínimo 10 caracteres)<input type="password" name="new" required minlength="10" autocomplete="new-password"></label>
  <label>Repetir nova palavra-passe<input type="password" name="new2" required minlength="10" autocomplete="new-password"></label>
  <button class="btn" type="submit">Guardar</button>
</form>
<?php admin_footer();
