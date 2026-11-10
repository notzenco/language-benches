fn main() {
    let n: usize = std::env::args()
        .nth(1)
        .expect("usage: sieve N")
        .parse()
        .expect("N must be an integer");
    let mut sieve = vec![0u8; n + 1];

    let mut i = 2usize;
    while i * i <= n {
        if sieve[i] == 0 {
            let mut j = i * i;
            while j <= n {
                sieve[j] = 1;
                j += i;
            }
        }
        i += 1;
    }

    let mut count = 0usize;
    for k in 2..=n {
        if sieve[k] == 0 {
            count += 1;
        }
    }
    println!("{}", count);
}
