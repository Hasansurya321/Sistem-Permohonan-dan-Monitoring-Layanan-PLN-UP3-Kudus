<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$cols = \Illuminate\Support\Facades\Schema::getColumnListing('applicant_identities');
file_put_contents('db_cols.txt', implode("\n", $cols));
echo "DONE";
