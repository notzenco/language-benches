package main

import (
	"fmt"
	"os"
	"strconv"
)

func main() {
	n, err := strconv.Atoi(os.Args[1])
	if err != nil {
		panic(err)
	}
	sieve := make([]byte, n+1)

	for i := 2; i*i <= n; i++ {
		if sieve[i] != 0 {
			continue
		}
		for j := i * i; j <= n; j += i {
			sieve[j] = 1
		}
	}

	count := 0
	for k := 2; k <= n; k++ {
		if sieve[k] == 0 {
			count++
		}
	}
	fmt.Println(count)
}
