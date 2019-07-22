// Select all images
let images = document.getElementsByTagName('img');

for (var i = 0, count = images.length; i < count; ++i)
{
    images[i].attributes.onclick = "expandImage(" + images[i].src + ")";
}

function expandImage(src) {

}