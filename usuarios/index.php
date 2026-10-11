<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
requerir_admin();
$titulo = 'Gestión de usuarios';
$descripcion = 'Cuentas, roles y accesos de las personas.';
$db = getDB();

// Acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $op = $_POST['op'] ?? '';
  if ($op === 'crear' || $op === 'editar') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $rol = (int)($_POST['id_rol'] ?? 3);
    $estado = ($_POST['estado'] ?? 'activo') === 'inactivo' ? 'inactivo' : 'activo';
    $id = (int)($_POST['id_usuario'] ?? 0);
    if ($nombre === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
      flash_set('error', 'Nombre y correo válido son obligatorios.');
    } else {
    try {
      if ($op === 'crear') {
        $pass = $_POST['password'] ?? '';
        if (strlen($pass) < 6) { throw new Exception('La contraseña debe tener al menos 6 caracteres.'); }
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $st = $db->prepare("INSERT INTO usuarios (nombre, correo, password, id_rol, estado) VALUES (?,?,?,?,?)");
        $st->execute([$nombre, $correo, $hash, $rol, $estado]);
        registrar_bitacora('crear_usuario', 'usuarios', "Creo usuario $correo");
        flash_set('ok', 'Usuario creado.');
      } else {
      // Proteccion ultimo administrador: no degradar ni desactivar al unico admin activo
      $esUltimoAdmin = function($db, $id) {
        $r = $db->prepare("SELECT id_rol FROM usuarios WHERE id_usuario=?");
        $r->execute([$id]);
        $actual = $r->fetchColumn();
        if ((int)$actual !== 1) return false;
        return (int)$db->query("SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id_rol=u.id_rol WHERE r.nombre='administrador' AND u.estado='activo'")->fetchColumn() <= 1;
      };
    if ($esUltimoAdmin($db, $id) && ($rol !== 1 || $estado !== 'activo')) {
      throw new Exception('No puedes quitar rol/estado al último administrador activo.');
    }
  if (!empty($_POST['password'])) {
    if (strlen($_POST['password']) < 6) { throw new Exception('La contraseña debe tener al menos 6 caracteres.'); }
    $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $st = $db->prepare("UPDATE usuarios SET nombre=?, correo=?, id_rol=?, estado=?, password=? WHERE id_usuario=?");
    $st->execute([$nombre, $correo, $rol, $estado, $hash, $id]);
  } else {
  $st = $db->prepare("UPDATE usuarios SET nombre=?, correo=?, id_rol=?, estado=? WHERE id_usuario=?");
  $st->execute([$nombre, $correo, $rol, $estado, $id]);
}
registrar_bitacora('editar_usuario', 'usuarios', "Edito usuario id=$id");
flash_set('ok', 'Usuario actualizado.');
}
} catch (Exception $ex) { flash_set('error', 'Error: ' . $ex->getMessage()); }
}
redirect(base_url('usuarios/index.php'));
}
if ($op === 'toggle') {
  $id = (int)$_POST['id_usuario'];
  $r = $db->prepare("SELECT id_rol, estado FROM usuarios WHERE id_usuario=?");
  $r->execute([$id]); $t = $r->fetch();
  $nAdmins = (int)$db->query("SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id_rol=u.id_rol WHERE r.nombre='administrador' AND u.estado='activo'")->fetchColumn();
  if ($t && (int)$t['id_rol'] === 1 && $t['estado'] === 'activo' && $nAdmins <= 1) {
    flash_set('error', 'No puedes desactivar al último administrador activo.');
  } else {
  $db->prepare("UPDATE usuarios SET estado = IF(estado='activo','inactivo','activo') WHERE id_usuario=?")->execute([$id]);
  registrar_bitacora('cambiar_estado', 'usuarios', "Cambio estado usuario id=$id");
  flash_set('ok', 'Estado actualizado.');
}
redirect(base_url('usuarios/index.php'));
}
if ($op === 'eliminar') {
  $id = (int)$_POST['id_usuario'];
  if ($id === (int)$_SESSION['user_id']) { flash_set('error', 'No puedes eliminarte a ti mismo.'); }
  else {
    $r = $db->prepare("SELECT id_rol FROM usuarios WHERE id_usuario=?");
    $r->execute([$id]);
    $nAdmins = (int)$db->query("SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id_rol=u.id_rol WHERE r.nombre='administrador' AND u.estado='activo'")->fetchColumn();
    if ((int)$r->fetchColumn() === 1 && $nAdmins <= 1) { flash_set('error', 'No puedes eliminar al último administrador activo.'); }
    else {
      $ur = $db->prepare("SELECT id_usuario, nombre, correo, id_rol, estado FROM usuarios WHERE id_usuario=?");
      $ur->execute([$id]);
      if ($urow = $ur->fetch()) { a_papelera('usuario', $id, $urow['nombre'], ['usuario' => $urow]); }
      $db->prepare("DELETE FROM usuarios WHERE id_usuario=?")->execute([$id]);
      registrar_bitacora('eliminar_usuario', 'usuarios', "Elimino usuario id=$id (a papelera)");
      flash_set('ok', 'Usuario eliminado (restaurable en Papelera).');
    }
}
redirect(base_url('usuarios/index.php'));
}
}

$roles = $db->query("SELECT * FROM roles ORDER BY id_rol")->fetchAll();
$usuarios = $db->query("SELECT u.*, r.nombre AS rol FROM usuarios u JOIN roles r ON r.id_rol=u.id_rol ORDER BY u.id_usuario")->fetchAll();
$edit = null;
if (!empty($_GET['editar'])) {
  $st = $db->prepare("SELECT * FROM usuarios WHERE id_usuario=?");
  $st->execute([(int)$_GET['editar']]);
  $edit = $st->fetch();
}
include __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <h3><?= $edit ? 'Editar usuario' : 'Nuevo usuario' ?></h3>
  <form method="post" class="form-grid">
    <?= csrf_field() ?>
    <input type="hidden" name="op" value="<?= $edit ? 'editar' : 'crear' ?>">
  <?php if ($edit): ?><input type="hidden" name="id_usuario" value="<?= (int)$edit['id_usuario'] ?>"><?php endif; ?>
<label>Nombre<input name="nombre" required value="<?= e($edit['nombre'] ?? '') ?>"></label>
<label>Correo<input type="email" name="correo" required value="<?= e($edit['correo'] ?? '') ?>"></label>
<label>Contraseña <?= $edit ? '(vacío = no cambiar)' : '' ?><input type="password" name="password" <?= $edit ? '' : 'required' ?>></label>
<label>Rol<select name="id_rol"><?php foreach ($roles as $r): ?><option value="<?= $r['id_rol'] ?>" <?= isset($edit) && $edit['id_rol']==$r['id_rol']?'selected':'' ?>><?= e($r['nombre']) ?></option><?php endforeach; ?></select></label>
<label>Estado<select name="estado"><option value="activo" <?= ($edit['estado'] ?? '')==='activo'?'selected':'' ?>>activo</option><option value="inactivo" <?= ($edit['estado'] ?? '')==='inactivo'?'selected':'' ?>>inactivo</option></select></label>
<div><button class="btn btn-primary"><?= $edit ? 'Guardar' : 'Crear' ?></button> <?php if ($edit): ?><a class="btn" href="<?= e(base_url('usuarios/index.php')) ?>">Cancelar</a><?php endif; ?></div>
</form>
</div>
<div class="card">
  <h3>Usuarios registrados</h3>
  <table class="table"><thead><tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Último acceso</th><th>Acciones</th></tr></thead><tbody>
      <?php foreach ($usuarios as $u): ?>
        <tr><td><?= $u['id_usuario'] ?></td><td><?= e($u['nombre']) ?></td><td><?= e($u['correo']) ?></td>
          <td><span class="badge"><?= e($u['rol']) ?></span></td>
          <td><?= e($u['estado']) ?></td><td><?= e($u['ultimo_acceso'] ?? '—') ?></td>
          <td class="actions">
            <a class="btn btn-sm" href="?editar=<?= $u['id_usuario'] ?>">Editar</a>
          <form method="post" style="display:inline">
            <?= csrf_field() ?><input type="hidden" name="op" value="toggle"><input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>"><button class="btn btn-sm"><?= $u['estado']==='activo'?'Desactivar':'Activar' ?></button></form>
        <form method="post" style="display:inline" data-confirm="¿Eliminar usuario?">
          <?= csrf_field() ?><input type="hidden" name="op" value="eliminar"><input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>"><button class="btn btn-sm danger">Eliminar</button></form>
    </td></tr>
<?php endforeach; ?>
</tbody></table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
