<?php
if (current_user()) redirect('/dashboard');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? '';
    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
            '<p>' . t('mail_verify_body') . '</p>' .
            '<p style="text-align:center"><a href="' . $link . '" style="background:#4f8cff;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold">' . t('mail_verify_button') . '</a></p>' .
            '<p style="font-size:12px;color:#888">' . $link . '</p>'));
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
    <button class="btn mt" style="width:100%"><?= t('nav_register') ?></button>
  </form>
</div>
<?php view_footer();
