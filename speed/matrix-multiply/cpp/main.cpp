#include <cstdint>
#include <iostream>
#include <string>
#include <vector>

int main(int argc, char **argv) {
    if (argc < 2) return 1;
    const long n = std::stol(argv[1]);
    const size_t nn = static_cast<size_t>(n) * static_cast<size_t>(n);
    std::vector<double> a(nn), b(nn), c(nn);

    for (long i = 0; i < n; i++) {
        for (long j = 0; j < n; j++) {
            a[static_cast<size_t>(i * n + j)] = static_cast<double>((i + j) % 10);
            b[static_cast<size_t>(i * n + j)] = static_cast<double>((i * j) % 10);
        }
    }

    for (long i = 0; i < n; i++) {
        for (long k = 0; k < n; k++) {
            double aik = a[static_cast<size_t>(i * n + k)];
            for (long j = 0; j < n; j++) {
                c[static_cast<size_t>(i * n + j)] += aik * b[static_cast<size_t>(k * n + j)];
            }
        }
    }

    std::int64_t sum = 0;
    for (double v : c) sum += static_cast<std::int64_t>(v);
    std::cout << sum << '\n';
    return 0;
}
