// arm64 macOS: write(1, msg, len) then exit(0) via raw syscalls.
// Darwin syscall number goes in x16, trap with svc #0x80.
.global _start
.align 2

_start:
    mov     x0, #1                  // fd = stdout
    adrp    x1, msg@PAGE
    add     x1, x1, msg@PAGEOFF     // buf
    mov     x2, #14                 // len
    mov     x16, #4                 // SYS_write
    svc     #0x80

    mov     x0, #0                  // status
    mov     x16, #1                 // SYS_exit
    svc     #0x80

// read-only data: lives in the __TEXT page, so no extra __DATA page
.section __TEXT,__cstring
msg:
    .ascii  "Hello, World!\n"
