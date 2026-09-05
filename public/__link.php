<?php

$target = '/home/h355718/include/storage/app/public';
$link = '/home/h355718/public_html/storage';

if (is_link($link)) {
    echo 'Already exists';
} elseif (file_exists($link)) {
    echo 'ERROR: storage already exists';
} elseif (symlink($target, $link)) {
    echo 'Symlink created successfully';
} else {
    echo 'Failed to create symlink';
}