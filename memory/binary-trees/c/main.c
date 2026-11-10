#include <stdio.h>
#include <stdlib.h>

typedef struct Node {
    struct Node *left;
    struct Node *right;
} Node;

static Node *make_tree(int depth) {
    Node *node = malloc(sizeof *node);
    if (!node) abort();
    if (depth == 0) {
        node->left = node->right = NULL;
    } else {
        node->left = make_tree(depth - 1);
        node->right = make_tree(depth - 1);
    }
    return node;
}

static void free_tree(Node *node) {
    if (!node) return;
    free_tree(node->left);
    free_tree(node->right);
    free(node);
}

static long long check(const Node *node) {
    if (!node) return 0;
    return 1 + check(node->left) + check(node->right);
}

int main(int argc, char **argv) {
    int n, min_depth = 4, max_depth, d;
    Node *stretch, *long_lived;

    if (argc < 2) return 1;
    n = atoi(argv[1]);
    max_depth = min_depth + 2;
    if (n > max_depth) max_depth = n;

    stretch = make_tree(max_depth + 1);
    printf("stretch tree of depth %d\t check: %lld\n", max_depth + 1, check(stretch));
    free_tree(stretch);

    long_lived = make_tree(max_depth);
    for (d = min_depth; d <= max_depth; d += 2) {
        long long iterations = 1LL << (max_depth - d + min_depth);
        long long i, sum = 0;
        for (i = 0; i < iterations; i++) {
            Node *tree = make_tree(d);
            sum += check(tree);
            free_tree(tree);
        }
        printf("%lld\t trees of depth %d\t check: %lld\n", iterations, d, sum);
    }
    printf("long lived tree of depth %d\t check: %lld\n", max_depth, check(long_lived));
    free_tree(long_lived);
    return 0;
}
