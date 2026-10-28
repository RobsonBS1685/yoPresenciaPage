<?php
declare(strict_types=1);
// Ejecutar exclusivamente desde terminal: php create_admin.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit('No encontrado'); }
$config = require __DIR__ . '/config.php';
try { $pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',$config['db_user'],$config['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]); }
catch (Throwable $e) { fwrite(STDERR,"No se pudo conectar a MySQL. Importa schema.sql y revisa config.php.\n"); exit(1); }
function ask(string $label): string { fwrite(STDOUT,$label); return trim((string)fgets(STDIN)); }
$name=ask('Nombre del administrador: '); $email=strtolower(ask('Correo del administrador: '));
if (mb_strlen($name)<2 || !filter_var($email,FILTER_VALIDATE_EMAIL)) { fwrite(STDERR,"Nombre o correo inválido.\n"); exit(1); }
fwrite(STDOUT,"Contraseña (mínimo 12 caracteres, no se mostrará oculta en esta terminal): "); $password=trim((string)fgets(STDIN));
if (strlen($password)<12 || !preg_match('/[a-z]/',$password) || !preg_match('/[A-Z]/',$password) || !preg_match('/[0-9]/',$password)) { fwrite(STDERR,"Debe tener 12+ caracteres, mayúscula, minúscula y número.\n"); exit(1); }
try { $q=$pdo->prepare("INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,'admin')"); $q->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]); fwrite(STDOUT,"Administrador creado correctamente. Guarda sus credenciales y elimina este archivo después si lo deseas.\n"); }
catch (PDOException $e) { fwrite(STDERR, $e->getCode()==='23000' ? "Ese correo ya está registrado.\n" : "No se pudo crear el administrador.\n"); exit(1); }
