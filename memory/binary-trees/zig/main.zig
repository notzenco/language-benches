const std = @import("std");

const Node = struct {
    left: ?*Node,
    right: ?*Node,
};

fn makeTree(gpa: std.mem.Allocator, depth: u32) !*Node {
    const node = try gpa.create(Node);
    if (depth == 0) {
        node.* = .{ .left = null, .right = null };
    } else {
        node.* = .{
            .left = try makeTree(gpa, depth - 1),
            .right = try makeTree(gpa, depth - 1),
        };
    }
    return node;
}

fn freeTree(gpa: std.mem.Allocator, node: *Node) void {
    if (node.left) |left| freeTree(gpa, left);
    if (node.right) |right| freeTree(gpa, right);
    gpa.destroy(node);
}

fn check(node: ?*const Node) i64 {
    const n = node orelse return 0;
    return 1 + check(n.left) + check(n.right);
}

pub fn main(init: std.process.Init) !void {
    const args = try init.minimal.args.toSlice(init.arena.allocator());
    const n = try std.fmt.parseInt(u32, args[1], 10);
    const min_depth: u32 = 4;
    const max_depth = @max(min_depth + 2, n);
    const gpa = init.gpa;

    var buf: [256]u8 = undefined;
    var stdout: std.Io.File.Writer = .init(.stdout(), init.io, &buf);

    {
        const stretch = try makeTree(gpa, max_depth + 1);
        try stdout.interface.print("stretch tree of depth {d}\t check: {d}\n", .{ max_depth + 1, check(stretch) });
        freeTree(gpa, stretch);
    }

    const long_lived = try makeTree(gpa, max_depth);
    var depth: u32 = min_depth;
    while (depth <= max_depth) : (depth += 2) {
        const shift_amt: u5 = @intCast(max_depth - depth + min_depth);
        const iterations: u32 = @as(u32, 1) << shift_amt;
        var sum: i64 = 0;
        var i: u32 = 0;
        while (i < iterations) : (i += 1) {
            const tree = try makeTree(gpa, depth);
            sum += check(tree);
            freeTree(gpa, tree);
        }
        try stdout.interface.print("{d}\t trees of depth {d}\t check: {d}\n", .{ iterations, depth, sum });
    }
    try stdout.interface.print("long lived tree of depth {d}\t check: {d}\n", .{ max_depth, check(long_lived) });
    freeTree(gpa, long_lived);
    try stdout.interface.flush();
}
