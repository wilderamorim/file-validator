<?php
declare(strict_types=1);

if (!function_exists('dd')) {
    function dd($data): void
    {
        echo sprintf('<pre>%s</pre>', print_r($data, true));
        die;
    }
}

function upload(array $files, string $path): void
{
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }

    $name = $files['name'];
    $ext = '.' . pathinfo($name, PATHINFO_EXTENSION);

    $from = $files['tmp_name'];
    $to = $path . '/' . basename($name, $ext) . '-' . hash('crc32', $name) . $ext;

    move_uploaded_file($from, $to);
}

function html_attributes(array $attributes): string
{
    return implode(' ', array_map(fn($k, $v) => $k . '="' . $v . '"', array_keys($attributes), $attributes));
}
