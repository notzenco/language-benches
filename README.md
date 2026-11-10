# language-benches

The same small programs written in several compiled languages, built the same
way and compared. Target: macOS arm64.

```
./run.sh                     # every test
./run.sh basic/hello-world   # one or more tests
```

## Layout

```
<category>/<test>/
  expected.txt        exact stdout every language must print
  args                optional: command-line arguments passed to every program
  <lang>/main.<ext>   one directory per language (missing = skipped)
lib/langs.sh          build commands per language and profile
build/                output: build/<category>/<test>/<profile>/<lang>
```

Languages: `asm c cpp zig rust swift go`. Profiles: `release` (the usual
optimized build) and `min` (size-optimized and stripped). `run.sh` builds
every language in each profile, checks its stdout against `expected.txt`,
and prints binary sizes.

Tests under `speed/` are also timed: the `release` builds are run with
[hyperfine](https://github.com/sharkdp/hyperfine) (`brew install hyperfine`)
and ranked by median wall time, with peak memory. Full stats land in
`build/<category>/<test>/times.csv`. Pass problem sizes through `args`
rather than hard-coding them, so compilers can't precompute the answer.
Close busy apps first; background load (e.g. Spotlight indexing) skews times.

## Tests

| test                   | measures |
|------------------------|----------|
| `basic/hello-world`    | binary size floor and runtime linkage |
| `speed/fib-recursive`  | function-call overhead: naive recursive `fib(40)` |

## Adding a test

1. Make `<category>/<test>/expected.txt`.
2. Add `<lang>/main.<ext>` for each language you want to compare.
3. `./run.sh <category>/<test>`

## Adding a language

Add it to `LANGS` and give it one `compile` case per profile in
`lib/langs.sh`.
