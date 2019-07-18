function scrollDownSmooth(duration, steps, stepsLeft) {
    if (stepsLeft == 0) return;
    window.scrollBy(0, Math.max(document.documentElement.clientHeight, window.innerHeight || 0)/steps);
    setTimeout(() => { scrollDown(delay, steps, stepsLeft-1) }, duration / steps);
}

function scrollDown() {
    scrollDownSmooth(500, 50, 50);
}