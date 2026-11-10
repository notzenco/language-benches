use std::collections::HashMap;

fn main() {
    let mut args = std::env::args();
    args.next();
    let ops: u64 = args
        .next()
        .expect("usage: hash-map OPS KEYS")
        .parse()
        .expect("OPS must be an integer");
    let keys: u64 = args
        .next()
        .expect("usage: hash-map OPS KEYS")
        .parse()
        .expect("KEYS must be an integer");

    let mut x: u64 = 42;
    let mut counts: HashMap<u64, u64> = HashMap::new();
    for _ in 0..ops {
        x = x
            .wrapping_mul(6364136223846793005)
            .wrapping_add(1442695040888963407);
        let key = (x >> 33) % keys;
        *counts.entry(key).or_insert(0) += 1;
    }

    let max_count = counts.values().copied().max().unwrap_or(0);
    println!("distinct: {}", counts.len());
    println!("max: {max_count}");
}
