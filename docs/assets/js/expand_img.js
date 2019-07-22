// Select all images
let images = document.getElementsByTagName('img');

// let overlay_div = document.createElement('div');
// overlay_div.attributes.id = "overlay";
// document.body.appendChild(overlay_div);

// let close_btn = document.createElement('button');
// close_btn.innerHTML = '<i class="fas fa-times"></i>';
// overlay_div.appendChild(close_btn);

for (var i = 0, count = images.length; i < count; ++i)
{
    let image = images[i]

    image.onclick(() => {
        console.log(image.attributes.src);
    });
}