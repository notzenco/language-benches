package main

import (
	"fmt"
	"os"
	"strconv"
)

func main() {
	ops, err := strconv.ParseUint(os.Args[1], 10, 64)
	if err != nil {
		panic(err)
	}
	keys, err := strconv.ParseUint(os.Args[2], 10, 64)
	if err != nil {
		panic(err)
	}

	counts := make(map[uint64]uint64)
	x := uint64(42)
	for i := uint64(0); i < ops; i++ {
		x = x*6364136223846793005 + 1442695040888963407
		counts[(x>>33)%keys]++
	}

	var max uint64
	for _, v := range counts {
		if v > max {
			max = v
		}
	}
	fmt.Printf("distinct: %d\n", len(counts))
	fmt.Printf("max: %d\n", max)
}
