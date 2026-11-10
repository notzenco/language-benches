<?php
/**
 * A strip of headline numbers.
 * @var list<array{string, string, string}> $stats label, value, note
 */
?>
<?php if ($stats): ?>
      <div class="stats" style="--n: <?= count($stats) ?>">
<?php foreach ($stats as [$label, $value, $note]): ?>
        <div class="stat">
          <p class="stat-label"><?= h($label) ?></p>
          <p class="stat-value"><?= h($value) ?></p>
          <p class="stat-note" title="<?= h($note) ?>"><?= h($note) ?></p>
        </div>
<?php endforeach ?>
      </div>
<?php endif ?>
