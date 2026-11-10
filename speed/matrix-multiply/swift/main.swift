// The work lives in a function: top-level variables in main.swift are
// globals, and every global array access pays for exclusivity checks.
func multiply(_ n: Int) -> Int {
    let nn = n * n
    var a = [Double](repeating: 0, count: nn)
    var b = [Double](repeating: 0, count: nn)
    var c = [Double](repeating: 0, count: nn)

    for i in 0..<n {
        let row = i * n
        for j in 0..<n {
            a[row + j] = Double((i + j) % 10)
            b[row + j] = Double((i * j) % 10)
        }
    }

    for i in 0..<n {
        let row = i * n
        for k in 0..<n {
            let aik = a[row + k]
            let brow = k * n
            for j in 0..<n {
                c[row + j] += aik * b[brow + j]
            }
        }
    }

    var sum = 0
    for v in c {
        sum += Int(v)
    }
    return sum
}

print(multiply(Int(CommandLine.arguments[1])!))
