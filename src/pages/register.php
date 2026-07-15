<?php
if (current_user()) redirect('/dashboard');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? '';
    if (!captcha_check()) {
        flash('error', t('captcha_wrong'));
    } elseif (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', t('invalid_credentials'));
    } elseif (strlen($pass) < 6) {
        flash('error', t('password_min'));
    } elseif (row('SELECT id FROM users WHERE email = ?', [$email])) {
        flash('error', t('email_taken'));
    } else {
        $token = bin2hex(random_bytes(24));
        q('INSERT INTO users (name, email, pass_hash, verify_token, locale) VALUES (?,?,?,?,?)',
            [$name, $email, password_hash($pass, PASSWORD_DEFAULT), $token, lang()]);
        $link = APP_URL . '/verify?token=' . $token;
        queue_mail($email, $name, t('mail_verify_subject'), mail_layout(t('mail_verify_title'),
            mail_p(e($name) . ',') .
            mail_p(t('mail_verify_body')) .
            mail_button($link, t('mail_verify_button')) .
            mail_link_fallback($link)));
        flash('success', t('account_created_check_email'));
        redirect('/login');
    }
}
view_header(t('register_title'));
?>
<div class="card" style="max-width:420px;margin:40px auto">
  <h2><?= t('register_title') ?></h2>
  <form method="post">
    <?= csrf_field() ?>
    <label><?= t('name') ?></label>
    <input type="text" name="name" required autofocus>
    <label><?= t('email') ?></label>
    <input type="email" name="email" required>
    <label><?= t('password') ?></label>
    <input type="password" name="password" minlength="6" required>
    <?= captcha_field() ?>
    <button class="btn mt" style="width:100%"><?= t('nav_register') ?></button>
  </form>
</div>
<?php view_footer();
