function scrollDownSmooth(duration, steps, stepsLeft) {
    if (stepsLeft == 0) return;
    window.scrollBy(0, Math.max(document.documentElement.clientHeight, window.outerHeight || 0)/steps);

    setTimeout(() => { scrollDownSmooth(duration, steps, stepsLeft-1); }, duration / steps);
}

function scrollDown() {
    scrollDownSmooth(500, 50, 50);
}