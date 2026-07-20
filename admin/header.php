<?php
$page_title = $page_title ?? 'Admin';
$layout_type = $layout_type ?? 'main';
?>
<!DOCTYPE html>
<html lang="de">
<head>
<link rel="icon" href="../grafik/F%C3%BCreinander%20Freiburg.svg" type="image/svg+xml">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="admin.css">
<?php if (isset($extra_head)) echo $extra_head; ?>
</head>
<body<?= $layout_type === 'login' ? ' class="login-wrap"' : '' ?>>
<?php if ($layout_type === 'main'): ?>
<div class="admin-layout">
<?php require __DIR__ . '/nav.php'; ?>
<div class="admin-main">
<?php endif; ?>
