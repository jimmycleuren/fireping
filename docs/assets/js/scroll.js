function scrollDownSmooth(duration, steps) {

    let tot_height = document.getElementsByClassName("article__header--overlay")[0].offsetHeight;
    let left_height = tot_height - document.documentElement.scrollTop;
    stepsLeft = left_height>0 ? steps * (left_height/tot_height) : 0;

    // alert(tot_height + "\n" + left_height + "\n" + stepsLeft);

    if (stepsLeft == 0) return;

    window.scrollBy(0, tot_height/steps);

    setTimeout(() => { scrollDownSmooth(duration, steps); }, duration / steps);
}

function scrollDown() {
    scrollDownSmooth(500, 50);
}