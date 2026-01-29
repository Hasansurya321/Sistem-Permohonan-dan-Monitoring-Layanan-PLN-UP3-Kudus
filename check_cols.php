<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;

$cols = Schema::getColumnListing('applicant_identities');
file_put_contents('db_cols.json', json_encode($cols));
echo "DONE";
