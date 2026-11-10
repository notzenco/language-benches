#include <cstdint>
#include <cstdlib>
#include <iostream>
#include <unordered_map>

int main(int argc, char **argv) {
    if (argc < 3) return 1;
    const std::uint64_t ops = std::strtoull(argv[1], nullptr, 10);
    const std::uint64_t keys = std::strtoull(argv[2], nullptr, 10);

    std::unordered_map<std::uint64_t, std::uint64_t> counts;
    std::uint64_t x = 42;
    for (std::uint64_t i = 0; i < ops; i++) {
        x = x * 6364136223846793005ull + 1442695040888963407ull;
        counts[(x >> 33) % keys]++;
    }

    std::uint64_t max_count = 0;
    for (const auto &entry : counts) {
        if (entry.second > max_count) max_count = entry.second;
    }
    std::cout << "distinct: " << counts.size() << '\n';
    std::cout << "max: " << max_count << '\n';
    return 0;
}
