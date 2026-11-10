struct Body {
    x: f64,
    y: f64,
    z: f64,
    vx: f64,
    vy: f64,
    vz: f64,
    mass: f64,
}

fn offset_momentum(bodies: &mut [Body], solar_mass: f64) {
    let mut px = 0.0;
    let mut py = 0.0;
    let mut pz = 0.0;
    for b in bodies.iter() {
        px += b.vx * b.mass;
        py += b.vy * b.mass;
        pz += b.vz * b.mass;
    }
    bodies[0].vx = -px / solar_mass;
    bodies[0].vy = -py / solar_mass;
    bodies[0].vz = -pz / solar_mass;
}

fn advance(bodies: &mut [Body], dt: f64) {
    let n = bodies.len();
    for i in 0..n {
        for j in (i + 1)..n {
            let dx = bodies[i].x - bodies[j].x;
            let dy = bodies[i].y - bodies[j].y;
            let dz = bodies[i].z - bodies[j].z;
            let distance = (dx * dx + dy * dy + dz * dz).sqrt();
            let mag = dt / (distance * distance * distance);
            let mi = bodies[i].mass;
            let mj = bodies[j].mass;
            bodies[i].vx -= dx * mj * mag;
            bodies[i].vy -= dy * mj * mag;
            bodies[i].vz -= dz * mj * mag;
            bodies[j].vx += dx * mi * mag;
            bodies[j].vy += dy * mi * mag;
            bodies[j].vz += dz * mi * mag;
        }
    }
    for b in bodies.iter_mut() {
        b.x += dt * b.vx;
        b.y += dt * b.vy;
        b.z += dt * b.vz;
    }
}

fn energy(bodies: &[Body]) -> f64 {
    let n = bodies.len();
    let mut e = 0.0;
    for i in 0..n {
        let vx = bodies[i].vx;
        let vy = bodies[i].vy;
        let vz = bodies[i].vz;
        e += 0.5 * bodies[i].mass * (vx * vx + vy * vy + vz * vz);
        for j in (i + 1)..n {
            let dx = bodies[i].x - bodies[j].x;
            let dy = bodies[i].y - bodies[j].y;
            let dz = bodies[i].z - bodies[j].z;
            let distance = (dx * dx + dy * dy + dz * dz).sqrt();
            e -= (bodies[i].mass * bodies[j].mass) / distance;
        }
    }
    e
}

fn main() {
    let n: i64 = std::env::args()
        .nth(1)
        .expect("usage: n-body N")
        .parse()
        .expect("N must be an integer");

    let pi = 3.141592653589793_f64;
    let solar_mass = 4.0 * pi * pi;
    let days_per_year = 365.24_f64;

    let mut bodies = [
        Body { x: 0.0, y: 0.0, z: 0.0, vx: 0.0, vy: 0.0, vz: 0.0, mass: solar_mass },
        Body {
            x: 4.84143144246472090e+00,
            y: -1.16032004402742839e+00,
            z: -1.03622044471123109e-01,
            vx: 1.66007664274403694e-03 * days_per_year,
            vy: 7.69901118419740425e-03 * days_per_year,
            vz: -6.90460016972063023e-05 * days_per_year,
            mass: 9.54791938424326609e-04 * solar_mass,
        },
        Body {
            x: 8.34336671824457987e+00,
            y: 4.12479856412430479e+00,
            z: -4.03523417114321381e-01,
            vx: -2.76742510726862411e-03 * days_per_year,
            vy: 4.99852801234917238e-03 * days_per_year,
            vz: 2.30417297573763929e-05 * days_per_year,
            mass: 2.85885980666130812e-04 * solar_mass,
        },
        Body {
            x: 1.28943695621391310e+01,
            y: -1.51111514016986312e+01,
            z: -2.23307578892655734e-01,
            vx: 2.96460137564761618e-03 * days_per_year,
            vy: 2.37847173959480950e-03 * days_per_year,
            vz: -2.96589568540237556e-05 * days_per_year,
            mass: 4.36624404335156298e-05 * solar_mass,
        },
        Body {
            x: 1.53796971148509165e+01,
            y: -2.59193146099879641e+01,
            z: 1.79258772950371181e-01,
            vx: 2.68067772490389322e-03 * days_per_year,
            vy: 1.62824170038242295e-03 * days_per_year,
            vz: -9.51592254519715870e-05 * days_per_year,
            mass: 5.15138902046611451e-05 * solar_mass,
        },
    ];

    offset_momentum(&mut bodies, solar_mass);
    println!("{:.9}", energy(&bodies));
    for _ in 0..n {
        advance(&mut bodies, 0.01);
    }
    println!("{:.9}", energy(&bodies));
}
