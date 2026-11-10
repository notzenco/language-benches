// arm64 macOS: print fib(argv[1]) using the naive recursive algorithm.
// dyld calls _start like main: x0 = argc, x1 = argv.
.global _start
.align 2

_start:
    // parse argv[1] as an unsigned decimal integer into x0
    ldr     x9, [x1, #8]
    mov     x0, #0
    mov     x11, #10
parse:
    ldrb    w10, [x9], #1
    sub     w10, w10, #48           // '0'
    cmp     w10, #9
    b.hi    parsed                  // NUL or non-digit ends the number
    madd    x0, x0, x11, x10        // x0 = x0 * 10 + digit
    b       parse
parsed:
    bl      fib

    // format x0 backwards into a 32-byte stack buffer, newline last
    sub     sp, sp, #32
    add     x9, sp, #32
    mov     w10, #10
    strb    w10, [x9, #-1]!
digit:
    udiv    x12, x0, x11
    msub    x13, x12, x11, x0       // x13 = x0 % 10
    add     w13, w13, #48
    strb    w13, [x9, #-1]!
    mov     x0, x12
    cbnz    x0, digit

    mov     x0, #1                  // write(stdout, x9, end - x9)
    mov     x1, x9
    add     x2, sp, #32
    sub     x2, x2, x9
    mov     x16, #4                 // SYS_write
    svc     #0x80

    mov     x0, #0
    mov     x16, #1                 // SYS_exit
    svc     #0x80

// x0 = n  ->  x0 = fib(n)
fib:
    cmp     x0, #2
    b.lt    1f                      // n < 2: return n
    stp     x29, x30, [sp, #-32]!
    mov     x29, sp
    stp     x19, x20, [sp, #16]
    mov     x19, x0
    sub     x0, x0, #1
    bl      fib                     // fib(n - 1)
    mov     x20, x0
    sub     x0, x19, #2
    bl      fib                     // fib(n - 2)
    add     x0, x0, x20
    ldp     x19, x20, [sp, #16]
    ldp     x29, x30, [sp], #32
1:  ret
