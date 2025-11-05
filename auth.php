<?php
// Responde SIEMPRE JSON
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(0);

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

function respond($arr, $code = 200) {
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

if ($method === 'POST') {
  $body = json_input();

  // si viene "name" => registro, si no => login
  $name = isset($body['name']) ? trim($body['name']) : null;
  $email = trim($body['email'] ?? '');
  $password = $body['password'] ?? '';

  if ($email === '' || $password === '') {
    respond(['success'=>false,'message'=>'Email y contraseña requeridos'], 400);
  }

  if ($name !== null) {
    if ($name === '') respond(['success'=>false,'message'=>'Nombre requerido'], 400);

    $st = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email=?');
    $st->execute([$email]);
    if ($st->fetchColumn() > 0) respond(['success'=>false,'message'=>'El email ya está registrado'], 409);

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $ins  = $pdo->prepare('INSERT INTO users(name,email,password_hash) VALUES (?,?,?)');
    $ins->execute([$name,$email,$hash]);
    $id = $pdo->lastInsertId();

    $token = bin2hex(random_bytes(8));
    respond(['success'=>true,'data'=>[
      'userId'=>(string)$id,'token'=>$token,'name'=>$name,'email'=>$email
    ]]);
  } else {
    $st = $pdo->prepare('SELECT id,name,email,password_hash FROM users WHERE email=? LIMIT 1');
    $st->execute([$email]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
    if (!$u || !password_verify($password, $u['password_hash']))
      respond(['success'=>false,'message'=>'Credenciales inválidas'], 401);

    $token = bin2hex(random_bytes(8));
    respond(['success'=>true,'data'=>[
      'userId'=>(string)$u['id'],'token'=>$token,'name'=>$u['name'],'email'=>$u['email']
    ]]);
  }
}

respond(['success'=>false,'message'=>'Método no soportado'], 405);





