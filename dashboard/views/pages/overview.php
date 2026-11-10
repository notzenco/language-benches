<?php
/**
 * @var string $title
 * @var array $results
 * @var list<array> $ranking  every language summary, best first
 * @var list<array> $ranked   those timed on every test
 * @var list<array> $partial  those timed on some tests only
 * @var list<array{string, string, string}> $stats
 * @var array $heat
 */
$n = count($results['tests']);
$description = $n
    ? sprintf('The same %s in %s compiled languages, built and timed on macOS arm64.',
        plural($n, 'program'), count($results['langs']) ?: 'several')
    : 'No tests found yet.';
$partialNote = match (count($partial)) {
    0 => '',
    1 => sprintf('%s covers %d of %d tests and is left out of the ranking.',
        lang_name($partial[0]['lang']), $partial[0]['timed'], $results['timed']),
    default => sprintf('%s are not timed on all %d tests and are left out of the ranking.',
        implode(', ', array_map(fn ($s) => lang_name($s['lang']), $partial)), $results['timed']),
};
?>
<?= render('partials/page-head', [
    'title'       => $title,
    'description' => $description,
    'chips'       => $results['lastRun'] ? [['Last run ' . fmt_date($results['lastRun']), '']] : [],
]) ?>
<?php if (!$results['tests']): ?>
      <p class="empty section">No tests found. Add a <code>&lt;category&gt;/&lt;test&gt;/expected.txt</code>.</p>
<?php elseif (!$results['timed']): ?>
      <p class="empty section">No timings yet. Run <code>./run.sh</code>, then reload.</p>
<?php else: ?>
<?= render('partials/stats', ['stats' => $stats]) ?>
      <section class="section">
<?= render('partials/heatmap', ['heat' => $heat, 'langs' => $results['langs']]) ?>
      </section>
      <section class="section grid-2">
<?php if ($ranked): ?>
<?= render('partials/chart-card', [
    'title'       => 'Ranking',
    'description' => "Geometric mean of run time ÷ the fastest, over all {$results['timed']} tests.",
    'chart'       => ranking_chart($ranked),
    'link'        => ['/languages', 'All languages →'],
    'note'        => $partialNote,
]) ?>
<?php endif ?>
<?= render('partials/chart-card', [
    'title'       => 'Typical binary size',
    'description' => 'Median across tests. Smaller is better.',
    'chart'       => typical_size_chart($ranking),
    'profiles'    => true,
]) ?>
      </section>
<?php endif ?>
