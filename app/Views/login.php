<?php
/**
 * @var bool $has_errors
 * @var bool $is_latest
 * @var string $latest_version
 * @var bool $gcaptcha_enabled
 * @var array $config
 * @var $validation
 */
?>

<!doctype html>
<html lang="<?= current_language_code() ?>">

<head>
    <meta charset="utf-8">
    <base href="<?= base_url() ?>">
    <title><?= lang('Login.app_title') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico">
    <?php
    $theme = (empty($config['theme'])
        || 'paper' == $config['theme']
        || 'readable' == $config['theme']
        ? 'flatly'
        : $config['theme']);
    ?>
    <link rel="stylesheet" href="<?= esc(asset_v("resources/bootswatch/$theme/bootstrap.min.css")) ?>">
    <link rel="stylesheet" href="<?= esc(asset_v('css/login.css')) ?>">
    <meta name="theme-color" content="#182735">
</head>

<body class="bg-secondary-subtle d-flex flex-column">
    <main class="d-flex justify-content-center align-items-center flex-grow-1 p-3">
        <section class="login-card bg-body shadow rounded p-4" aria-labelledby="login-title">
            <header class="text-center mb-4">
                <h1 class="h3 fw-bold mb-1" id="login-title"><?= lang('Login.app_title') ?></h1>
                <?php if (!empty($config['company'])): ?>
                    <p class="fs-6 fw-semibold text-body-secondary mb-2"><?= esc($config['company']) ?></p>
                <?php endif; ?>
                <p class="text-body-secondary mb-0"><?= lang('Login.subtitle') ?></p>
            </header>
            <?= form_open('login') ?>
            <?php if (!$is_latest): ?>
                <div class="alert alert-info">
                    <?= lang('Login.migration_needed', [$latest_version]) ?>
                </div>
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label fw-semibold" for="input-username"><?= lang('Login.username') ?></label>
                <div class="input-group">
                    <span class="input-group-text" aria-hidden="true">
                        <svg class="bi bi-person-fill" fill="currentColor" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6" />
                        </svg>
                    </span>
                    <input class="form-control" id="input-username" name="username" type="text" autocomplete="username" autocapitalize="none" spellcheck="false" autofocus required <?php if (ENVIRONMENT == "testing") echo 'value="admin"'; ?>>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" for="input-password"><?= lang('Login.password') ?></label>
                <div class="input-group">
                    <span class="input-group-text" aria-hidden="true">
                        <svg class="bi bi-key-fill" fill="currentColor" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3.5 11.5a3.5 3.5 0 1 1 3.163-5H14L15.5 8 14 9.5l-1-1-1 1-1-1-1 1-1-1-1 1H6.663a3.5 3.5 0 0 1-3.163 2M2.5 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2" />
                        </svg>
                    </span>
                    <input class="form-control" id="input-password" name="password" type="password" autocomplete="current-password" required aria-describedby="caps-warning" <?php if (ENVIRONMENT == "testing") echo 'value="pointofsale"'; ?>>
                    <button class="btn btn-outline-secondary" id="toggle-password" type="button" aria-label="<?= lang('Login.show_password') ?>" aria-pressed="false" aria-controls="input-password">
                        <svg class="icon-show bi bi-eye-fill" fill="currentColor" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0" />
                            <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7" />
                        </svg>
                        <svg class="icon-hide bi bi-eye-slash-fill d-none" fill="currentColor" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="m10.79 12.912-1.614-1.615a3.5 3.5 0 0 1-4.474-4.474l-2.06-2.06C.938 6.278 0 8 0 8s3 5.5 8 5.5a7 7 0 0 0 2.79-.588M5.21 3.088A7 7 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238l-2.062-2.062a3.5 3.5 0 0 0-4.474-4.474z" />
                            <path d="M5.525 7.646a2.5 2.5 0 0 0 2.829 2.829zm4.95.708-2.829-2.83a2.5 2.5 0 0 1 2.829 2.829zm3.171 6-12-12 .708-.708 12 12z" />
                        </svg>
                    </button>
                </div>
                <div class="form-text text-warning-emphasis fw-semibold d-none" id="caps-warning" role="status"><?= lang('Login.caps_lock') ?></div>
            </div>
            <?php if ($gcaptcha_enabled): ?>
                <script src="https://www.google.com/recaptcha/api.js?render=<?= $config['gcaptcha_site_key'] ?>"></script>
                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
            <?php endif; ?>
            <div class="login-error" id="login-error" role="alert" aria-live="assertive" aria-atomic="true">
                <?php if ($has_errors): ?>
                    <?php foreach ($validation->getErrors() as $error): ?>
                        <p class="login-error-msg bg-danger-subtle text-danger-emphasis border border-danger-subtle rounded fw-semibold mb-2 px-3 py-2"><?= esc($error) ?></p>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="d-grid">
                <button class="btn btn-primary" name="login-button" type="submit"><?= lang('Login.go') ?></button>
            </div>
            <?= form_close() ?>
        </section>
    </main>

    <footer class="text-center text-body-secondary small py-3 flex-shrink-0">
        <?= lang('Login.app_title') . ' ' . date('Y') ?>
    </footer>

    <script>
        (function() {
            var pwd = document.getElementById('input-password');
            var btn = document.getElementById('toggle-password');
            var caps = document.getElementById('caps-warning');
            var user = document.getElementById('input-username');

            btn.addEventListener('click', function() {
                var show = pwd.type === 'password';
                pwd.type = show ? 'text' : 'password';
                btn.setAttribute('aria-pressed', show ? 'true' : 'false');
                btn.querySelector('.icon-show').classList.toggle('d-none', show);
                btn.querySelector('.icon-hide').classList.toggle('d-none', !show);
            });

            function checkCaps(e) {
                if (e.getModifierState) {
                    caps.classList.toggle('d-none', !e.getModifierState('CapsLock'));
                }
            }
            pwd.addEventListener('keydown', checkCaps);
            pwd.addEventListener('keyup', checkCaps);
            pwd.addEventListener('blur', function() { caps.classList.add('d-none'); });

            <?php if ($has_errors): ?>
            pwd.value = '';
            <?php endif; ?>
            user.focus();
        })();
    </script>
    <?php if ($gcaptcha_enabled): ?>
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            var form = this;
            grecaptcha.ready(function() {
                grecaptcha.execute('<?= $config['gcaptcha_site_key'] ?>', {action: 'login'}).then(function(token) {
                    document.getElementById('g-recaptcha-response').value = token;
                    form.submit();
                });
            });
        });
    </script>
    <?php endif; ?>
</body>

</html>
