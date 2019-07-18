function scrollDown() {
    scrollDownSmooth(16, 50, 50);
}

function scrollDownSmooth(delay, steps, stepsLeft) {
    if (stepsLeft == 0) return;
    window.scrollBy(0, window.outerHeight/steps);
    setTimeout(() => { scrollDown(delay, steps, stepsLeft-1) }, delay);
}