<?php
$data = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
function export(array $a, int $depth): string {
    $pad = str_repeat('    ', $depth + 1);
    $out = "[\n";
    foreach ($a as $k => $v) {
        $key = "'".str_replace(["\\", "'"], ["\\\\", "\\'"], (string) $k)."'";
        $val = is_array($v) ? export($v, $depth + 1) : "'".str_replace(["\\", "'"], ["\\\\", "\\'"], (string) $v)."'";
        $out .= "{$pad}{$key} => {$val},\n";
    }
    return $out.str_repeat('    ', $depth).']';
}
foreach ($data as $locale => $groups) {
    foreach ($groups as $group => $entries) {
        $path = $argv[2]."/lang/{$locale}/{$group}.php";
        $existing = file_exists($path) ? require $path : [];
        file_put_contents($path, "<?php\n\nreturn ".export(array_replace_recursive($existing, $entries), 0).";\n");
    }
}
