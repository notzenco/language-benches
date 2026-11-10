<?php
/**
 * @var array $results
 * @var string $current
 */
$byCategory = [];
foreach ($results['tests'] as $t) {
    $byCategory[$t['category'] === '' ? 'tests' : $t['category']][] = $t;
}
?>
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-top">
      <a class="brand" href="/">
        <span class="brand-mark" aria-hidden="true">lb</span>
        <span><span class="brand-name">language-benches</span><span class="brand-sub">macOS arm64</span></span>
      </a>
      <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="sidebar-nav"><?= icon('menu') ?>Menu</button>
    </div>
    <nav class="nav" id="sidebar-nav" aria-label="Dashboard">
      <div class="nav-group">
        <?= nav_link('/', 'Overview', $current, 'overview') ?>
        <?= nav_link('/toolchains', 'Toolchains', $current, 'tools') ?>
      </div>
<?php foreach ($byCategory as $category => $tests): ?>
      <div class="nav-group">
        <p class="nav-label"><?= h($category) ?></p>
<?php foreach ($tests as $t): ?>
        <?= nav_link(test_url($t['id']), $t['name'], $current) ?>
<?php endforeach ?>
      </div>
<?php endforeach ?>
<?php if ($results['langs']): ?>
      <div class="nav-group">
        <p class="nav-label">Languages</p>
        <?= nav_link('/languages', 'Ranking', $current, 'ranking') ?>
<?php foreach ($results['langs'] as $lang): ?>
        <?= nav_link(lang_url($lang), lang_name($lang), $current) ?>
<?php endforeach ?>
      </div>
<?php endif ?>
    </nav>
<?php if ($results['lastRun']): ?>
    <div class="sidebar-foot"><strong>Last run</strong><?= h(fmt_date($results['lastRun'])) ?></div>
<?php endif ?>
  </aside>
