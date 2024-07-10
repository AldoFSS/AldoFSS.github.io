function playVideo() {
    var popupVideo = document.querySelector('.content-header .popup-video');
    popupVideo.style.display = 'block';
    document.getElementById('video').play();
}

function closeVideo() {
    var popupVideo = document.querySelector('.content-header .popup-video');
    popupVideo.style.display = 'none';
    video.currentTime = 0;
}

document.getElementById('playvideo').addEventListener('click', playVideo);

