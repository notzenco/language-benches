<?php
/**
 * One language across every test.
 * @var string $title
 * @var array $s   language_summary() plus place
 * @var list<array{string, string, string}> $stats
 * @var string $description
 * @var list<array{string, string}> $chips
 * @var list<array{string, ?string}> $crumbs
 */
?>
<?= render('partials/page-head', compact('title', 'description', 'chips', 'crumbs')) ?>
<?= render('partials/stats', ['stats' => $stats]) ?>
<?php if ($s['timed'] > 0): ?>
      <section class="section">
<?= render('partials/chart-card', [
    'title'       => 'Run time vs the fastest',
    'description' => 'Median of the release build ÷ the fastest language’s median, per test. 1.00× is fastest.',
    'chart'       => slowdown_chart($s),
]) ?>
      </section>
<?php endif ?>
      <section class="section">
        <div class="section-head">
          <div>
            <h2>Per test</h2>
            <p>Release-build timings, binary sizes and compile times.</p>
          </div>
        </div>
        <div class="card table-card">
          <div class="table-wrap">
            <table class="data">
              <thead>
                <tr>
                  <th scope="col">Test</th>
                  <th class="num" scope="col">Rank</th>
                  <th class="num" scope="col">Median</th>
                  <th class="num" scope="col">vs fastest</th>
                  <th class="num" scope="col">Peak RSS</th>
                  <th class="num" scope="col">Release size</th>
                  <th class="num" scope="col">Min size</th>
                  <th class="num" scope="col">Release build</th>
                  <th class="num" scope="col">Min build</th>
                </tr>
              </thead>
              <tbody>
<?php foreach ($s['rows'] as $id => $r): ?>
                <tr>
                  <td><a href="<?= h(test_url($id)) ?>"><?= h($r['test']['name']) ?></a> <span class="soft"><?= h($r['test']['category']) ?></span></td>
                  <td class="num"><?= $r['rank'] === null ? '–' : h("#{$r['rank']} of {$r['of']}") ?></td>
                  <td class="num"><?= or_dash($r['times']['median'] ?? null, 'fmt_ms') ?></td>
                  <td class="num"><?= or_dash($r['ratio'], 'fmt_ratio') ?></td>
                  <td class="num"><?= or_dash($r['times']['rss'] ?? null, 'fmt_mem') ?></td>
                  <td class="num"><?= or_dash($r['sizes']['release'] ?? null, 'fmt_size') ?></td>
                  <td class="num"><?= or_dash($r['sizes']['min'] ?? null, 'fmt_size') ?></td>
                  <td class="num"><?= or_dash($r['build_ms']['release'] ?? null, 'fmt_ms') ?></td>
                  <td class="num"><?= or_dash($r['build_ms']['min'] ?? null, 'fmt_ms') ?></td>
                </tr>
<?php endforeach ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>
