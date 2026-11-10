fn main() {
    let n: i64 = std::env::args()
        .nth(1)
        .expect("usage: mandelbrot N")
        .parse()
        .expect("N must be an integer");
    let mut count: i64 = 0;

    for y in 0..n {
        for x in 0..n {
            let cr = 2.0 * x as f64 / n as f64 - 1.5;
            let ci = 2.0 * y as f64 / n as f64 - 1.0;
            let mut zr = 0.0;
            let mut zi = 0.0;
            let mut escaped = false;
            for _ in 0..50 {
                let nr = zr * zr - zi * zi + cr;
                let ni = 2.0 * zr * zi + ci;
                zr = nr;
                zi = ni;
                if zr * zr + zi * zi > 4.0 {
                    escaped = true;
                    break;
                }
            }
            if !escaped {
                count += 1;
            }
        }
    }
    println!("{}", count);
}
