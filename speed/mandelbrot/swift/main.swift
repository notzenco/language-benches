// The work lives in a function: top-level variables in main.swift are
// globals, and globals pay for exclusivity checks on every access.
func mandelbrotCount(_ n: Int) -> Int {
    var count = 0

    for y in 0..<n {
        for x in 0..<n {
            let cr = 2.0 * Double(x) / Double(n) - 1.5
            let ci = 2.0 * Double(y) / Double(n) - 1.0
            var zr = 0.0
            var zi = 0.0
            var escaped = false
            for _ in 0..<50 {
                let nr = zr * zr - zi * zi + cr
                let ni = 2.0 * zr * zi + ci
                zr = nr
                zi = ni
                if zr * zr + zi * zi > 4.0 {
                    escaped = true
                    break
                }
            }
            if !escaped {
                count += 1
            }
        }
    }
    return count
}

print(mandelbrotCount(Int(CommandLine.arguments[1])!))
