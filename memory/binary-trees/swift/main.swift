final class TreeNode {
    let left: TreeNode?
    let right: TreeNode?

    init(left: TreeNode?, right: TreeNode?) {
        self.left = left
        self.right = right
    }
}

func makeTree(_ depth: Int) -> TreeNode {
    if depth == 0 {
        return TreeNode(left: nil, right: nil)
    }
    return TreeNode(left: makeTree(depth - 1), right: makeTree(depth - 1))
}

func check(_ node: TreeNode?) -> Int64 {
    guard let node else { return 0 }
    return 1 + check(node.left) + check(node.right)
}

let n = Int(CommandLine.arguments[1])!
let minDepth = 4
let maxDepth = max(minDepth + 2, n)

do {
    let stretch = makeTree(maxDepth + 1)
    print("stretch tree of depth \(maxDepth + 1)\t check: \(check(stretch))")
}

let longLived = makeTree(maxDepth)
var depth = minDepth
while depth <= maxDepth {
    let iterations = 1 << (maxDepth - depth + minDepth)
    var sum: Int64 = 0
    for _ in 0..<iterations {
        let tree = makeTree(depth)
        sum += check(tree)
    }
    print("\(iterations)\t trees of depth \(depth)\t check: \(sum)")
    depth += 2
}
print("long lived tree of depth \(maxDepth)\t check: \(check(longLived))")
