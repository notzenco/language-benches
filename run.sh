#!/usr/bin/env bash
# Build each language's version of a test, check its output, and report sizes.
# Tests under speed/ are also timed with hyperfine.
#
#   ./run.sh                       run every test
#   ./run.sh basic/hello-world     run specific tests
#
# A test is any directory with an expected.txt (the exact stdout every
# language must produce). Sources live in <test>/<lang>/main.<ext>; a
# language without a directory is skipped. An optional <test>/args file holds
# command-line arguments passed to every program. Binaries are written to
# build/<test>/<profile>/<lang>. Build commands live in lib/langs.sh.
set -euo pipefail

ROOT=$(cd "$(dirname "$0")" && pwd)
cd "$ROOT"
source lib/langs.sh

if (( $# )); then
  TESTS=("${@%/}")
else
  TESTS=($(find . -path ./build -prune -o -name expected.txt -print | sed 's|^\./||; s|/expected\.txt$||' | sort))
fi

human() {
  awk -v b="$1" 'BEGIN { if (b >= 1048576) printf "%.2f MiB", b/1048576; else printf "%.1f KiB", b/1024 }'
}

run_test() {
  local t=$1 lang p out row libs
  local -a args=()
  [[ -f $t/expected.txt ]] || { echo "not a test (no expected.txt): $t" >&2; return 1; }
  if [[ -f $t/args ]]; then read -r -a args < "$t/args"; fi
  rm -rf "build/$t"

  echo
  echo "## $t${args[*]+ (args: ${args[*]})}"
  echo
  row="| lang  "; for p in "${PROFILES[@]}"; do row+=$(printf '| %10s ' "$p"); done
  echo "$row| dynamic libs (${PROFILES[${#PROFILES[@]}-1]})"
  row="|-------"; for p in "${PROFILES[@]}"; do row+="|------------"; done
  echo "$row|------------------"

  for lang in "${LANGS[@]}"; do
    [[ -d $t/$lang ]] || continue
    row=$(printf '| %-5s ' "$lang")
    for p in "${PROFILES[@]}"; do
      out=build/$t/$p/$lang
      mkdir -p "build/$t/$p"
      compile "$lang" "$p" "$t/$lang" "$out"
      (cd "$t" && "$ROOT/$out" ${args[@]+"${args[@]}"}) > "$out.stdout"
      if ! cmp -s "$out.stdout" "$t/expected.txt"; then
        echo "FAIL $t [$lang/$p]: output differs from expected.txt" >&2
        diff "$t/expected.txt" "$out.stdout" >&2 || true
        return 1
      fi
      row+=$(printf '| %10s ' "$(human "$(stat -f %z "$out")")")
    done
    libs=$(otool -L "$out" | tail -n +2 | awk '{print $1}' | xargs -n1 basename | paste -sd, -)
    echo "$row| $libs"
  done

  if [[ $t == speed/* ]]; then
    time_test "$t" ${args[@]+"${args[@]}"}
  fi
}

# time_test TEST [ARGS...]: benchmark every language's release build.
time_test() {
  local t=$1 lang csv=build/$1/times.csv
  shift
  local -a cmds=()
  command -v hyperfine > /dev/null || { echo "speed tests need hyperfine: brew install hyperfine" >&2; return 1; }
  for lang in "${LANGS[@]}"; do
    [[ -d $t/$lang ]] || continue
    cmds+=(-n "$lang" "$ROOT/build/$t/release/$lang $*")
  done

  echo
  echo "timing release builds with hyperfine..." >&2
  (cd "$t" && hyperfine -N --style none --warmup 2 --min-runs 10 \
    --metrics time_wall_clock:ms,memory_peak_resident:MiB \
    --export-csv "$ROOT/$csv" "${cmds[@]}")

  # Ranked by median: a busy machine adds slow outliers that skew the mean.
  # Full stats (mean, stddev, max) are in build/<test>/times.csv.
  echo "| lang  | median (ms) |   min (ms) | relative |   peak RSS |"
  echo "|-------|-------------|------------|----------|------------|"
  awk -F, '
    NR == 1 { for (i = 1; i <= NF; i++) col[$i] = i; next }
    { print $col["time_wall_clock_median"] "," $0 }' "$csv" | sort -t, -k1,1g | cut -d, -f2- |
  awk -F, -v hdr="$(head -1 "$csv")" '
    BEGIN { n = split(hdr, h, ","); for (i = 1; i <= n; i++) col[h[i]] = i }
    {
      med = $col["time_wall_clock_median"]; if (NR == 1) best = med
      printf "| %-5s | %11.1f | %10.1f | %7.2fx | %6.1f MiB |\n", $col["command"], med,
        $col["time_wall_clock_min"], med / best, $col["memory_peak_resident_median"]
    }'
}

for t in "${TESTS[@]}"; do
  run_test "$t"
done
echo
echo "All outputs matched expected.txt."
