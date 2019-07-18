function scrollDown(delay, steps) {
    window.scrollBy(0, window.innerHeight/steps);
    setTimeout(scrollDown(delay, steps-1), delay);
}