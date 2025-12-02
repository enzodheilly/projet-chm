document.querySelector('.scroll-btn.left').onclick = () => {
    document.querySelector('.other-zones-slider').scrollBy({
        left: -300,
        behavior: 'smooth'
    });
};

document.querySelector('.scroll-btn.right').onclick = () => {
    document.querySelector('.other-zones-slider').scrollBy({
        left: 300,
        behavior: 'smooth'
    });
};