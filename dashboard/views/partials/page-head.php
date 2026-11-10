<?php
/**
 * Breadcrumbs, title, description and chips.
 * @var string $title
 * @var string $description
 * @var list<array{string, ?string}> $crumbs label, href (null = not a link)
 * @var list<array{string, string}> $chips  text, code
 */
$crumbs ??= [];
$chips ??= [];
$description ??= '';
?>
<?php if ($crumbs): ?>
      <nav class="crumbs" aria-label="Breadcrumb">
<?php foreach ($crumbs as $i => [$label, $href]): ?>
<?php if ($i > 0): ?>
        <span aria-hidden="true">/</span>
<?php endif ?>
<?php if ($href !== null): ?>
        <a href="<?= h($href) ?>"><?= h($label) ?></a>
<?php else: ?>
        <span<?= $i === count($crumbs) - 1 ? ' aria-current="page"' : '' ?>><?= h($label) ?></span>
<?php endif ?>
<?php endforeach ?>
      </nav>
<?php endif ?>
      <header class="page-head">
        <div>
          <h1><?= h($title) ?></h1>
<?php if ($description !== ''): ?>
          <p><?= h($description) ?></p>
<?php endif ?>
        </div>
<?php if ($chips): ?>
        <div class="page-meta">
<?php foreach ($chips as [$text, $code]): ?>
          <?= chip($text, $code) ?>
<?php endforeach ?>
        </div>
<?php endif ?>
      </header>
