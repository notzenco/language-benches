// The work lives in a function: top-level variables in main.swift are
// globals, and every global array access pays for exclusivity checks.
func countPrimes(upTo n: Int) -> Int {
    var sieve = [UInt8](repeating: 0, count: n + 1)

    var i = 2
    while i * i <= n {
        if sieve[i] == 0 {
            var j = i * i
            while j <= n {
                sieve[j] = 1
                j += i
            }
        }
        i += 1
    }

    var count = 0
    for k in 2...n where sieve[k] == 0 {
        count += 1
    }
    return count
}

print(countPrimes(upTo: Int(CommandLine.arguments[1])!))
