<?php
declare(strict_types=1);

function dd($data): void
{
    echo sprintf('<pre>%s</pre>', print_r($data, true));
    die;
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
