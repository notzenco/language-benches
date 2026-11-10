<?php
/**
 * Test × language grid of median run times, shaded by slowdown vs the
 * fastest language in that test.
 * @var array $heat  test id => [test, cells: lang => null|{text, class, best, tip}]
 * @var list<string> $langs
 */
$legend = ['1×'];
foreach (HEAT_STEPS as $i => $step) {
    $legend[] = rtrim(rtrim(sprintf('%.2f', $step), '0'), '.') . '×' . ($i === count(HEAT_STEPS) - 1 ? '+' : '');
}
?>
        <figure class="card">
          <figcaption class="card-head">
            <div>
              <h3>Run time</h3>
              <p>Median wall-clock time of each release build, in ms. Lower is better.</p>
            </div>
          </figcaption>
          <div class="heat-wrap">
            <table class="heat">
              <caption class="sr-only">Median run time in milliseconds per test and language; the fastest in each row is bold.</caption>
              <thead>
                <tr>
                  <th scope="col">Test</th>
<?php foreach ($langs as $lang): ?>
                  <th scope="col"><a href="<?= h(lang_url($lang)) ?>"><?= h(lang_name($lang)) ?></a></th>
<?php endforeach ?>
                </tr>
              </thead>
              <tbody>
<?php foreach ($heat as $id => ['test' => $t, 'cells' => $cells]): ?>
                <tr>
                  <th scope="row"><a href="<?= h(test_url($id)) ?>"><?= h($t['name']) ?></a> <small><?= h($t['times'] ? $t['category'] : 'not timed') ?></small></th>
<?php foreach ($cells as $c): ?>
<?php if ($c === null): ?>
                  <td class="none"><span aria-hidden="true">–</span><span class="sr-only">no result</span></td>
<?php else: ?>
                  <td class="<?= h($c['class']) ?><?= $c['best'] ? ' best' : '' ?>"<?= tip_attrs($c['tip']) ?>><?= h($c['text']) ?></td>
<?php endif ?>
<?php endforeach ?>
                </tr>
<?php endforeach ?>
              </tbody>
            </table>
          </div>
          <div class="scale" aria-hidden="true">
            <span>Slowdown vs the fastest in each row</span>
            <ol>
<?php foreach ($legend as $i => $label): ?>
              <li><i class="h<?= $i + 1 ?>"></i><?= h($label) ?></li>
<?php endforeach ?>
            </ol>
            <span><strong>Bold</strong>: fastest</span>
          </div>
        </figure>
