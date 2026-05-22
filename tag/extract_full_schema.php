<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$databaseName = DB::getDatabaseName();
$tables = DB::select('SHOW TABLES');
$tableKey = 'Tables_in_'.$databaseName;

$schema = [];
foreach ($tables as $table) {
    $tableName = $table->$tableKey;
    $columns = DB::select("DESCRIBE `$tableName`");
    $fks = DB::select('
        SELECT 
            COLUMN_NAME, 
            REFERENCED_TABLE_NAME, 
            REFERENCED_COLUMN_NAME 
        FROM 
            information_schema.KEY_COLUMN_USAGE 
        WHERE 
            TABLE_SCHEMA = ? AND 
            TABLE_NAME = ? AND 
            REFERENCED_TABLE_NAME IS NOT NULL
    ', [$databaseName, $tableName]);

    $schema[$tableName] = [
        'columns' => $columns,
        'fks' => $fks,
    ];
}
echo json_encode($schema);
