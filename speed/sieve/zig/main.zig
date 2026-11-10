const std = @import("std");

pub fn main(init: std.process.Init) !void {
    const args = try init.minimal.args.toSlice(init.arena.allocator());
    const n = try std.fmt.parseInt(usize, args[1], 10);
    const sieve = try init.gpa.alloc(u8, n + 1);
    defer init.gpa.free(sieve);
    @memset(sieve, 0);

    var i: usize = 2;
    while (i * i <= n) : (i += 1) {
        if (sieve[i] != 0) continue;
        var j = i * i;
        while (j <= n) : (j += i) sieve[j] = 1;
    }

    var count: usize = 0;
    var k: usize = 2;
    while (k <= n) : (k += 1) {
        if (sieve[k] == 0) count += 1;
    }

    var buf: [32]u8 = undefined;
    var stdout: std.Io.File.Writer = .init(.stdout(), init.io, &buf);
    try stdout.interface.print("{d}\n", .{count});
    try stdout.interface.flush();
}
