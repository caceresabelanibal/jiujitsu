<?php
if (current_user()) redirect('/dashboard');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u = row('SELECT * FROM users WHERE email = ?', [strtolower(trim($_POST['email'] ?? ''))]);
    if (!$u || !password_verify($_POST['password'] ?? '', $u['pass_hash'])) {
        flash('error', t('invalid_credentials'));
    } elseif (!$u['verified_at']) {
        flash('warning', t('email_not_verified'));
    } else {
        login_user($u);
        redirect('/dashboard');
    }
}
view_header(t('login_title'));
?>
<div class="card" style="max-width:420px;margin:40px auto">
  <h2><?= t('login_title') ?></h2>
  <form method="post">
    <?= csrf_field() ?>
    <label><?= t('email') ?></label>
    <input type="email" name="email" required autofocus>
    <label><?= t('password') ?></label>
    <input type="password" name="password" required>
    <button class="btn mt" style="width:100%"><?= t('nav_login') ?></button>
  </form>
  <p class="muted mt"><a href="<?= APP_URL ?>/register"><?= t('nav_register') ?></a></p>
</div>
<?php view_footer();
