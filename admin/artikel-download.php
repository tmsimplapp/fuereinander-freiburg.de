<?php
require_once __DIR__ . '/auth.php';

$db = admin_db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id < 1) {
    die('Ungültige Artikel-ID.');
}

$stmt = $db->prepare('SELECT * FROM artikel WHERE id = ?');
$stmt->execute([$id]);
$art = $stmt->fetch();

if (!$art) {
    die('Artikel nicht gefunden.');
}

// Markdown-Format generieren (inkl. YAML Frontmatter für Metadaten)
$markdown = "---\n";
$markdown .= "title: " . $art['titel'] . "\n";
$markdown .= "slug: " . $art['slug'] . "\n";
$markdown .= "category: " . $art['kategorie'] . "\n";
$markdown .= "tags: " . ($art['tags'] ?? '') . "\n";
$markdown .= "teaser: " . str_replace(["\r", "\n"], " ", $art['teaser']) . "\n";
$markdown .= "created_at: " . $art['created_at'] . "\n";
$markdown .= "---\n\n";
$markdown .= $art['inhalt'];

// Header für den Datei-Download senden
$filename = preg_replace('/[^a-zA-Z0-9\-]/', '_', $art['slug']) . '.md';

header('Content-Type: text/markdown; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($markdown));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $markdown;
exit;
