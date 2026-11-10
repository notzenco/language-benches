fn fib(n: i64) -> i64 {
    if n < 2 { n } else { fib(n - 1) + fib(n - 2) }
}

fn main() {
    let n: i64 = std::env::args().nth(1).expect("usage: fib N").parse().expect("N must be an integer");
    println!("{}", fib(n));
}
