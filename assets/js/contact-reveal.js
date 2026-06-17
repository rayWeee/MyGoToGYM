document.addEventListener('DOMContentLoaded', () => {
    const items = document.querySelectorAll('.contact-item');
    if (!items.length) return;

    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;

            entry.target.classList.add('reveal');
            obs.unobserve(entry.target);
        });
    }, { threshold: 0.1 });

    items.forEach(item => observer.observe(item));
});