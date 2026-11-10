const std = @import("std");

pub fn main(init: std.process.Init) !void {
    const args = try init.minimal.args.toSlice(init.arena.allocator());
    const ops = try std.fmt.parseInt(u64, args[1], 10);
    const keys = try std.fmt.parseInt(u64, args[2], 10);

    var map = std.AutoHashMap(u64, u64).init(init.gpa);
    defer map.deinit();

    var x: u64 = 42;
    var i: u64 = 0;
    while (i < ops) : (i += 1) {
        x = (x *% 6364136223846793005) +% 1442695040888963407;
        const key = (x >> 33) % keys;
        const gop = try map.getOrPut(key);
        if (gop.found_existing) {
            gop.value_ptr.* +%= 1;
        } else {
            gop.value_ptr.* = 1;
        }
    }

    var max_count: u64 = 0;
    var it = map.valueIterator();
    while (it.next()) |value| {
        if (value.* > max_count) max_count = value.*;
    }

    var buf: [128]u8 = undefined;
    var stdout: std.Io.File.Writer = .init(.stdout(), init.io, &buf);
    try stdout.interface.print("distinct: {d}\n", .{map.count()});
    try stdout.interface.print("max: {d}\n", .{max_count});
    try stdout.interface.flush();
}
