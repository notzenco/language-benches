const std = @import("std");

pub fn main(init: std.process.Init) !void {
    const args = try init.minimal.args.toSlice(init.arena.allocator());
    const n = try std.fmt.parseInt(usize, args[1], 10);
    var count: usize = 0;

    for (0..n) |y| {
        for (0..n) |x| {
            const cr = 2.0 * @as(f64, @floatFromInt(x)) / @as(f64, @floatFromInt(n)) - 1.5;
            const ci = 2.0 * @as(f64, @floatFromInt(y)) / @as(f64, @floatFromInt(n)) - 1.0;
            var zr: f64 = 0.0;
            var zi: f64 = 0.0;
            var escaped = false;
            var i: usize = 0;
            while (i < 50) : (i += 1) {
                const nr = zr * zr - zi * zi + cr;
                const ni = 2.0 * zr * zi + ci;
                zr = nr;
                zi = ni;
                if (zr * zr + zi * zi > 4.0) {
                    escaped = true;
                    break;
                }
            }
            if (!escaped) count += 1;
        }
    }

    var buf: [32]u8 = undefined;
    var stdout: std.Io.File.Writer = .init(.stdout(), init.io, &buf);
    try stdout.interface.print("{d}\n", .{count});
    try stdout.interface.flush();
}
