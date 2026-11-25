<?php
declare(strict_types=1);

function admin_header(string $title, ?array $user = null, string $active = ''): void
{
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    $nav = '';
    if ($user) {
        $items = ['index.php' => 'Artigos', 'post.php' => 'Novo artigo', 'conta.php' => 'Conta'];
        foreach ($items as $href => $label) {
            $nav .= '<a href="' . $href . '"' . ($active === $href ? ' class="on"' : '') . '>' . e($label) . '</a>';
        }
    }
    ?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> — Admin Mark Seller</title>
<link rel="icon" href="../img/mark-seller.webp">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600&display=swap">
<link rel="stylesheet" href="../css/admin.css">
</head>
<body class="<?= $user ? 'in' : 'out' ?>">
<?php if ($user): ?>
<header class="top">
  <a class="brand" href="index.php"><img src="../img/mark-seller.webp" alt="Mark Seller"><span>Admin</span></a>
  <nav><?= $nav ?></nav>
  <div class="who">
    <a class="ghost" href="../blog.php" target="_blank" rel="noopener">Ver blog ↗</a>
    <form method="post" action="logout.php"><?= csrf_field() ?><button class="ghost" type="submit">Sair (<?= e($user['name']) ?>)</button></form>
  </div>
</header>
<?php endif; ?>
<main class="wrap">
<?php
    $f = flash();
    if ($f) {
        echo '<div class="notice ' . e($f['type']) . '" role="status">' . e($f['msg']) . '</div>';
    }
}

function admin_footer(): void
{
    ?>
</main>
<script src="../js/admin.js"></script>
</body>
</html>
<?php
}
