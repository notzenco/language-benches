package main

import (
	"fmt"
	"os"
	"strconv"
)

type node struct {
	left, right *node
}

func makeTree(depth int) *node {
	n := &node{}
	if depth > 0 {
		n.left = makeTree(depth - 1)
		n.right = makeTree(depth - 1)
	}
	return n
}

func check(n *node) int64 {
	if n == nil {
		return 0
	}
	return 1 + check(n.left) + check(n.right)
}

func main() {
	n, err := strconv.Atoi(os.Args[1])
	if err != nil {
		panic(err)
	}
	const minDepth = 4
	maxDepth := minDepth + 2
	if n > maxDepth {
		maxDepth = n
	}

	stretch := makeTree(maxDepth + 1)
	fmt.Printf("stretch tree of depth %d\t check: %d\n", maxDepth+1, check(stretch))
	stretch = nil

	longLived := makeTree(maxDepth)
	for d := minDepth; d <= maxDepth; d += 2 {
		iterations := 1 << (maxDepth - d + minDepth)
		var sum int64
		for i := 0; i < iterations; i++ {
			t := makeTree(d)
			sum += check(t)
		}
		fmt.Printf("%d\t trees of depth %d\t check: %d\n", iterations, d, sum)
	}
	fmt.Printf("long lived tree of depth %d\t check: %d\n", maxDepth, check(longLived))
}
