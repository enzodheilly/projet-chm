
																											const slider = document.querySelector('.slider');
																											const slides = document.querySelectorAll('.slide');
																											const leftBtn = document.querySelector('.nav-button.left');
																											const rightBtn = document.querySelector('.nav-button.right');
																											
																											let currentIndex = 0;
																											
																											function getSlidesToShow() {
																											    if (window.innerWidth <= 768) return 1;   // mobile
																											    if (window.innerWidth <= 1024) return 2;  // tablette
																											    return 3;                                 // desktop
																											}
																											
																											function updateSlider() {
																											    const slidesToShow = getSlidesToShow();
																											    const slideWidth = slides[0].offsetWidth + 20; // inclut le padding/margin si besoin
																											    // Limites pour ne pas dépasser
																											    if (currentIndex > slides.length - slidesToShow) {
																											        currentIndex = slides.length - slidesToShow;
																											    }
																											    if (currentIndex < 0) currentIndex = 0;
																											
																											    slider.style.transform = `translateX(-${currentIndex * slideWidth}px)`;
																											}
																											
																											rightBtn.addEventListener('click', () => {
																											    currentIndex++;
																											    updateSlider();
																											});
																											
																											leftBtn.addEventListener('click', () => {
																											    currentIndex--;
																											    updateSlider();
																											});
																											
																											window.addEventListener('resize', updateSlider);
																											updateSlider();
																											
																																															