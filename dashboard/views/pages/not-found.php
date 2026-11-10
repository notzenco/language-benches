<?php
/**
 * @var string $title
 * @var string $path
 * @var list<array{string, ?string}> $crumbs
 */
?>
<?= render('partials/page-head', [
    'title'       => $title,
    'description' => "Nothing here at $path.",
    'crumbs'      => $crumbs,
]) ?>
      <p class="empty section">Pick a test or language from the sidebar, or go back to the <a href="/">overview</a>.</p>
