fn main() {
    let n: usize = std::env::args()
        .nth(1)
        .expect("usage: matrix-multiply N")
        .parse()
        .expect("N must be an integer");
    let nn = n * n;
    let mut a = vec![0.0f64; nn];
    let mut b = vec![0.0f64; nn];
    let mut c = vec![0.0f64; nn];

    for i in 0..n {
        for j in 0..n {
            a[i * n + j] = ((i + j) % 10) as f64;
            b[i * n + j] = ((i * j) % 10) as f64;
        }
    }

    for i in 0..n {
        for k in 0..n {
            let aik = a[i * n + k];
            for j in 0..n {
                c[i * n + j] += aik * b[k * n + j];
            }
        }
    }

    let mut sum: i64 = 0;
    for v in &c {
        sum += *v as i64;
    }
    println!("{}", sum);
}
