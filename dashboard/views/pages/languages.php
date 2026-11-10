<?php
/**
 * Every language, ranked by score.
 * @var string $title
 * @var array $results
 * @var list<array> $ranking
 * @var list<array{string, string, string}> $stats
 * @var float $max   largest score, for the meter scale
 * @var list<array{string, ?string}> $crumbs
 */
?>
<?= render('partials/page-head', [
    'title'       => $title,
    'description' => 'Ranked by how close each comes to the fastest language, test by test.',
    'crumbs'      => $crumbs,
]) ?>
<?php if (!$ranking): ?>
      <p class="empty section">No results yet. Run <code>./run.sh</code>, then reload.</p>
<?php else: ?>
<?= render('partials/stats', ['stats' => $stats]) ?>
      <section class="section">
        <div class="card table-card">
          <div class="table-wrap">
            <table class="data">
              <thead>
                <tr>
                  <th class="rank" scope="col">#</th>
                  <th scope="col">Language</th>
                  <th class="wide" scope="col">Score (geometric mean vs fastest)</th>
                  <th class="num" scope="col">Wins</th>
                  <th class="num" scope="col">Tests timed</th>
                  <th class="num" scope="col">Typical binary</th>
                  <th class="num" scope="col">Median build</th>
                </tr>
              </thead>
              <tbody>
<?php foreach ($ranking as $s): $partial = $s['place'] === null; ?>
                <tr<?= $partial ? ' class="partial"' : '' ?>>
                  <td class="rank"><?= $s['place'] ?? '–' ?></td>
                  <td>
                    <a href="<?= h(lang_url($s['lang'])) ?>"><?= h(lang_name($s['lang'])) ?></a>
<?php if ($partial && $s['timed'] > 0): ?>
                    <span class="badge">partial</span>
<?php endif ?>
                  </td>
                  <td>
<?php if ($s['score'] !== null): ?>
                    <div class="meter">
                      <div class="meter-track"><div class="meter-fill" style="--f: <?= sprintf('%.4f', $max > 0 ? $s['score'] / $max : 0) ?>"></div></div>
                      <span class="meter-value"><?= h(fmt_ratio($s['score'])) ?></span>
                    </div>
<?php else: ?>
                    <span class="soft">not timed</span>
<?php endif ?>
                  </td>
                  <td class="num"><?= $s['wins'] ?></td>
                  <td class="num"><?= h("{$s['timed']} of {$results['timed']}") ?></td>
                  <td class="num"><?= or_dash($s['size']['release'], 'fmt_size') ?></td>
                  <td class="num"><?= or_dash($s['build']['release'], 'fmt_ms') ?></td>
                </tr>
<?php endforeach ?>
              </tbody>
            </table>
          </div>
        </div>
        <p class="card-note">
          Score: for each test, the language’s median run time divided by the fastest median, then the
          geometric mean over its tests. 1.00× means fastest in every test. Languages not timed on every
          test are listed but not ranked, since their score covers different tests. Typical binary and
          median build are medians of the release build across tests.
        </p>
      </section>
<?php endif ?>
