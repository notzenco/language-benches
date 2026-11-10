// arm64 macOS: count primes <= argv[1] with a one-byte-per-number sieve.
// dyld calls _start like main: x0 = argc, x1 = argv.
.global _start
.align 2

_start:
    // parse argv[1] as an unsigned decimal integer into x19
    ldr     x9, [x1, #8]
    mov     x19, #0
    mov     x11, #10
parse:
    ldrb    w10, [x9], #1
    sub     w10, w10, #48           // '0'
    cmp     w10, #9
    b.hi    parsed                  // NUL or non-digit ends the number
    madd    x19, x19, x11, x10      // x19 = x19 * 10 + digit
    b       parse
parsed:
    // calloc(n + 1, 1): one zeroed byte for each number in 0..n
    stp     x29, x30, [sp, #-16]!
    mov     x29, sp
    add     x0, x19, #1
    mov     x1, #1
    bl      _calloc
    cbz     x0, fail
    mov     x20, x0                 // sieve base

    mov     x21, #2                 // i = 2
outer:
    mul     x9, x21, x21            // i * i
    cmp     x9, x19
    b.hi    count                   // i * i > n
    ldrb    w10, [x20, x21]
    cbnz    w10, next_i             // already marked
    mov     x22, x9                 // j = i * i
mark:
    cmp     x22, x19
    b.hi    next_i                  // j > n
    mov     w10, #1
    strb    w10, [x20, x22]
    add     x22, x22, x21           // j += i
    b       mark
next_i:
    add     x21, x21, #1
    b       outer

count:
    mov     x0, #0                  // unmarked count
    mov     x9, #2
count_loop:
    cmp     x9, x19
    b.hi    print                   // k > n
    ldrb    w10, [x20, x9]
    cbnz    w10, count_next
    add     x0, x0, #1
count_next:
    add     x9, x9, #1
    b       count_loop

    // format x0 backwards into a 32-byte stack buffer, newline last
print:
    mov     x11, #10
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

fail:
    mov     x0, #1
    mov     x16, #1                 // SYS_exit
    svc     #0x80
