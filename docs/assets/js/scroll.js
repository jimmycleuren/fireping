function scrollDown(delay, steps, stepsLeft) {
    if (stepsLeft == 0) return;
    stepsLeft = stepsLeft || steps;
    delay = delay || 16;
    steps = steps || 50;
    window.scrollBy(0, window.outerHeight/steps);
    setTimeout(() => { scrollDown(delay, steps, stepsLeft-1) }, delay);
}