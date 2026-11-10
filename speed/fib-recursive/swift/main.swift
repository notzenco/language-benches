func fib(_ n: Int64) -> Int64 {
    n < 2 ? n : fib(n - 1) + fib(n - 2)
}

let n = Int64(CommandLine.arguments[1])!
print(fib(n))
