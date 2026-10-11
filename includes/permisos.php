<?php
// Control de permisos por rol.
// Roles: administrador > steward > usuario

function es_admin() { return (($_SESSION['rol'] ?? '') === 'administrador'); }
function es_steward() { return in_array(($_SESSION['rol'] ?? ''), ['administrador','steward'], true); }

function requerir_admin() {
  if (!es_admin()) { http_response_code(403); die('Acceso denegado: se requiere rol Administrador.'); }
}
function requerir_steward() {
  if (!es_steward()) { http_response_code(403); die('Acceso denegado: se requiere rol Data Steward o Administrador.'); }
}
function puede_editar() { return es_steward(); } // steward y admin pueden crear/editar
