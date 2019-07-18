function scrollDownSmooth(duration, steps, stepsLeft) {
    if (stepsLeft == 0) return;
    window.scrollBy(0, Math.max(document.documentElement.clientHeight, window.outerHeight || 0)/steps);

    if(stepsLeft > steps/2) duration-=1;
    else duration+=1;

    if (duration < 0) duration = 0;

    setTimeout(() => { scrollDownSmooth(duration, steps, stepsLeft-1); }, duration / steps);
}

function scrollDown() {
    scrollDownSmooth(500, 50, 50);
}