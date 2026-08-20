<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$totalScore = 73.50;
$classCategory = 'Junior Secondary';

DB::enableQueryLog();

$gradeRule = App\Models\GradeRule::where('class_category', $classCategory)
    ->where('min_score', '<=', $totalScore)
    ->where('max_score', '>=', $totalScore)
    ->first();

print_r(DB::getQueryLog());
echo "\nFound: " . ($gradeRule ? $gradeRule->grade : 'null') . "\n";
