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
	nn := n * n
	a := make([]float64, nn)
	b := make([]float64, nn)
	c := make([]float64, nn)

	for i := 0; i < n; i++ {
		for j := 0; j < n; j++ {
			a[i*n+j] = float64((i + j) % 10)
			b[i*n+j] = float64((i * j) % 10)
		}
	}

	for i := 0; i < n; i++ {
		for k := 0; k < n; k++ {
			aik := a[i*n+k]
			for j := 0; j < n; j++ {
				c[i*n+j] += aik * b[k*n+j]
			}
		}
	}

	sum := int64(0)
	for _, v := range c {
		sum += int64(v)
	}
	fmt.Println(sum)
}
