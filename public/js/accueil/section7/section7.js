const slides = document.querySelectorAll('.ambassador-card');
let currentIndex = 0;

function showSlide(index) {
    slides.forEach((slide, i) => {
        slide.classList.toggle('active', i === index);

        const prevBtn = slide.querySelector('.slider-prev');
        const nextBtn = slide.querySelector('.slider-next');

        // Slide 1 : cacher prev
        if(prevBtn) prevBtn.style.display = (i === 0) ? 'none' : 'block';
        // Dernière slide : cacher next
        if(nextBtn) nextBtn.style.display = (i === slides.length - 1) ? 'none' : 'block';
    });

    // Dots
    const dots = document.querySelectorAll('.slider-dot');
    dots.forEach((dot, i) => {
        dot.classList.toggle('active', i === index);
    });
}

// Événements
slides.forEach((slide, i) => {
    const prevBtn = slide.querySelector('.slider-prev');
    const nextBtn = slide.querySelector('.slider-next');

    if(prevBtn) prevBtn.addEventListener('click', () => {
        if(currentIndex > 0){
            currentIndex--;
            showSlide(currentIndex);
        }
    });

    if(nextBtn) nextBtn.addEventListener('click', () => {
        if(currentIndex < slides.length - 1){
            currentIndex++;
            showSlide(currentIndex);
        }
    });
});

// Initialisation
showSlide(currentIndex);
