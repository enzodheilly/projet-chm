const slides = document.querySelectorAll('.ambassador-card');
const nextBtn = document.querySelector('.slider-next');
const prevBtn = document.querySelector('.slider-prev');
const dots = document.querySelectorAll('.slider-dot');

let currentIndex = 0;

function showSlide(index) {
    slides.forEach((slide, i) => {
        slide.classList.toggle('active', i === index);
        dots[i].classList.toggle('active', i === index);
    });

    // Gestion boutons
    if (index === 0) {
        prevBtn.classList.remove('show');
    } else {
        prevBtn.classList.add('show');
    }
    nextBtn.classList.add('show'); // toujours visible
}

// Événements
nextBtn.addEventListener('click', () => {
    currentIndex = (currentIndex + 1) % slides.length;
    showSlide(currentIndex);
});

prevBtn.addEventListener('click', () => {
    currentIndex = (currentIndex - 1 + slides.length) % slides.length;
    showSlide(currentIndex);
});

// Initialisation
showSlide(currentIndex);
