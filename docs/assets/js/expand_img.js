// Select all images
let images = document.getElementsByTagName('img');
let overlay = document.getElementById('image-overlay');

// set onclick for close button
overlay.getElementsByTagName('button')[0].onclick = () => {
    overlay.style.display = "none";
    document.body.classList.remove("noscroll");
}

for (var i = 0, count = images.length; i < count; ++i)
{
    let image = images[i];
    image.onclick = () => {
        overlay.style.display = "grid";
        document.body.classList.add("noscroll");
        let overlay_image = overlay.getElementsByTagName('img')[0];
        overlay_image.src = image.src;
    };
}