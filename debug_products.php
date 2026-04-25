<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$p = App\Models\Product::first();
if (!$p) {
    echo "No products found\n";
    exit;
}
$c = new App\Http\Controllers\Api\SuperAdmin\ProductController;
echo json_encode($c->dashboard()->getData(), JSON_PRETTY_PRINT);
