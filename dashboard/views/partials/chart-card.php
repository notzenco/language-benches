<?php
/**
 * A titled card around a chart.
 * @var string $title
 * @var string $description
 * @var string $chart     HTML from a charts.php function
 * @var bool $profiles    show the release/min legend
 * @var array{string, string}|null $link href, label
 * @var string $note
 */
$profiles ??= false;
$link ??= null;
$note ??= '';
?>
        <figure class="card">
          <figcaption class="card-head">
            <div>
              <h3><?= h($title) ?></h3>
              <p><?= h($description) ?></p>
            </div>
<?php if ($link): ?>
            <a class="card-link" href="<?= h($link[0]) ?>"><?= h($link[1]) ?></a>
<?php endif ?>
          </figcaption>
<?php if ($profiles): ?>
          <div class="legend" aria-hidden="true">
            <span><i style="background: var(--s1)"></i>release</span>
            <span><i style="background: var(--s2)"></i>min (size-optimized, stripped)</span>
          </div>
<?php endif ?>
          <?= $chart ?>
<?php if ($note !== ''): ?>
          <p class="card-note"><?= h($note) ?></p>
<?php endif ?>
        </figure>
