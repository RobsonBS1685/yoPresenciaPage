<?php
declare(strict_types=1);
$config = require __DIR__ . '/../config.php';
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $config['allowed_origins'], true)) {
  header('Access-Control-Allow-Origin: ' . $origin);
  header('Access-Control-Allow-Credentials: true');
  header('Vary: Origin');
}
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, private');
header('Referrer-Policy: same-origin');
header('X-Frame-Options: DENY');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token'); http_response_code(204); exit; }
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET','POST'], true)) respond(405, ['success'=>false,'message'=>'Método no permitido.']);
$action = (string)($_GET['action'] ?? '');
if ($action === 'login') { $now=time(); $_SESSION['login_attempts'] = array_values(array_filter($_SESSION['login_attempts'] ?? [], static fn($t) => is_int($t) && $t > $now-900)); if (count($_SESSION['login_attempts']) >= 8) respond(429, ['success'=>false,'message'=>'Demasiados intentos. Espera 15 minutos antes de volver a intentarlo.']); }
try {
  $pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4', $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]);
} catch (Throwable $e) { respond(503, ['success'=>false,'message'=>'No se pudo conectar a MySQL. Revisa XAMPP y la base de datos.']); }
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name('YOPRESENCIASESSID');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
session_start();
// Mitigación CSRF: aceptar escrituras solo desde los orígenes locales explícitamente autorizados.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($origin, $config['allowed_origins'], true)) respond(403, ['success'=>false,'message'=>'Origen no autorizado.']);
if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$input = json_decode(file_get_contents('php://input'), true); if (!is_array($input)) $input=[];
function respond(int $code, array $body): never { http_response_code($code); echo json_encode($body, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
function cleanText($value, int $max): string { $s=trim((string)$value); return mb_substr($s,0,$max,'UTF-8'); }
function requirePost(): void { if ($_SERVER['REQUEST_METHOD']!=='POST') respond(405,['success'=>false,'message'=>'Método no permitido.']); }
function requireUser(): array { if (empty($_SESSION['user'])) respond(401,['success'=>false,'message'=>'Debes iniciar sesión.']); return $_SESSION['user']; }
function requireAdmin(): array { $u=requireUser(); if (($u['role']??'')!=='admin') respond(403,['success'=>false,'message'=>'No tienes permisos para esta acción.']); return $u; }
function csrfCheck(): void { $token=$_SERVER['HTTP_X_CSRF_TOKEN']??''; if (!is_string($token)||!hash_equals((string)($_SESSION['csrf']??''),$token)) respond(403,['success'=>false,'message'=>'La sesión de seguridad expiró. Recarga la página e inténtalo otra vez.']); }
// El token CSRF se entrega mediante la acción me/login y se exige en operaciones privadas de escritura.
if ($action==='me' && $_SERVER['REQUEST_METHOD']==='GET') respond(200,['success'=>true,'user'=>$_SESSION['user']??null,'csrfToken'=>$_SESSION['csrf']]);
if ($_SERVER['REQUEST_METHOD']==='POST' && !in_array($action, ['login','register','contact'], true)) { $provided=$_SERVER['HTTP_X_CSRF_TOKEN']??''; if (!is_string($provided)||!hash_equals((string)$_SESSION['csrf'],$provided)) respond(403,['success'=>false,'message'=>'Token de seguridad inválido. Recarga la página e inténtalo de nuevo.']); }
if ($action==='login') { requirePost(); $email=strtolower(cleanText($input['email']??'',190)); $pass=(string)($input['password']??''); if (!filter_var($email,FILTER_VALIDATE_EMAIL)||$pass==='') respond(422,['success'=>false,'message'=>'Ingresa un correo válido y tu contraseña.']); $q=$pdo->prepare('SELECT id,name,email,password_hash,role,created_at FROM users WHERE email=? LIMIT 1'); $q->execute([$email]); $u=$q->fetch(); if (!$u||!password_verify($pass,$u['password_hash'])) { $_SESSION['login_attempts'][]=time(); usleep(250000); respond(401,['success'=>false,'message'=>'Correo o contraseña incorrectos.']); } unset($_SESSION['login_attempts']); session_regenerate_id(true); unset($u['password_hash']); $_SESSION['user']=['id'=>(int)$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role']]; $_SESSION['csrf']=bin2hex(random_bytes(32)); respond(200,['success'=>true,'message'=>'Sesión iniciada.','user'=>$_SESSION['user'],'csrfToken'=>$_SESSION['csrf']]); }
if ($action==='register') { requirePost(); $name=cleanText($input['name']??'',100); $email=strtolower(cleanText($input['email']??'',190)); $pass=(string)($input['password']??''); if (mb_strlen($name)<2||!filter_var($email,FILTER_VALIDATE_EMAIL)) respond(422,['success'=>false,'message'=>'Revisa tu nombre y correo electrónico.']); if (strlen($pass)<10||!preg_match('/[a-z]/',$pass)||!preg_match('/[A-Z]/',$pass)||!preg_match('/[0-9]/',$pass)) respond(422,['success'=>false,'message'=>'La contraseña debe tener al menos 10 caracteres, una mayúscula, una minúscula y un número.']); try {$q=$pdo->prepare('INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,\'user\')');$q->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT)]);}catch(PDOException $e){if($e->getCode()==='23000')respond(409,['success'=>false,'message'=>'Ya existe una cuenta con ese correo.']);throw $e;} respond(201,['success'=>true,'message'=>'Cuenta creada correctamente. Ya puedes iniciar sesión.']); }
if ($action==='contact') { requirePost(); $name=cleanText($input['name']??'',100); $email=strtolower(cleanText($input['email']??'',190)); $subject=cleanText($input['subject']??'Consulta general',120); $message=cleanText($input['message']??'',3000); if (mb_strlen($name)<2||!filter_var($email,FILTER_VALIDATE_EMAIL)||mb_strlen($message)<5||empty($input['consent'])) respond(422,['success'=>false,'message'=>'Completa los datos correctamente y acepta el uso de datos para responder.']); $uid=$_SESSION['user']['id']??null; $q=$pdo->prepare('INSERT INTO contact_messages(user_id,name,email,subject,message) VALUES(?,?,?,?,?)');$q->execute([$uid,$name,$email,$subject,$message]); respond(201,['success'=>true,'message'=>'Tu mensaje fue enviado. Gracias por contactarte con Yopresencia.']); }
if ($action==='logout') { requirePost(); $_SESSION=[]; if (ini_get('session.use_cookies')) { $p=session_get_cookie_params(); setcookie(session_name(),'',time()-42000,$p['path'],'',$p['secure'],$p['httponly']); } session_destroy(); respond(200,['success'=>true,'message'=>'Sesión cerrada.']); }
if ($action==='admin-data' && $_SERVER['REQUEST_METHOD']==='GET') { requireAdmin(); $messages=$pdo->query('SELECT id,name,email,subject,message,status,DATE_FORMAT(created_at, \'%d-%m-%Y %H:%i\') AS created_at FROM contact_messages ORDER BY created_at DESC LIMIT 500')->fetchAll(); $users=$pdo->query('SELECT id,name,email,role,DATE_FORMAT(created_at, \'%d-%m-%Y\') AS created_at FROM users ORDER BY created_at DESC LIMIT 500')->fetchAll(); respond(200,['success'=>true,'messages'=>$messages,'users'=>$users]); }
if ($action==='message-read') { requirePost(); requireAdmin(); $id=filter_var($input['id']??null,FILTER_VALIDATE_INT); if (!$id) respond(422,['success'=>false,'message'=>'Identificador inválido.']); $q=$pdo->prepare("UPDATE contact_messages SET status='leido' WHERE id=?");$q->execute([$id]);respond(200,['success'=>true,'message'=>'Mensaje marcado como leído.']); }
if ($action==='message-delete') { requirePost(); requireAdmin(); $id=filter_var($input['id']??null,FILTER_VALIDATE_INT); if (!$id) respond(422,['success'=>false,'message'=>'Identificador inválido.']); $q=$pdo->prepare('DELETE FROM contact_messages WHERE id=?');$q->execute([$id]);respond(200,['success'=>true,'message'=>'Mensaje eliminado.']); }
if ($action==='user-delete') { requirePost(); $admin=requireAdmin(); $id=filter_var($input['id']??null,FILTER_VALIDATE_INT); if (!$id||(int)$id===(int)$admin['id']) respond(422,['success'=>false,'message'=>'No puedes eliminar tu propia cuenta desde este panel.']); $q=$pdo->prepare("DELETE FROM users WHERE id=? AND role='user'");$q->execute([$id]);respond(200,['success'=>true,'message'=>'Cuenta eliminada si correspondía a un usuario normal.']); }
respond(404,['success'=>false,'message'=>'Acción no encontrada.']);
