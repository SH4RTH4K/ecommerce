<?php

namespace App\Support;

class PublicUpload
{
    public static function store($file, $relativeDirectory, $filenamePrefix, array $allowedExtensions)
    {
        if (!$file || !$file->isValid()) {
            throw new \RuntimeException('The uploaded file is not valid.');
        }

        $relativeDirectory = trim(str_replace('\\', '/', $relativeDirectory), '/').'/';
        $directory = public_path($relativeDirectory);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('The upload directory could not be created.');
        }
        if (!is_writable($directory)) {
            throw new \RuntimeException('The upload directory is not writable.');
        }

        $extension = strtolower((string)($file->getClientOriginalExtension() ?: $file->extension()));
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }
        $allowedExtensions = array_map(function ($extension) {
            $extension = strtolower($extension);
            return $extension === 'jpeg' ? 'jpg' : $extension;
        }, $allowedExtensions);

        if (!$extension || !in_array($extension, $allowedExtensions, true)) {
            throw new \RuntimeException('The uploaded file type is not supported.');
        }

        do {
            $name = $filenamePrefix.str_random(24).'.'.$extension;
            $destination = $directory.DIRECTORY_SEPARATOR.$name;
        } while (is_file($destination));

        $file->move($directory, $name);

        if (!is_file($destination) || filesize($destination) === 0) {
            if (is_file($destination)) {
                @unlink($destination);
            }
            throw new \RuntimeException('The uploaded file was not written completely.');
        }

        return $relativeDirectory.$name;
    }

    public static function remove($relativePath)
    {
        $relativePath = ltrim(str_replace('\\', '/', (string)$relativePath), '/');
        if ($relativePath === '' || strpos($relativePath, '..') !== false) {
            return false;
        }

        $fullPath = public_path($relativePath);
        if (!is_file($fullPath) && !is_link($fullPath)) {
            return false;
        }

        return @unlink($fullPath);
    }
}
