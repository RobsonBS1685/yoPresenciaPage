<?php
declare(strict_types=1);
// Configuración local de XAMPP. En producción, mueve secretos a variables de entorno.
return [
  'db_host' => getenv('YOP_DB_HOST') ?: '127.0.0.1',
  'db_name' => getenv('YOP_DB_NAME') ?: 'yopresencia',
  'db_user' => getenv('YOP_DB_USER') ?: 'root',
  'db_pass' => getenv('YOP_DB_PASS') !== false ? getenv('YOP_DB_PASS') : '',
  'allowed_origins' => ['http://localhost:4200', 'http://127.0.0.1:4200'],
];
