<?php
// Shared utility functions for Construct3 Game Uploader

if (!function_exists('c3gu_delete_directory')) {
    function c3gu_delete_directory($dir) {
        if (!file_exists($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? c3gu_delete_directory($path) : unlink($path);
        }
        rmdir($dir);
    }
}