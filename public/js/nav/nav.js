window.addEventListener('scroll', function() {
    const nav = document.querySelector('nav');
    if (window.scrollY > 50) { // déclenche après 50px de scroll
        nav.classList.add('scrolled');
    } else {
        nav.classList.remove('scrolled');
    }
});
