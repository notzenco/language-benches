package main

import (
	"fmt"
	"math"
	"os"
	"strconv"
)

const nbodies = 5

type body struct {
	x, y, z, vx, vy, vz, mass float64
}

func offsetMomentum(bodies []body, solarMass float64) {
	px, py, pz := 0.0, 0.0, 0.0
	for i := 0; i < nbodies; i++ {
		px += bodies[i].vx * bodies[i].mass
		py += bodies[i].vy * bodies[i].mass
		pz += bodies[i].vz * bodies[i].mass
	}
	bodies[0].vx = -px / solarMass
	bodies[0].vy = -py / solarMass
	bodies[0].vz = -pz / solarMass
}

func advance(bodies []body, dt float64) {
	for i := 0; i < nbodies; i++ {
		for j := i + 1; j < nbodies; j++ {
			dx := bodies[i].x - bodies[j].x
			dy := bodies[i].y - bodies[j].y
			dz := bodies[i].z - bodies[j].z
			distance := math.Sqrt(dx*dx + dy*dy + dz*dz)
			mag := dt / (distance * distance * distance)
			mi := bodies[i].mass
			mj := bodies[j].mass
			bodies[i].vx -= dx * mj * mag
			bodies[i].vy -= dy * mj * mag
			bodies[i].vz -= dz * mj * mag
			bodies[j].vx += dx * mi * mag
			bodies[j].vy += dy * mi * mag
			bodies[j].vz += dz * mi * mag
		}
	}
	for i := 0; i < nbodies; i++ {
		bodies[i].x += dt * bodies[i].vx
		bodies[i].y += dt * bodies[i].vy
		bodies[i].z += dt * bodies[i].vz
	}
}

func energy(bodies []body) float64 {
	e := 0.0
	for i := 0; i < nbodies; i++ {
		vx, vy, vz := bodies[i].vx, bodies[i].vy, bodies[i].vz
		e += 0.5 * bodies[i].mass * (vx*vx + vy*vy + vz*vz)
		for j := i + 1; j < nbodies; j++ {
			dx := bodies[i].x - bodies[j].x
			dy := bodies[i].y - bodies[j].y
			dz := bodies[i].z - bodies[j].z
			distance := math.Sqrt(dx*dx + dy*dy + dz*dz)
			e -= (bodies[i].mass * bodies[j].mass) / distance
		}
	}
	return e
}

func main() {
	n, err := strconv.ParseInt(os.Args[1], 10, 64)
	if err != nil {
		panic(err)
	}

	pi := 3.141592653589793
	solarMass := 4 * pi * pi
	daysPerYear := 365.24

	bodies := []body{
		{0, 0, 0, 0, 0, 0, solarMass},
		{
			4.84143144246472090e+00,
			-1.16032004402742839e+00,
			-1.03622044471123109e-01,
			1.66007664274403694e-03 * daysPerYear,
			7.69901118419740425e-03 * daysPerYear,
			-6.90460016972063023e-05 * daysPerYear,
			9.54791938424326609e-04 * solarMass,
		},
		{
			8.34336671824457987e+00,
			4.12479856412430479e+00,
			-4.03523417114321381e-01,
			-2.76742510726862411e-03 * daysPerYear,
			4.99852801234917238e-03 * daysPerYear,
			2.30417297573763929e-05 * daysPerYear,
			2.85885980666130812e-04 * solarMass,
		},
		{
			1.28943695621391310e+01,
			-1.51111514016986312e+01,
			-2.23307578892655734e-01,
			2.96460137564761618e-03 * daysPerYear,
			2.37847173959480950e-03 * daysPerYear,
			-2.96589568540237556e-05 * daysPerYear,
			4.36624404335156298e-05 * solarMass,
		},
		{
			1.53796971148509165e+01,
			-2.59193146099879641e+01,
			1.79258772950371181e-01,
			2.68067772490389322e-03 * daysPerYear,
			1.62824170038242295e-03 * daysPerYear,
			-9.51592254519715870e-05 * daysPerYear,
			5.15138902046611451e-05 * solarMass,
		},
	}

	offsetMomentum(bodies, solarMass)
	fmt.Printf("%.9f\n", energy(bodies))
	for step := int64(0); step < n; step++ {
		advance(bodies, 0.01)
	}
	fmt.Printf("%.9f\n", energy(bodies))
}
