// The work lives in a function: top-level variables in main.swift are
// globals, and every global access pays for exclusivity checks.
func countKeys(ops: UInt64, keys: UInt64) -> (distinct: Int, max: UInt64) {
    var x: UInt64 = 42
    var counts: [UInt64: UInt64] = [:]
    var i: UInt64 = 0
    while i < ops {
        x = (x &* 6364136223846793005) &+ 1442695040888963407
        let key = (x >> 33) % keys
        counts[key, default: 0] += 1
        i += 1
    }

    var maxCount: UInt64 = 0
    for value in counts.values {
        if value > maxCount {
            maxCount = value
        }
    }
    return (counts.count, maxCount)
}

let result = countKeys(ops: UInt64(CommandLine.arguments[1])!, keys: UInt64(CommandLine.arguments[2])!)
print("distinct: \(result.distinct)")
print("max: \(result.max)")
