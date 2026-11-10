#include <stdint.h>
#include <stdio.h>
#include <stdlib.h>

typedef struct {
    uint64_t key;
    uint64_t count;
    int used;
} Slot;

typedef struct {
    Slot *slots;
    size_t cap;
    unsigned shift; /* 64 - log2(cap) */
    size_t used;
} Map;

/* Fibonacci hashing: multiply by 2^64 / phi and keep the top bits, which
   depend on every bit of the key (the low bits only see the key's low bits). */
static size_t slot_for(uint64_t key, unsigned shift) {
    return (size_t)((key * 11400714819323198485ull) >> shift);
}

static void map_init(Map *m) {
    m->cap = 16;
    m->shift = 60;
    m->used = 0;
    m->slots = calloc(m->cap, sizeof *m->slots);
    if (!m->slots) abort();
}

static void map_grow(Map *m) {
    size_t ncap = m->cap << 1;
    unsigned nshift = m->shift - 1;
    Slot *nslots = calloc(ncap, sizeof *nslots);
    size_t i;
    if (!nslots) abort();
    for (i = 0; i < m->cap; i++) {
        size_t j;
        if (!m->slots[i].used) continue;
        j = slot_for(m->slots[i].key, nshift);
        while (nslots[j].used) j = (j + 1) & (ncap - 1);
        nslots[j] = m->slots[i];
    }
    free(m->slots);
    m->slots = nslots;
    m->cap = ncap;
    m->shift = nshift;
}

static void map_add(Map *m, uint64_t key) {
    size_t i = slot_for(key, m->shift);
    while (m->slots[i].used) {
        if (m->slots[i].key == key) {
            m->slots[i].count++;
            return;
        }
        i = (i + 1) & (m->cap - 1);
    }
    if ((m->used + 1) * 10 > m->cap * 7) {
        map_grow(m);
        i = slot_for(key, m->shift);
        while (m->slots[i].used) i = (i + 1) & (m->cap - 1);
    }
    m->slots[i].used = 1;
    m->slots[i].key = key;
    m->slots[i].count = 1;
    m->used++;
}

int main(int argc, char **argv) {
    uint64_t ops, keys, x, i, max_count;
    size_t s;
    Map map;

    if (argc < 3) return 1;
    ops = strtoull(argv[1], NULL, 10);
    keys = strtoull(argv[2], NULL, 10);
    map_init(&map);
    x = 42;
    for (i = 0; i < ops; i++) {
        x = x * 6364136223846793005ull + 1442695040888963407ull;
        map_add(&map, (x >> 33) % keys);
    }
    max_count = 0;
    for (s = 0; s < map.cap; s++) {
        if (map.slots[s].used && map.slots[s].count > max_count) max_count = map.slots[s].count;
    }
    printf("distinct: %zu\n", map.used);
    printf("max: %llu\n", (unsigned long long)max_count);
    free(map.slots);
    return 0;
}
