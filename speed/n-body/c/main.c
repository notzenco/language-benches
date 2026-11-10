#include <math.h>
#include <stdio.h>
#include <stdlib.h>

#define NBODIES 5

struct body {
    double x, y, z, vx, vy, vz, mass;
};

static void offset_momentum(struct body *bodies, double solar_mass) {
    double px = 0.0, py = 0.0, pz = 0.0;
    for (int i = 0; i < NBODIES; i++) {
        px += bodies[i].vx * bodies[i].mass;
        py += bodies[i].vy * bodies[i].mass;
        pz += bodies[i].vz * bodies[i].mass;
    }
    bodies[0].vx = -px / solar_mass;
    bodies[0].vy = -py / solar_mass;
    bodies[0].vz = -pz / solar_mass;
}

static void advance(struct body *bodies, double dt) {
    for (int i = 0; i < NBODIES; i++) {
        for (int j = i + 1; j < NBODIES; j++) {
            double dx = bodies[i].x - bodies[j].x;
            double dy = bodies[i].y - bodies[j].y;
            double dz = bodies[i].z - bodies[j].z;
            double distance = sqrt(dx * dx + dy * dy + dz * dz);
            double mag = dt / (distance * distance * distance);
            double mi = bodies[i].mass;
            double mj = bodies[j].mass;
            bodies[i].vx -= dx * mj * mag;
            bodies[i].vy -= dy * mj * mag;
            bodies[i].vz -= dz * mj * mag;
            bodies[j].vx += dx * mi * mag;
            bodies[j].vy += dy * mi * mag;
            bodies[j].vz += dz * mi * mag;
        }
    }
    for (int i = 0; i < NBODIES; i++) {
        bodies[i].x += dt * bodies[i].vx;
        bodies[i].y += dt * bodies[i].vy;
        bodies[i].z += dt * bodies[i].vz;
    }
}

static double energy(struct body *bodies) {
    double e = 0.0;
    for (int i = 0; i < NBODIES; i++) {
        double vx = bodies[i].vx, vy = bodies[i].vy, vz = bodies[i].vz;
        e += 0.5 * bodies[i].mass * (vx * vx + vy * vy + vz * vz);
        for (int j = i + 1; j < NBODIES; j++) {
            double dx = bodies[i].x - bodies[j].x;
            double dy = bodies[i].y - bodies[j].y;
            double dz = bodies[i].z - bodies[j].z;
            double distance = sqrt(dx * dx + dy * dy + dz * dz);
            e -= (bodies[i].mass * bodies[j].mass) / distance;
        }
    }
    return e;
}

int main(int argc, char **argv) {
    if (argc < 2) return 1;
    long n = atol(argv[1]);

    const double pi = 3.141592653589793;
    const double solar_mass = 4 * pi * pi;
    const double days_per_year = 365.24;

    struct body bodies[NBODIES] = {
        {0, 0, 0, 0, 0, 0, solar_mass},
        {
            4.84143144246472090e+00,
            -1.16032004402742839e+00,
            -1.03622044471123109e-01,
            1.66007664274403694e-03 * days_per_year,
            7.69901118419740425e-03 * days_per_year,
            -6.90460016972063023e-05 * days_per_year,
            9.54791938424326609e-04 * solar_mass,
        },
        {
            8.34336671824457987e+00,
            4.12479856412430479e+00,
            -4.03523417114321381e-01,
            -2.76742510726862411e-03 * days_per_year,
            4.99852801234917238e-03 * days_per_year,
            2.30417297573763929e-05 * days_per_year,
            2.85885980666130812e-04 * solar_mass,
        },
        {
            1.28943695621391310e+01,
            -1.51111514016986312e+01,
            -2.23307578892655734e-01,
            2.96460137564761618e-03 * days_per_year,
            2.37847173959480950e-03 * days_per_year,
            -2.96589568540237556e-05 * days_per_year,
            4.36624404335156298e-05 * solar_mass,
        },
        {
            1.53796971148509165e+01,
            -2.59193146099879641e+01,
            1.79258772950371181e-01,
            2.68067772490389322e-03 * days_per_year,
            1.62824170038242295e-03 * days_per_year,
            -9.51592254519715870e-05 * days_per_year,
            5.15138902046611451e-05 * solar_mass,
        },
    };

    offset_momentum(bodies, solar_mass);
    printf("%.9f\n", energy(bodies));
    for (long step = 0; step < n; step++) advance(bodies, 0.01);
    printf("%.9f\n", energy(bodies));
    return 0;
}
