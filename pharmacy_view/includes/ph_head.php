<?php
/**
 * pharmacy_view/includes/ph_head.php
 * Drop this at the top of every ERP page with:
 *   $pageTitle = 'Page Name';
 *   require_once 'includes/ph_head.php';
 */
if (!isset($pageTitle)) $pageTitle = 'GM Pharmacy';

// Calculate API base URL dynamically
$docRoot     = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$projectRoot = str_replace('\\', '/', dirname(dirname(__DIR__)));
$baseUrl     = str_ireplace($docRoot, '', $projectRoot);
$apiBase     = rtrim($baseUrl, '/') . '/api/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> — GM Pharmacy ERP</title>
<!-- Bootstrap 5 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Inter Font -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<!-- Custom ERP CSS -->
<link rel="stylesheet" href="assets/css/pharmacy.css">
<style>
  /* Critical inline CSS — prevents black screen before pharmacy.css loads */
  :root {
    --ph-primary: #0FA4AF;
    --ph-sidebar-bg: #0A0F1E;
    --ph-bg: #F0F4F8;
    --ph-surface: #FFFFFF;
    --ph-text: #0F172A;
    --ph-border: #E2E8F0;
    --ph-muted: #64748B;
    --ph-sidebar-w: 250px;
    --ph-navbar-h: 64px;
  }
  html, body { background: #F0F4F8 !important; }
</style>
<script>
  // Global API Base for JavaScript calls
  const API_BASE = '<?= htmlspecialchars($apiBase) ?>';
</script>
</head>
<body>
<?php
