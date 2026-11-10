#include <stdio.h>
#include <stdlib.h>

int main(int argc, char **argv) {
    if (argc < 2) return 1;
    long n = atol(argv[1]);
    unsigned char *sieve = calloc((size_t)n + 1, 1);
    if (!sieve) return 1;

    for (long i = 2; i * i <= n; i++) {
        if (sieve[i]) continue;
        for (long j = i * i; j <= n; j += i) sieve[j] = 1;
    }

    long count = 0;
    for (long i = 2; i <= n; i++) {
        if (!sieve[i]) count++;
    }
    printf("%ld\n", count);
    free(sieve);
    return 0;
}
