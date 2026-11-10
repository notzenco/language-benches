struct Node {
    left: Option<Box<Node>>,
    right: Option<Box<Node>>,
}

fn make_tree(depth: i32) -> Box<Node> {
    if depth == 0 {
        Box::new(Node { left: None, right: None })
    } else {
        Box::new(Node {
            left: Some(make_tree(depth - 1)),
            right: Some(make_tree(depth - 1)),
        })
    }
}

fn check(node: &Node) -> i64 {
    let left = node.left.as_deref().map(check).unwrap_or(0);
    let right = node.right.as_deref().map(check).unwrap_or(0);
    1 + left + right
}

fn main() {
    let n: i32 = std::env::args()
        .nth(1)
        .expect("usage: binary-trees N")
        .parse()
        .expect("N must be an integer");
    let min_depth = 4;
    let max_depth = if n > min_depth + 2 { n } else { min_depth + 2 };

    {
        let stretch = make_tree(max_depth + 1);
        println!("stretch tree of depth {}\t check: {}", max_depth + 1, check(&stretch));
    }

    let long_lived = make_tree(max_depth);
    let mut d = min_depth;
    while d <= max_depth {
        let iterations = 1i64 << ((max_depth - d + min_depth) as u32);
        let mut sum = 0i64;
        for _ in 0..iterations {
            let tree = make_tree(d);
            sum += check(&tree);
        }
        println!("{iterations}\t trees of depth {d}\t check: {sum}");
        d += 2;
    }
    println!("long lived tree of depth {max_depth}\t check: {}", check(&long_lived));
}
