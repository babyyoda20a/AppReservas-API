<?php
require_once 'db.php';
ini_set('display_errors', 0);
error_reporting(0);

$id     = $_GET['id']     ?? null;
$query  = $_GET['query']  ?? ''; // OJO: "query" (no "q")
$sede   = $_GET['sede']   ?? '';
$minCap = isset($_GET['minCap']) ? intval($_GET['minCap']) : null;

if ($id) {
  $st = $pdo->prepare("SELECT * FROM rooms WHERE id=?");
  $st->execute([$id]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  if ($r) {
    $r['capacidad'] = (int)$r['capacidad'];
    $r['recursos']  = json_decode($r['recursos'], true) ?: [];
    echo json_encode($r, JSON_UNESCAPED_UNICODE);
  } else {
    http_response_code(404);
    echo json_encode(['success'=>false,'message'=>'Room not found']);
  }
  exit;
}

$sql = "SELECT * FROM rooms WHERE 1=1"; $p = [];
if ($query!=='') {
  $sql .= " AND (LOWER(nombre) LIKE ? OR LOWER(edificio) LIKE ? OR LOWER(sede) LIKE ?)";
  $like = "%".strtolower($query)."%";
  array_push($p,$like,$like,$like);
}
if ($sede!==''){ $sql.=" AND LOWER(sede)=?"; $p[] = strtolower($sede); }
if ($minCap!==null){ $sql.=" AND capacidad>=?"; $p[] = $minCap; }
$sql .= " ORDER BY sede, edificio, nombre";
$st = $pdo->prepare($sql);
$st->execute($p);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as &$x) {
  $x['capacidad'] = (int)$x['capacidad'];        // fuerza número
  $x['recursos']  = json_decode($x['recursos'], true) ?: [];
}
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
