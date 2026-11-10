#include <iostream>
#include <memory>
#include <cstdlib>

struct Node {
    std::unique_ptr<Node> left;
    std::unique_ptr<Node> right;
};

static std::unique_ptr<Node> make_tree(int depth) {
    auto node = std::make_unique<Node>();
    if (depth > 0) {
        node->left = make_tree(depth - 1);
        node->right = make_tree(depth - 1);
    }
    return node;
}

static long long check(const Node *node) {
    if (!node) return 0;
    return 1 + check(node->left.get()) + check(node->right.get());
}

int main(int argc, char **argv) {
    if (argc < 2) return 1;
    const int n = std::atoi(argv[1]);
    const int min_depth = 4;
    const int max_depth = n > min_depth + 2 ? n : min_depth + 2;

    {
        auto stretch = make_tree(max_depth + 1);
        std::cout << "stretch tree of depth " << (max_depth + 1) << "\t check: " << check(stretch.get()) << '\n';
    }

    auto long_lived = make_tree(max_depth);
    for (int d = min_depth; d <= max_depth; d += 2) {
        const long long iterations = 1LL << (max_depth - d + min_depth);
        long long sum = 0;
        for (long long i = 0; i < iterations; i++) {
            auto tree = make_tree(d);
            sum += check(tree.get());
        }
        std::cout << iterations << "\t trees of depth " << d << "\t check: " << sum << '\n';
    }
    std::cout << "long lived tree of depth " << max_depth << "\t check: " << check(long_lived.get()) << '\n';
    return 0;
}
