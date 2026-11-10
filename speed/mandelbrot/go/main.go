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
	count := 0

	for y := 0; y < n; y++ {
		for x := 0; x < n; x++ {
			cr := 2.0*float64(x)/float64(n) - 1.5
			ci := 2.0*float64(y)/float64(n) - 1.0
			zr := 0.0
			zi := 0.0
			escaped := false
			for i := 0; i < 50; i++ {
				nr := zr*zr - zi*zi + cr
				ni := 2.0*zr*zi + ci
				zr = nr
				zi = ni
				if zr*zr+zi*zi > 4.0 {
					escaped = true
					break
				}
			}
			if !escaped {
				count++
			}
		}
	}
	fmt.Println(count)
}
