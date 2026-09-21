<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$databaseDir = realpath(__DIR__ . '/../database');
if (!$databaseDir) { fwrite(STDERR, "Diretório de migrações não encontrado.\n"); exit(1); }

$forbidden = [
    '/\bDROP\s+(?:DATABASE|SCHEMA|TABLE|COLUMN)\b/i',
    '/\bTRUNCATE\b/i',
    '/\bDELETE\s+FROM\b/i',
    '/\bREPLACE\s+INTO\b/i',
];

foreach (glob($databaseDir . '/*.sql') as $file) {
    $sql = file_get_contents($file);
    foreach ($forbidden as $pattern) {
        if (preg_match($pattern, $sql)) {
            fwrite(STDERR, 'Migração destrutiva bloqueada: ' . basename($file) . "\n");
            exit(1);
        }
    }
}

echo "Migrações verificadas: nenhuma operação destrutiva de banco.\n";
