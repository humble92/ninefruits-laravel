<?php
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$keys = [];
$files = [];
foreach (['app', 'resources/views', 'database', 'routes'] as $d) { $files = array_merge($files, iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../../'.$d)))); }
foreach ($files as $f) {
    if (! str_ends_with((string) $f, '.php')) continue;
    preg_match_all("/(?:__|trans|trans_choice|@lang)\\(\\s*'([a-z_]+\\.[a-zA-Z0-9_.]+)'/", file_get_contents((string) $f), $m);
    foreach ($m[1] as $k) $keys[$k] = true;
}
foreach (array_keys($keys) as $k) {
    if (str_ends_with($k, '.')) continue;
    if (! trans()->has($k, 'en', false)) echo $k, "\n";
}
