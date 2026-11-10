#include <stdint.h>
#include <stdio.h>
#include <stdlib.h>

int main(int argc, char **argv) {
    if (argc < 2) return 1;
    long n = atol(argv[1]);
    size_t nn = (size_t)n * (size_t)n;
    double *a = malloc(nn * sizeof(double));
    double *b = malloc(nn * sizeof(double));
    double *c = calloc(nn, sizeof(double));
    if (!a || !b || !c) return 1;

    for (long i = 0; i < n; i++) {
        for (long j = 0; j < n; j++) {
            a[i * n + j] = (double)((i + j) % 10);
            b[i * n + j] = (double)((i * j) % 10);
        }
    }

    for (long i = 0; i < n; i++) {
        for (long k = 0; k < n; k++) {
            double aik = a[i * n + k];
            for (long j = 0; j < n; j++) {
                c[i * n + j] += aik * b[k * n + j];
            }
        }
    }

    int64_t sum = 0;
    for (size_t i = 0; i < nn; i++) sum += (int64_t)c[i];
    printf("%lld\n", (long long)sum);

    free(a);
    free(b);
    free(c);
    return 0;
}
