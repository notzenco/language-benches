# Build recipes shared by every test. Sourced by run.sh (bash 3.2 compatible).
#
# Profiles:
#   release  the usual optimized build for that toolchain
#   min      size-optimized, dead code and symbols stripped

LANGS=(asm c cpp zig rust swift go)
PROFILES=(release min)

SDK=$(xcrun --show-sdk-path)

# toolchain_version LANG: one line naming the compiler, saved with results
toolchain_version() {
  case $1 in
    asm)   as --version 2>&1 | head -1 ;;
    c|cpp) clang --version | head -1 ;;
    zig)   echo "zig $(zig version)" ;;
    rust)  rustc --version ;;
    swift) swiftc --version 2>&1 | grep -o 'Apple Swift version [0-9.]*' | head -1 ;;
    go)    go version ;;
    *)     echo unknown ;;
  esac
}

# go_build OUT SRC_DIR [go build flags...]
# Recompile this package every run. Go's build cache is content-addressed, so
# an unchanged tree is a cache hit and a rebuild looks instant. Copy main.go,
# append a unique comment, and compile it via -overlay so the compiler still
# records the original path: an edit-and-rebuild of this package only.
# Imported standard-library packages are unchanged, so their cache stays warm.
# The comment is not code, and the flags passed in are the ones that affect
# the binary (-overlay does not change codegen).
go_build() {
  local out=$1 src=$2
  shift 2
  local tmp stamp rc=0 orig
  tmp=$(mktemp -d "${TMPDIR:-/tmp}/langbench-go.XXXXXX")
  orig=$(cd "$src" && pwd)/main.go
  cp "$orig" "$tmp/main.go" || { rm -rf "$tmp"; return 1; }
  stamp=$(perl -MTime::HiRes=time -e 'printf "%.6f", time')
  printf '\n// rebuild %s\n' "$stamp" >> "$tmp/main.go"
  perl -e '
    sub j { my $s = shift; $s =~ s/\\/\\\\/g; $s =~ s/"/\\"/g; return $s }
    printf "{\"Replace\":{\"%s\":\"%s\"}}\n", j($ARGV[0]), j($ARGV[1]);
  ' "$orig" "$tmp/main.go" > "$tmp/overlay.json"
  if (($#)); then
    go build -overlay "$tmp/overlay.json" "$@" -o "$out" "$src"/*.go || rc=$?
  else
    go build -overlay "$tmp/overlay.json" -o "$out" "$src"/*.go || rc=$?
  fi
  rm -rf "$tmp"
  return "$rc"
}

# compile LANG PROFILE SRC_DIR OUT
#   SRC_DIR holds main.<ext> (C, C++, Go and Swift also pick up sibling files).
compile() {
  local lang=$1 profile=$2 src=$3 out=$4
  local zig_cache
  case $lang/$profile in
    asm/release)
      as -arch arm64 "$src/main.s" -o "$out.o" &&
      ld -arch arm64 -e _start -lSystem -syslibroot "$SDK" "$out.o" -o "$out" &&
      rm "$out.o" ;;
    asm/min)
      as -arch arm64 "$src/main.s" -o "$out.o" &&
      ld -arch arm64 -e _start -lSystem -syslibroot "$SDK" -dead_strip "$out.o" -o "$out" &&
      rm "$out.o" && strip "$out" ;;

    c/release)   clang -O2 "$src"/*.c -o "$out" ;;
    c/min)       clang -Os -Wl,-dead_strip "$src"/*.c -o "$out" && strip "$out" ;;

    cpp/release) clang++ -std=c++20 -O2 "$src"/*.cpp -o "$out" ;;
    cpp/min)     clang++ -std=c++20 -Os -Wl,-dead_strip "$src"/*.cpp -o "$out" && strip "$out" ;;

    # Local cache is per test and profile (build/<test>/<profile>/.zig-cache).
    # run.sh deletes build/<test> first, so each run compiles against a cold
    # local cache. Zig's global cache (~/.cache/zig) is left alone and may
    # stay warm.
    zig/release)
      zig_cache=$(dirname "$out")/.zig-cache
      mkdir -p "$zig_cache"
      zig build-exe "$src/main.zig" -O ReleaseFast --cache-dir "$zig_cache" -femit-bin="$out" &&
      rm -f "$out.o" ;;
    zig/min)
      zig_cache=$(dirname "$out")/.zig-cache
      mkdir -p "$zig_cache"
      zig build-exe "$src/main.zig" -O ReleaseSmall -fstrip --cache-dir "$zig_cache" -femit-bin="$out" &&
      rm -f "$out.o" ;;

    # release mirrors `cargo build --release` (opt-level 3, debuginfo stripped)
    rust/release)
      rustc --edition 2024 -C opt-level=3 -C strip=debuginfo "$src/main.rs" -o "$out" ;;
    rust/min)
      rustc --edition 2024 -C opt-level=z -C lto=fat -C codegen-units=1 -C panic=abort \
        -C strip=symbols "$src/main.rs" -o "$out" ;;

    go/release)  go_build "$out" "$src" ;;
    go/min)      go_build "$out" "$src" -trimpath -ldflags="-s -w" ;;

    swift/release) swiftc -O "$src"/*.swift -o "$out" ;;
    swift/min)     swiftc -Osize -Xlinker -dead_strip "$src"/*.swift -o "$out" && strip "$out" ;;

    *) echo "no build recipe for $lang/$profile" >&2; return 1 ;;
  esac
}
