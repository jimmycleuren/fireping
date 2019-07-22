// Select all images
let images = document.getElementsByTagName('img');

for (var i = 0, count = images.length; i < count; ++i)
{
    let image = images[i];
    image.onclick = () => {
        console.log(image.src);
    };
}