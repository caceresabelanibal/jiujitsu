<?php
$me = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($do === 'create') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && !row('SELECT id FROM users WHERE email=?', [$email]) && strlen($_POST['password'] ?? '') >= 6) {
            q('INSERT INTO users (name, email, pass_hash, role, verified_at) VALUES (?,?,?,?,NOW())',
                [trim($_POST['name']), $email, password_hash($_POST['password'], PASSWORD_DEFAULT),
                 ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user']);
            flash('success', t('user_saved'));
        } else {
            flash('error', t('email_taken') . ' / ' . t('password_min'));
        }
    } elseif ($do === 'update' && $id) {
        q('UPDATE users SET name=?, role=? WHERE id=?',
            [trim($_POST['name']), ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user', $id]);
        if (strlen($_POST['password'] ?? '') >= 6) {
            q('UPDATE users SET pass_hash=? WHERE id=?', [password_hash($_POST['password'], PASSWORD_DEFAULT), $id]);
        }
        flash('success', t('user_saved'));
    } elseif ($do === 'verify' && $id) {
        q('UPDATE users SET verified_at = NOW(), verify_token = NULL WHERE id=?', [$id]);
    } elseif ($do === 'delete' && $id && $id !== (int)$me['id']) {
        q('DELETE FROM users WHERE id=?', [$id]);
        flash('success', t('user_deleted'));
    }
    redirect('/admin/users');
}

$users = rows('SELECT u.*, (SELECT COUNT(*) FROM tournaments t WHERE t.user_id=u.id) tcount FROM users u ORDER BY u.created_at DESC');
view_header(t('users'));
?>
<h1>👤 <?= t('users') ?></h1>

<div class="card">
  <h3><?= t('new_user') ?></h3>
  <form method="post" class="flex">
    <?= csrf_field() ?>
    <input type="hidden" name="do" value="create">
    <div style="flex:1;min-width:140px"><input type="text" name="name" placeholder="<?= t('name') ?>" required></div>
    <div style="flex:1;min-width:180px"><input type="email" name="email" placeholder="<?= t('email') ?>" required></div>
    <div style="flex:1;min-width:120px"><input type="password" name="password" placeholder="<?= t('password') ?>" minlength="6" required></div>
    <div><select name="role"><option value="user">user</option><option value="admin">admin</option></select></div>
    <button class="btn">+</button>
  </form>
</div>

<div class="card table-wrap">
<table>
  <tr><th><?= t('name') ?></th><th><?= t('email') ?></th><th><?= t('role') ?></th><th><?= t('verified') ?></th><th><?= t('nav_tournaments') ?></th><th><?= t('actions') ?></th></tr>
  <?php foreach ($users as $u): ?>
  <tr>
    <form method="post">
    <td><input type="text" name="name" value="<?= e($u['name']) ?>" style="min-width:120px"></td>
    <td class="muted"><?= e($u['email']) ?></td>
    <td><select name="role"><option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>user</option><option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>admin</option></select></td>
    <td><?= $u['verified_at'] ? '<span class="badge green">✓</span>' : '<span class="badge grey">✕</span>' ?></td>
    <td><?= (int)$u['tcount'] ?></td>
    <td style="white-space:nowrap">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $u['id'] ?>">
      <input type="password" name="password" placeholder="••••" style="width:80px;display:inline-block">
      <button class="btn sm" name="do" value="update"><?= t('save') ?></button>
      <?php if (!$u['verified_at']): ?><button class="btn sm green" name="do" value="verify">✓</button><?php endif; ?>
      <?php if ($u['id'] != $me['id']): ?><button class="btn sm danger" name="do" value="delete" onclick="return confirm('<?= t('confirm_delete') ?>')">✕</button><?php endif; ?>
    </td>
    </form>
  </tr>
  <?php endforeach; ?>
</table>
</div>
<?php view_footer();
