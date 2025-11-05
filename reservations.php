<?php
require_once 'db.php';
ini_set('display_errors', 0);
error_reporting(0);

$m = $_SERVER['REQUEST_METHOD'];

if ($m === 'GET') {
  // SIEMPRE devolvemos ARRAY
  $userId = $_GET['userId'] ?? '';
  if ($userId === '') { http_response_code(400); echo json_encode([]); exit; }

  $st = $pdo->prepare("
    SELECT
      id,
      room_id   AS roomId,
      user_id   AS userId,
      date,
      start_min AS startMin,
      end_min   AS endMin
    FROM reservations
    WHERE user_id = ?
    ORDER BY date DESC, start_min
  ");
  $st->execute([$userId]);

  echo json_encode($st->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
  exit;
}

if ($m === 'POST') {
  // Devuelve OBJETO con success/data
  $b = json_input();
  $roomId = $b['roomId'] ?? '';
  $userId = $b['userId'] ?? '';
  $date   = $b['date']   ?? '';
  $start  = intval($b['startMin'] ?? -1);
  $end    = intval($b['endMin']   ?? -1);

  if (!$roomId || !$userId || !$date || $start<0 || $end<0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Datos incompletos']); exit;
  }
  if ($start >= $end) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Hora inicio >= término']); exit;
  }

  $q  = "SELECT COUNT(*) FROM reservations WHERE room_id=? AND date=? AND start_min<? AND end_min>?";
  $st = $pdo->prepare($q);
  $st->execute([$roomId, $date, $end, $start]);
  if ($st->fetchColumn() > 0) {
    http_response_code(409);
    echo json_encode(['success'=>false,'message'=>'La sala ya está reservada en ese horario']); exit;
  }

  $ins = $pdo->prepare("INSERT INTO reservations(room_id,user_id,date,start_min,end_min) VALUES (?,?,?,?,?)");
  $ins->execute([$roomId,$userId,$date,$start,$end]);
  $id = $pdo->lastInsertId();

  echo json_encode(['success'=>true,'data'=>[
    'id'=>$id,'roomId'=>$roomId,'userId'=>$userId,'date'=>$date,'startMin'=>$start,'endMin'=>$end
  ]], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($m === 'DELETE') {
  $id = $_GET['id'] ?? '';
  if ($id === '') { http_response_code(400); echo json_encode(['success'=>false,'message'=>'id requerido']); exit; }
  $del = $pdo->prepare("DELETE FROM reservations WHERE id=?");
  $ok  = $del->execute([$id]);
  echo json_encode(['success'=>$ok]); exit;
}

http_response_code(405);
echo json_encode(['success'=>false,'message'=>'Método no soportado']);

