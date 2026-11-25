<?php
declare(strict_types=1);

/**
 * Arranque comum: configuração, base de dados, sessão, CSRF e utilitários.
 */

define('ROOT_DIR', dirname(__DIR__));

$cfgFile = getenv('MS_CONFIG') ?: ROOT_DIR . '/config/config.php';
if (!is_file($cfgFile)) {
    http_response_code(500);
    exit('Configuração em falta: copie config/config.sample.php para config/config.php e preencha os dados.');
}
$GLOBALS['CONFIG'] = require $cfgFile;
date_default_timezone_set((string) cfg('timezone', 'Africa/Luanda'));

function cfg(string $key, $default = null)
{
    return $GLOBALS['CONFIG'][$key] ?? $default;
}

/** Ligação PDO (uma só por pedido). */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', cfg('db_host'), (int) cfg('db_port', 3306), cfg('db_name'));
        $pdo = new PDO($dsn, (string) cfg('db_user'), (string) cfg('db_pass'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '" . date('P') . "'");
    }
    return $pdo;
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/** Sessão segura, usada só pela área de administração. */
function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('ms_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    start_session();
    $sent = (string) ($_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    if ($sent === '' || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        exit('Pedido inválido ou sessão expirada. Volte atrás, recarregue a página e tente de novo.');
    }
}

function current_user(): ?array
{
    start_session();
    if (empty($_SESSION['uid'])) {
        return null;
    }
    static $user = false;
    if ($user === false) {
        $st = db()->prepare('SELECT id, name, email FROM users WHERE id = ?');
        $st->execute([(int) $_SESSION['uid']]);
        $user = $st->fetch() ?: null;
    }
    return $user;
}

function require_login(): array
{
    security_headers();
    $u = current_user();
    if (!$u) {
        header('Location: login.php');
        exit;
    }
    return $u;
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function flash(?string $msg = null, string $type = 'ok'): ?array
{
    start_session();
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/** Texto -> endereço (slug): "Olá, Mundo!" -> "ola-mundo". */
function slugify(string $text): string
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    $map = ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n'];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return substr($text !== '' ? $text : 'artigo', 0, 120);
}

function unique_slug(string $slug, ?int $ignoreId = null): string
{
    $base = $slug;
    $n = 2;
    while (true) {
        $st = db()->prepare('SELECT id FROM posts WHERE slug = ?' . ($ignoreId ? ' AND id <> ?' : ''));
        $st->execute($ignoreId ? [$slug, $ignoreId] : [$slug]);
        if (!$st->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $n++;
    }
}

function reading_minutes(string $content): int
{
    $words = preg_match_all('/\p{L}+/u', $content);
    return max(1, min(60, (int) ceil(($words ?: 0) / 200)));
}

const MESES_PT = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

function data_pt(?string $dt, bool $short = false): string
{
    if (!$dt) {
        return '';
    }
    $t = strtotime($dt);
    $m = MESES_PT[(int) date('n', $t) - 1];
    return $short
        ? date('j', $t) . ' ' . mb_substr($m, 0, 3) . ' ' . date('Y', $t)
        : date('j', $t) . ' de ' . $m . ' de ' . date('Y', $t);
}

/**
 * Guarda uma imagem enviada em uploads/blog/AAAA/ e devolve o caminho relativo à raiz do site.
 * Valida tamanho, tipo real do ficheiro e dimensões; o nome é aleatório.
 */
function save_upload(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Não foi possível receber o ficheiro. Tente de novo.');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('A imagem é demasiado grande (máximo 5 MB).');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if ($ext === null || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Formato não permitido. Use JPG, PNG ou WebP.');
    }
    $rel = 'uploads/blog/' . date('Y');
    $dir = ROOT_DIR . '/' . $rel;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Não foi possível criar a pasta de imagens.');
    }
    $name = bin2hex(random_bytes(10)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('Não foi possível guardar a imagem.');
    }
    return $rel . '/' . $name;
}

/** Só aceita caminhos de imagem do próprio site (img/ ou uploads/blog/). */
function valid_cover_path(string $p): bool
{
    return (bool) preg_match('#^(img|uploads/blog(/\d{4})?)/[A-Za-z0-9._-]+\.(jpg|jpeg|png|webp)$#', $p);
}
