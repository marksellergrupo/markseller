<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/seed.php';
require __DIR__ . '/_layout.php';

security_headers();
start_session();

$key = (string) cfg('setup_key', '');
$keyOk = $key !== '' && $key !== 'MUDE-ESTA-CHAVE' && strlen($key) >= 12;

// Já instalado? (existe pelo menos um administrador)
$installed = false;
try {
    $installed = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
} catch (Throwable $e) {
    $installed = false;
}
if ($installed) {
    flash('O painel já está instalado. Entre com a sua conta.');
    redirect('login.php');
}

$error = '';
$done = false;

if ($keyOk && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $pass = (string) ($_POST['password'] ?? '');
    $pass2 = (string) ($_POST['password2'] ?? '');
    $sent = (string) ($_POST['setup_key'] ?? '');

    if (!hash_equals($key, $sent)) {
        $error = 'Chave de instalação incorrecta.';
    } elseif ($name === '' || mb_strlen($name) > 120) {
        $error = 'Indique o seu nome.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Indique um email válido.';
    } elseif (mb_strlen($pass) < 10) {
        $error = 'A palavra-passe deve ter pelo menos 10 caracteres.';
    } elseif ($pass !== $pass2) {
        $error = 'As palavras-passe não coincidem.';
    } else {
        try {
            $dbName = (string) cfg('db_name');
            if (!preg_match('/^[A-Za-z0-9_]+$/', $dbName)) {
                throw new RuntimeException('Nome da base de dados inválido em config/config.php.');
            }
            // Cria a base de dados se ainda não existir.
            $root = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', cfg('db_host'), (int) cfg('db_port', 3306)), (string) cfg('db_user'), (string) cfg('db_pass'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $root->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            $pdo = db();
            run_schema($pdo);
            $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)')
                ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
            $imported = !empty($_POST['seed']) ? import_seed_posts($pdo) : 0;
            flash('Instalação concluída. ' . ($imported ? $imported . ' artigos de exemplo importados. ' : '') . 'Entre com a sua conta.');
            redirect('login.php');
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            error_log('setup: ' . $e->getMessage());
            $error = 'Não foi possível ligar à base de dados. Confirme que o MySQL está a correr e que config/config.php tem os dados certos.';
        }
    }
}

admin_header('Instalação');
?>
<section class="auth">
  <img class="auth-logo" src="../img/mark-seller.webp" alt="Mark Seller">
  <h1>Instalar o painel</h1>
  <?php if (!$keyOk): ?>
    <div class="notice err">Defina primeiro uma <strong>setup_key</strong> longa (12+ caracteres) em <code>config/config.php</code>.</div>
  <?php else: ?>
    <p class="muted">Cria a base de dados, as tabelas e o primeiro administrador. A chave de instalação está em <code>config/config.php</code> (<code>setup_key</code>).</p>
    <?php if ($error): ?><div class="notice err" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form" autocomplete="off">
      <?= csrf_field() ?>
      <label>Chave de instalação<input type="password" name="setup_key" required></label>
      <label>Nome<input type="text" name="name" required maxlength="120" value="<?= e((string) ($_POST['name'] ?? '')) ?>"></label>
      <label>Email<input type="email" name="email" required value="<?= e((string) ($_POST['email'] ?? '')) ?>"></label>
      <label>Palavra-passe (mínimo 10 caracteres)<input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
      <label>Repetir palavra-passe<input type="password" name="password2" required minlength="10" autocomplete="new-password"></label>
      <label class="check"><input type="checkbox" name="seed" value="1" checked> Importar os 4 artigos de exemplo</label>
      <button class="btn" type="submit">Instalar</button>
    </form>
  <?php endif; ?>
</section>
<?php admin_footer();
