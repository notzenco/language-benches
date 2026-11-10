#include <iostream>
#include <string>
#include <vector>

int main(int argc, char **argv) {
    if (argc < 2) return 1;
    const long n = std::stol(argv[1]);
    // vector<char>, not vector<bool>: one byte per number, not a bit pack.
    std::vector<unsigned char> sieve(static_cast<size_t>(n) + 1);

    for (long i = 2; i * i <= n; i++) {
        if (sieve[static_cast<size_t>(i)]) continue;
        for (long j = i * i; j <= n; j += i) sieve[static_cast<size_t>(j)] = 1;
    }

    long count = 0;
    for (long i = 2; i <= n; i++) {
        if (!sieve[static_cast<size_t>(i)]) count++;
    }
    std::cout << count << '\n';
    return 0;
}
