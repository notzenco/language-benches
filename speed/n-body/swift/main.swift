import Darwin

struct Body {
    var x, y, z, vx, vy, vz, mass: Double
}

func offsetMomentum(_ bodies: inout [Body], solarMass: Double) {
    var px = 0.0, py = 0.0, pz = 0.0
    for b in bodies {
        px += b.vx * b.mass
        py += b.vy * b.mass
        pz += b.vz * b.mass
    }
    bodies[0].vx = -px / solarMass
    bodies[0].vy = -py / solarMass
    bodies[0].vz = -pz / solarMass
}

func advance(_ bodies: inout [Body], dt: Double) {
    let n = bodies.count
    for i in 0..<n {
        for j in (i + 1)..<n {
            let dx = bodies[i].x - bodies[j].x
            let dy = bodies[i].y - bodies[j].y
            let dz = bodies[i].z - bodies[j].z
            let distance = (dx * dx + dy * dy + dz * dz).squareRoot()
            let mag = dt / (distance * distance * distance)
            let mi = bodies[i].mass
            let mj = bodies[j].mass
            bodies[i].vx -= dx * mj * mag
            bodies[i].vy -= dy * mj * mag
            bodies[i].vz -= dz * mj * mag
            bodies[j].vx += dx * mi * mag
            bodies[j].vy += dy * mi * mag
            bodies[j].vz += dz * mi * mag
        }
    }
    for i in 0..<n {
        bodies[i].x += dt * bodies[i].vx
        bodies[i].y += dt * bodies[i].vy
        bodies[i].z += dt * bodies[i].vz
    }
}

func energy(_ bodies: [Body]) -> Double {
    let n = bodies.count
    var e = 0.0
    for i in 0..<n {
        let vx = bodies[i].vx, vy = bodies[i].vy, vz = bodies[i].vz
        e += 0.5 * bodies[i].mass * (vx * vx + vy * vy + vz * vz)
        for j in (i + 1)..<n {
            let dx = bodies[i].x - bodies[j].x
            let dy = bodies[i].y - bodies[j].y
            let dz = bodies[i].z - bodies[j].z
            let distance = (dx * dx + dy * dy + dz * dz).squareRoot()
            e -= (bodies[i].mass * bodies[j].mass) / distance
        }
    }
    return e
}

// Swift cannot call variadic snprintf. vsnprintf plus withVaList is printf "%.9f".
func printFixed9(_ value: Double) {
    var buf = [CChar](repeating: 0, count: 64)
    buf.withUnsafeMutableBufferPointer { ptr in
        _ = withVaList([value]) { args in
            vsnprintf(ptr.baseAddress, ptr.count, "%.9f", args)
        }
    }
    print(String(cString: buf))
}

// The simulation runs in a function: top-level variables in main.swift are
// globals, and passing a global inout pays for exclusivity checks per call.
func run(steps n: Int) {
    let pi = 3.141592653589793
    let solarMass = 4.0 * pi * pi
    let daysPerYear = 365.24

    var bodies = [
        Body(x: 0, y: 0, z: 0, vx: 0, vy: 0, vz: 0, mass: solarMass),
        Body(
            x: 4.84143144246472090e+00,
            y: -1.16032004402742839e+00,
            z: -1.03622044471123109e-01,
            vx: 1.66007664274403694e-03 * daysPerYear,
            vy: 7.69901118419740425e-03 * daysPerYear,
            vz: -6.90460016972063023e-05 * daysPerYear,
            mass: 9.54791938424326609e-04 * solarMass
        ),
        Body(
            x: 8.34336671824457987e+00,
            y: 4.12479856412430479e+00,
            z: -4.03523417114321381e-01,
            vx: -2.76742510726862411e-03 * daysPerYear,
            vy: 4.99852801234917238e-03 * daysPerYear,
            vz: 2.30417297573763929e-05 * daysPerYear,
            mass: 2.85885980666130812e-04 * solarMass
        ),
        Body(
            x: 1.28943695621391310e+01,
            y: -1.51111514016986312e+01,
            z: -2.23307578892655734e-01,
            vx: 2.96460137564761618e-03 * daysPerYear,
            vy: 2.37847173959480950e-03 * daysPerYear,
            vz: -2.96589568540237556e-05 * daysPerYear,
            mass: 4.36624404335156298e-05 * solarMass
        ),
        Body(
            x: 1.53796971148509165e+01,
            y: -2.59193146099879641e+01,
            z: 1.79258772950371181e-01,
            vx: 2.68067772490389322e-03 * daysPerYear,
            vy: 1.62824170038242295e-03 * daysPerYear,
            vz: -9.51592254519715870e-05 * daysPerYear,
            mass: 5.15138902046611451e-05 * solarMass
        ),
    ]

    offsetMomentum(&bodies, solarMass: solarMass)
    printFixed9(energy(bodies))
    for _ in 0..<n {
        advance(&bodies, dt: 0.01)
    }
    printFixed9(energy(bodies))
}

run(steps: Int(CommandLine.arguments[1])!)
