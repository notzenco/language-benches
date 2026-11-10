<?php
/**
 * One test: headline numbers, four charts and the full table.
 * @var string $title
 * @var array $t            load_test()
 * @var list<array{string, string, string}> $stats
 * @var list<string> $order languages, fastest first
 * @var string $description
 * @var list<array{string, string}> $chips
 * @var list<array{string, ?string}> $crumbs
 * @var bool $hasRss
 */
$hasBuild = $t['build_ms'] !== [];
$hasTimes = $t['times'] !== [];
?>
<?= render('partials/page-head', compact('title', 'description', 'chips', 'crumbs')) ?>
<?php if (!$t['sizes']): ?>
      <p class="empty section">No results yet. Run <code>./run.sh <?= h($t['id']) ?></code>, then reload.</p>
<?php else: ?>
<?= render('partials/stats', ['stats' => $stats]) ?>
      <section class="section grid-2">
<?php if ($hasTimes): ?>
<?= render('partials/chart-card', [
    'title'       => 'Run time',
    'description' => 'Median wall-clock time of the release build. Lower is better.',
    'chart'       => time_chart($t),
]) ?>
<?php endif ?>
<?php if ($hasRss): ?>
<?= render('partials/chart-card', [
    'title'       => 'Peak memory',
    'description' => 'Peak resident set size of the release build. Lower is better.',
    'chart'       => memory_chart($t),
]) ?>
<?php endif ?>
<?= render('partials/chart-card', [
    'title'       => 'Binary size',
    'description' => 'Bytes on disk. Smaller is better.',
    'chart'       => size_chart($t),
    'profiles'    => true,
]) ?>
<?php if ($hasBuild): ?>
<?= render('partials/chart-card', [
    'title'       => 'Build time',
    'description' => 'Wall-clock time to compile. Lower is better.',
    'chart'       => build_chart($t),
    'profiles'    => true,
]) ?>
<?php endif ?>
      </section>

      <section class="section">
        <div class="section-head">
          <div>
            <h2>Results</h2>
            <p><?= $hasTimes ? 'Every language, ranked by median run time.' : 'Every language that built.' ?></p>
          </div>
        </div>
        <div class="card table-card">
          <div class="table-wrap">
            <table class="data">
              <thead>
                <tr>
<?php if ($hasTimes): ?>
                  <th class="rank" scope="col">#</th>
<?php endif ?>
                  <th scope="col">Language</th>
<?php if ($hasTimes): ?>
                  <th class="num" scope="col">Median</th>
                  <th class="num" scope="col">vs fastest</th>
                  <th class="num" scope="col">Mean ± σ</th>
                  <th class="num" scope="col">Min … max</th>
                  <th class="num" scope="col">Peak RSS</th>
<?php endif ?>
                  <th class="num" scope="col">Release size</th>
                  <th class="num" scope="col">Min size</th>
<?php if ($hasBuild): ?>
                  <th class="num" scope="col">Release build</th>
                  <th class="num" scope="col">Min build</th>
<?php endif ?>
                  <th scope="col">Linked libraries</th>
                </tr>
              </thead>
              <tbody>
<?php foreach ($order as $i => $lang): $s = $t['times'][$lang] ?? null; ?>
                <tr>
<?php if ($hasTimes): ?>
                  <td class="rank"><?= $s ? $i + 1 : '–' ?></td>
<?php endif ?>
                  <td><a href="<?= h(lang_url($lang)) ?>"><?= h(lang_name($lang)) ?></a></td>
<?php if ($hasTimes): ?>
                  <td class="num"><?= or_dash($s['median'] ?? null, 'fmt_ms') ?></td>
                  <td class="num"><?= $s ? h(fmt_ratio($s['median'] / $t['best'])) : '–' ?></td>
                  <td class="num"><?= $s ? h(fmt_ms($s['mean']) . ' ± ' . fmt_ms($s['stddev'])) : '–' ?></td>
                  <td class="num"><?= $s ? h(fmt_ms($s['min']) . ' … ' . fmt_ms($s['max'])) : '–' ?></td>
                  <td class="num"><?= or_dash($s['rss'] ?? null, 'fmt_mem') ?></td>
<?php endif ?>
                  <td class="num"><?= or_dash($t['sizes'][$lang]['release'] ?? null, 'fmt_size') ?></td>
                  <td class="num"><?= or_dash($t['sizes'][$lang]['min'] ?? null, 'fmt_size') ?></td>
<?php if ($hasBuild): ?>
                  <td class="num"><?= or_dash($t['build_ms'][$lang]['release'] ?? null, 'fmt_ms') ?></td>
                  <td class="num"><?= or_dash($t['build_ms'][$lang]['min'] ?? null, 'fmt_ms') ?></td>
<?php endif ?>
                  <td class="soft"><?= h(str_replace(' ', ', ', $t['libs'][$lang] ?? '')) ?></td>
                </tr>
<?php endforeach ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>
<?php endif ?>
