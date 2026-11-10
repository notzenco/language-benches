# Build recipes shared by every test. Sourced by run.sh (bash 3.2 compatible).
#
# Profiles:
#   release  the usual optimized build for that toolchain
#   min      size-optimized, dead code and symbols stripped

LANGS=(asm c cpp zig rust swift go)
PROFILES=(release min)

SDK=$(xcrun --show-sdk-path)
ZIG_CACHE=${ZIG_CACHE:-build/.zig-cache}

# compile LANG PROFILE SRC_DIR OUT
#   SRC_DIR holds main.<ext> (C, C++, Go and Swift also pick up sibling files).
compile() {
  local lang=$1 profile=$2 src=$3 out=$4
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

    zig/release)
      zig build-exe "$src/main.zig" -O ReleaseFast --cache-dir "$ZIG_CACHE" -femit-bin="$out" &&
      rm -f "$out.o" ;;
    zig/min)
      zig build-exe "$src/main.zig" -O ReleaseSmall -fstrip --cache-dir "$ZIG_CACHE" -femit-bin="$out" &&
      rm -f "$out.o" ;;

    # release mirrors `cargo build --release` (opt-level 3, debuginfo stripped)
    rust/release)
      rustc --edition 2024 -C opt-level=3 -C strip=debuginfo "$src/main.rs" -o "$out" ;;
    rust/min)
      rustc --edition 2024 -C opt-level=z -C lto=fat -C codegen-units=1 -C panic=abort \
        -C strip=symbols "$src/main.rs" -o "$out" ;;

    go/release)  go build -o "$out" "$src"/*.go ;;
    go/min)      go build -trimpath -ldflags="-s -w" -o "$out" "$src"/*.go ;;

    swift/release) swiftc -O "$src"/*.swift -o "$out" ;;
    swift/min)     swiftc -Osize -Xlinker -dead_strip "$src"/*.swift -o "$out" && strip "$out" ;;

    *) echo "no build recipe for $lang/$profile" >&2; return 1 ;;
  esac
}
