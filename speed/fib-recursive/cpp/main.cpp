#include <cstdint>
#include <iostream>
#include <string>

static std::int64_t fib(std::int64_t n) {
    return n < 2 ? n : fib(n - 1) + fib(n - 2);
}

int main(int argc, char** argv) {
    if (argc < 2) return 1;
    std::cout << fib(std::stoll(argv[1])) << '\n';
    return 0;
}
