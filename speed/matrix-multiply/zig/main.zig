const std = @import("std");

pub fn main(init: std.process.Init) !void {
    const args = try init.minimal.args.toSlice(init.arena.allocator());
    const n = try std.fmt.parseInt(usize, args[1], 10);
    const nn = n * n;
    const a = try init.gpa.alloc(f64, nn);
    defer init.gpa.free(a);
    const b = try init.gpa.alloc(f64, nn);
    defer init.gpa.free(b);
    const c = try init.gpa.alloc(f64, nn);
    defer init.gpa.free(c);
    @memset(c, 0);

    for (0..n) |i| {
        for (0..n) |j| {
            a[i * n + j] = @floatFromInt((i + j) % 10);
            b[i * n + j] = @floatFromInt((i * j) % 10);
        }
    }

    for (0..n) |i| {
        for (0..n) |k| {
            const aik = a[i * n + k];
            for (0..n) |j| {
                c[i * n + j] += aik * b[k * n + j];
            }
        }
    }

    var sum: i64 = 0;
    for (c) |v| sum += @intFromFloat(v);

    var buf: [32]u8 = undefined;
    var stdout: std.Io.File.Writer = .init(.stdout(), init.io, &buf);
    try stdout.interface.print("{d}\n", .{sum});
    try stdout.interface.flush();
}
