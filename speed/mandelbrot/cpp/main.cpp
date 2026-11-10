#include <iostream>
#include <string>

int main(int argc, char **argv) {
    if (argc < 2) return 1;
    const long n = std::stol(argv[1]);
    long count = 0;

    for (long y = 0; y < n; y++) {
        for (long x = 0; x < n; x++) {
            double cr = 2.0 * x / n - 1.5;
            double ci = 2.0 * y / n - 1.0;
            double zr = 0.0;
            double zi = 0.0;
            bool escaped = false;
            for (int i = 0; i < 50; i++) {
                double nr = zr * zr - zi * zi + cr;
                double ni = 2.0 * zr * zi + ci;
                zr = nr;
                zi = ni;
                if (zr * zr + zi * zi > 4.0) {
                    escaped = true;
                    break;
                }
            }
            if (!escaped) count++;
        }
    }
    std::cout << count << '\n';
    return 0;
}
