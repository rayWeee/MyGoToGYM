<!-- Cookie Consent Popup -->
<div id="cookiePopup" class="cookie-popup">
    <div class="cookie-content">
        <p><?= $t['cookie_msg'] ?></p>
    </div>
    <div class="cookie-btns">
        <button onclick="setConsent('denied')" class="btn-deny"><?= $t['cookie_deny'] ?></button>
        <button onclick="setConsent('accepted')" class="btn-accept"><?= $t['cookie_accept'] ?></button>
    </div>
</div>

<script>
function getCookie(name) {
    let value = "; " + document.cookie;
    let parts = value.split("; " + name + "=");
    if (parts.length == 2) return parts.pop().split(";").shift();
}

function setConsent(choice) {
    const d = new Date();
    d.setTime(d.getTime() + (365*24*60*60*1000));
    document.cookie = "cookie_consent=" + choice + ";expires=" + d.toUTCString() + ";path=/";
    document.getElementById('cookiePopup').classList.remove('show');
    
    if (choice === 'accepted') {
        trackEvent('visit');
    }
}

function trackEvent(action) {
    // Uses the basePath variable from your router to find the API correctly
    const apiPath = '<?= $basePath ?? "" ?>/api/track_analytics';
    fetch(apiPath, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            action: action,
            page_path: window.location.pathname 
        })
    });
}

document.addEventListener("DOMContentLoaded", function() {
    const consent = getCookie('cookie_consent');
    if (!consent) {
        document.getElementById('cookiePopup').classList.add('show');
    } else if (consent === 'accepted') {
        // Auto-track if already accepted
        trackEvent('visit');
    }
});
</script>