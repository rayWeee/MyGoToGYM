document.addEventListener('DOMContentLoaded', () => {
    const map = document.querySelector('.map-container');
    if (!map) return;

    const observer = new IntersectionObserver(([entry], obs) => {
        if (!entry.isIntersecting) return;

        entry.target.classList.add('reveal');
        obs.unobserve(entry.target);
    }, { threshold: 0.1 });

    observer.observe(map);
});