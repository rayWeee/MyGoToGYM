<?php 
// Load database and translation variables ($t, $current_lang, $conn) to satisfy the editor and runtime
require_once __DIR__ . '/db.php';

// Fetch username and role
$username = '';
$role = '';
$membership_info = '';

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT name, role, membership_type, membership_expiry FROM users WHERE id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();

    if ($u) {
        $username = $u['name'];
        $role = $u['role'];

        if (!empty($u['membership_type']) && !empty($u['membership_expiry'])) {
            $expiry = strtotime($u['membership_expiry']);
            $remaining = $expiry - time();
            if ($remaining > 0) { // Only show if membership is active
                $days = ceil($remaining / (60 * 60 * 24)); // Calculate remaining days
                $membership_info = ' - ' . ucfirst($t[$u['membership_type']]) . " ($days " . $t['days_left'] . ")";
            }
        }
    }
}

// ACTIVE PAGE DETECTION
// Since we are using router.php, PHP_SELF is always 'router.php'.
// We check the actual URI path to see which page to highlight.
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$currentPage = str_replace('.php', '', basename($requestUri));

// If the URL is just the folder path (like /abc/), default to 'homepage'
if ($currentPage == 'abc' || $currentPage == 'L' || $currentPage == '' || $currentPage == 'router') {
    $currentPage = 'homepage';
}

// Preserve all existing URL parameters (like 'token') when switching languages
$queryParams = $_GET;
unset($queryParams['lang']); // Remove existing lang to prevent duplicates
$qStr = http_build_query($queryParams);
$langLinkPrefix = $qStr ? '?' . $qStr . '&' : '?';
?>

<nav>
<div class="nav-left">
    <a href="<?= $basePath ?>/homepage" class="<?= $currentPage == 'homepage' ? 'active' : '' ?>"><?= $t['home'] ?></a>
    <a href="<?= $basePath ?>/index" class="<?= $currentPage == 'index' ? 'active' : '' ?>"><?= $t['schedule'] ?></a>
    <a href="<?= $basePath ?>/contacts" class="<?= $currentPage == 'contacts' ? 'active' : '' ?>"><?= $t['contacts'] ?></a>
</div>

<div class="nav-center">
    <div class="lang-switcher">
        <button class="lang-btn" onclick="toggleLangMenu(event)">
            <?= $current_lang === 'en' ? 'EN' : ($current_lang === 'lv' ? 'LV' : 'RU') ?>
            <span style="font-size: 0.6rem; opacity: 0.5;">&#9660;</span>
        </button>
        <div class="lang-dropdown-content" id="langMenuDropdown">
            <a href="<?= $langLinkPrefix ?>lang=en">English</a>
            <a href="<?= $langLinkPrefix ?>lang=lv">Latviešu</a>
            <a href="<?= $langLinkPrefix ?>lang=ru">Русский</a>
        </div>
    </div>
</div>

<button class="hamburger" onclick="toggleMenu()">☰</button>
<div class="nav-right" id="navMenu">
<?php if(isset($_SESSION['user_id'])): ?>
    <?php if($username): // Ensure $username is not empty before displaying ?>
        <a href="<?= $basePath ?>/profile" class="nav-profile-link">
            <span><?= htmlspecialchars($username . $membership_info) ?></span>
            <span class="nav-role">(<?= ucfirst($role) ?>)</span>
        </a>
    <?php endif; ?>
    <?php if($role === 'admin'): ?>
        <a href="<?= $basePath ?>/admin/dashboard" class="<?= $currentPage == 'dashboard' ? 'active' : '' ?>"><?= $t['admin_panel'] ?></a>
    <?php endif; ?>
    <a href="<?= $basePath ?>/logout"><?= $t['logout'] ?></a>
<?php else: ?>
    <a href="<?= $basePath ?>/login" class="<?= in_array($currentPage, ['login', 'forgot_password', 'reset_password']) ? 'active' : '' ?>"><?= $t['login'] ?></a>
    <a href="<?= $basePath ?>/register" class="<?= $currentPage == 'register' ? 'active' : '' ?>"><?= $t['register'] ?></a>
<?php endif; ?>
</div>
</nav>
<script>
function toggleMenu() {
    document.getElementById("navMenu").classList.toggle("show");
}

function toggleLangMenu(event) {
    event.stopPropagation();
    document.getElementById("langMenuDropdown").classList.toggle("show");
}
</script>