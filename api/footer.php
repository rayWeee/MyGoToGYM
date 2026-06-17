<footer>
    <div>
        © <?= date('Y') ?> MyGoToGym | 
        <a href="<?= $basePath ?>/privacy">
            <?= $t['privacy_policy'] ?>
        </a>
        | <a href="<?= $basePath ?>/impressum">
            <?= $t['impressum_title'] ?>
        </a>
    </div>
    
    <div class="social-icons">
        <a href="https://www.facebook.com/raivis.locmelis.7" target="_blank" class="social-link" aria-label="<?= $t['facebook'] ?>" data-tooltip="<?= $t['facebook'] ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 3.656 11.127 8.812 13.226v-9.357h-3.393v-3.869h3.393v-2.949c0-3.35 2.043-5.183 5.032-5.183 1.432 0 2.929.256 2.929.256v3.22h-1.65c-1.659 0-2.176 1.03-2.176 2.088v2.588h3.628l-.58 3.869h-3.048v9.357c5.156-2.099 8.812-7.236 8.812-13.226z"/>
            </svg>
        </a>
        <a href="https://www.instagram.com/rayweee/" target="_blank" class="social-link" aria-label="<?= $t['instagram'] ?>" data-tooltip="<?= $t['instagram'] ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.07 1.645.07 4.85s-.012 3.584-.07 4.85c-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07s-3.584-.012-4.85-.07c-3.251-.148-4.77-1.691-4.919-4.919-.058-1.265-.07-1.644-.07-4.85s.012-3.584.07-4.85c.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.07 4.85-.07zm0 2.163c-3.204 0-3.584.012-4.85.07-2.703.123-3.95 1.409-4.071 4.071-.058 1.265-.07 1.644-.07 4.85s.012 3.584.07 4.85c.121 2.662 1.368 3.948 4.071 4.071 1.265.058 1.644.07 4.85.07s3.584-.012 4.85-.07c2.703-.123 3.95-1.409 4.071-4.071.058-1.265.07-1.644.07-4.85s-.012-3.584-.07-4.85c-.121-2.662-1.368-3.948-4.071-4.071-1.265-.058-1.644-.07-4.85-.07zm0 3.627c-2.34 0-4.24 1.9-4.24 4.24s1.9 4.24 4.24 4.24 4.24-1.9 4.24-4.24-1.9-4.24-4.24-4.24zm0 2.163c1.17 0 2.077.907 2.077 2.077s-.907 2.077-2.077 2.077-2.077-.907-2.077-2.077.907-2.077 2.077-2.077zm5.504-5.162c0 .667-.543 1.21-1.21 1.21s-1.21-.543-1.21-1.21.543-1.21 1.21-1.21 1.21.543 1.21 1.21z"/>
            </svg>
        </a>
        <a href="https://twitter.com" target="_blank" class="social-link" aria-label="<?= $t['twitter'] ?>" data-tooltip="<?= $t['twitter'] ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932 6.064-6.932zm-1.294 19.497h2.039L6.486 3.24H4.298l13.309 17.41z"/>
            </svg>
        </a>
    </div>
</footer>

<button id="backToTopBtn" class="back-to-top-btn" title="Go to top">↑</button>

<link rel="stylesheet" href="<?= $basePath ?>/assets/css/footer.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/cookie_popup.css">
<?php include __DIR__ . '/cookie_popup.php'; ?>

<script>
    // Get the button
    let backToTopBtn = document.getElementById("backToTopBtn");

    // Show button after scrolling down 300px
    window.onscroll = function() {
        if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
            backToTopBtn.classList.add("show");
        } else {
            backToTopBtn.classList.remove("show");
        }
    };

    // When the user clicks on the button, scroll to the top of the document
    backToTopBtn.onclick = function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    };
</script>