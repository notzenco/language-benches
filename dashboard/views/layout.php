<?php
/**
 * Page shell: sidebar, main column, tooltip.
 * @var array $page     from a page_* function
 * @var array $results  load_results()
 * @var string $current sidebar href to mark as current
 * @var string $content rendered page body
 */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($page['title']) ?> · language-benches</title>
<link rel="stylesheet" href="<?= h(asset_url('app.css')) ?>">
<script>document.documentElement.classList.add('js');</script>
<script src="<?= h(asset_url('app.js')) ?>" defer></script>
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<div class="app">
<?= render('partials/sidebar', ['results' => $results, 'current' => $current]) ?>
  <main class="main" id="main">
    <div class="content">
<?= $content ?>
    </div>
  </main>
</div>
<div class="tip" id="tip" role="tooltip" hidden></div>
</body>
</html>
