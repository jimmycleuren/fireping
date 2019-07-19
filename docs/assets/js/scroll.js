function scrollDownSmooth(duration, steps, stepsLeft) {
    if (stepsLeft == 0) return;
    let element = document.getElementsByClassName("article__header--overlay")[0];
    window.scrollBy(0, element.clientHeight/steps);

    setTimeout(() => { scrollDownSmooth(duration, steps, stepsLeft-1); }, duration / steps);
}

function scrollDown() {
    scrollDownSmooth(500, 50, 50);
}