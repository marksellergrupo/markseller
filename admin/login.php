<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_layout.php';

security_headers();
start_session();

// Sem tabelas ou sem administradores: vai para a instalação.
try {
    $users = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
} catch (Throwable $e) {
    redirect('setup.php');
}
if ($users === 0) {
    redirect('setup.php');
}
if (current_user()) {
    redirect('index.php');
}

const DUMMY_HASH = '$2y$10$eq.pUcfbe3i6XrMiD8lzc.MJdl87.iZ1MbNqxB.G1XzsCsP2fCW6K';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0'), 0, 45);
    db()->prepare('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)')->execute();
    $st = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)');
    $st->execute([$ip]);

    if ((int) $st->fetchColumn() >= 5) {
        $error = 'Demasiadas tentativas. Aguarde 15 minutos e tente de novo.';
    } else {
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $pass = (string) ($_POST['password'] ?? '');
        $st = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
        $st->execute([$email]);
        $u = $st->fetch();
        $ok = password_verify($pass, $u['password_hash'] ?? DUMMY_HASH) && $u;
        if ($ok) {
            db()->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$ip]);
            session_regenerate_id(true);
            $_SESSION['uid'] = (int) $u['id'];
            db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$u['id']]);
            if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($pass, PASSWORD_DEFAULT), $u['id']]);
            }
            redirect('index.php');
        }
        db()->prepare('INSERT INTO login_attempts (ip) VALUES (?)')->execute([$ip]);
        usleep(400000);
        $error = 'Email ou palavra-passe incorrectos.';
    }
}

admin_header('Entrar');
?>
<section class="auth">
  <img class="auth-logo" src="../img/mark-seller.webp" alt="Mark Seller">
  <h1>Entrar no painel</h1>
  <p class="muted">Gestão dos artigos do blog.</p>
  <?php if ($error): ?><div class="notice err" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="form" autocomplete="on">
    <?= csrf_field() ?>
    <label>Email<input type="email" name="email" required autofocus autocomplete="username" value="<?= e((string) ($_POST['email'] ?? '')) ?>"></label>
    <label>Palavra-passe<input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn" type="submit">Entrar</button>
  </form>
  <p class="muted small"><a href="../index.html">← Voltar ao site</a></p>
</section>
<?php admin_footer();
