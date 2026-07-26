<template>
    <div aria-hidden="true" class="brand-pattern pointer-events-none absolute inset-x-0 top-0 z-0 h-[420px] opacity-[0.12] dark:opacity-[0.20]" />
</template>

<style scoped>
.brand-pattern {
    /* Two copies of the same crown, the second offset by half a cell in both
    axes, so alternating rows read as a staggered/brick pattern instead of a
    plain grid. Scaling the file itself down to 40px wide (height auto, to
    keep the file's own aspect ratio undistorted) leaves visible transparent
    margin around the crown — that margin is what creates the gap. */
    background-image: url('/brand/crown-single.png'), url('/brand/crown-single.png');
    background-repeat: repeat, repeat;
    /* Fixed pixel offset, not 50% 50% — percentages resolve against the
    element's own box size, not the tile size, so the stagger amount drifts
    with page/viewport width and rows end up unevenly spaced. Half of the
    40px x 33.42px cell (see background-size below) is a constant 20px /
    16.71px regardless of container size. */
    background-position:
        0 0,
        20px 16.71px;
    background-size:
        40px auto,
        40px auto;
    -webkit-mask-image: linear-gradient(to bottom, #000 0%, transparent 100%);
    mask-image: linear-gradient(to bottom, #000 0%, transparent 100%);
}
</style>
