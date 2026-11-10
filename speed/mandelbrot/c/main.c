#include <stdio.h>
#include <stdlib.h>

int main(int argc, char **argv) {
    if (argc < 2) return 1;
    long n = atol(argv[1]);
    long count = 0;

    for (long y = 0; y < n; y++) {
        for (long x = 0; x < n; x++) {
            double cr = 2.0 * x / n - 1.5;
            double ci = 2.0 * y / n - 1.0;
            double zr = 0.0;
            double zi = 0.0;
            int escaped = 0;
            for (int i = 0; i < 50; i++) {
                double nr = zr * zr - zi * zi + cr;
                double ni = 2.0 * zr * zi + ci;
                zr = nr;
                zi = ni;
                if (zr * zr + zi * zi > 4.0) {
                    escaped = 1;
                    break;
                }
            }
            if (!escaped) count++;
        }
    }
    printf("%ld\n", count);
    return 0;
}
