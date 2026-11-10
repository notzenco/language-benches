<?php
/**
 * Compiler versions and build profiles.
 * @var string $title
 * @var array $results
 * @var list<array{string, ?string}> $crumbs
 */
?>
<?= render('partials/page-head', [
    'title'       => $title,
    'description' => 'Compiler versions recorded by the last ./run.sh, and the build profiles every test uses.',
    'crumbs'      => $crumbs,
]) ?>
      <section class="section">
        <div class="section-head">
          <div>
            <h2>Versions</h2>
          </div>
        </div>
<?php if (!$results['toolchains']): ?>
        <p class="empty">No versions recorded yet. Run <code>./run.sh</code>, then reload.</p>
<?php else: ?>
        <div class="card table-card">
          <div class="table-wrap">
            <table class="data">
              <thead>
                <tr><th scope="col">Language</th><th class="fill" scope="col">Toolchain</th></tr>
              </thead>
              <tbody>
<?php foreach (lang_order(array_keys($results['toolchains'])) as $lang): ?>
                <tr>
                  <td>
<?php if (in_array($lang, $results['langs'], true)): ?>
                    <a href="<?= h(lang_url($lang)) ?>"><?= h(lang_name($lang)) ?></a>
<?php else: ?>
                    <?= h(lang_name($lang)) ?>
<?php endif ?>
                  </td>
                  <td class="wrap"><code><?= h($results['toolchains'][$lang]) ?></code></td>
                </tr>
<?php endforeach ?>
              </tbody>
            </table>
          </div>
        </div>
<?php endif ?>
      </section>
      <section class="section">
        <div class="section-head">
          <div>
            <h2>Build profiles</h2>
            <p>Every language is built once per profile. Exact flags are in <code>lib/langs.sh</code>.</p>
          </div>
        </div>
        <div class="card table-card">
          <div class="table-wrap">
            <table class="data">
              <thead>
                <tr><th scope="col">Profile</th><th class="fill" scope="col">What it is</th></tr>
              </thead>
              <tbody>
<?php foreach (PROFILES as $profile => $about): ?>
                <tr><td><code><?= h($profile) ?></code></td><td class="wrap soft"><?= h($about) ?></td></tr>
<?php endforeach ?>
              </tbody>
            </table>
          </div>
        </div>
        <p class="card-note">Run time and peak memory come from the release build, timed with hyperfine and ranked by median.</p>
      </section>
