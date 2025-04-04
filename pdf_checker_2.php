<?php

require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;
use setasign\Fpdi\Fpdi;

// Fungsi untuk memeriksa apakah file PDF valid
function isPdfValidWithFpdi($filePath)
{
    try {
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($filePath);  // Coba membuka file PDF dan mendapatkan jumlah halaman

        if ($pageCount > 0) {
            echo "File '$filePath' adalah PDF yang valid.<br/>";
            return true;
        } else {
            echo "File '$filePath' tidak valid (tidak ada halaman).<br/>";
            return false;
        }
    } catch (Exception $e) {
        echo "Error membuka file PDF dengan FPDI: " . $e->getMessage() . "<br/>";
        return false;
    }
}

// Fungsi untuk memeriksa apakah file Word valid (.doc atau .docx)
function isWordValid($filePath)
{
    try {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Pastikan hanya file dengan ekstensi .doc atau .docx yang diproses
        if ($extension !== 'doc' && $extension !== 'docx') {
            echo "File '$filePath' bukan file Word. Dilewati.<br/>";
            return false;
        }

        // Coba membuka file dengan PHPWord
        $phpWord = IOFactory::load($filePath);
        echo "File '$filePath' adalah file Word yang valid.<br/>";
        return true;
    } catch (PhpOffice\PhpWord\Exception\Exception $e) {
        echo "Error membuka file Word '$filePath': " . $e->getMessage() . "<br/>";
        return false;
    } catch (Exception $e) {
        echo "Terjadi kesalahan umum saat membuka file Word '$filePath': " . $e->getMessage() . "<br/>";
        return false;
    }
}

// Fungsi untuk memeriksa format file Word (DOC atau DOCX) sebelum membuka
function checkFileFormat($filePath)
{
    $fileContents = file_get_contents($filePath, false, NULL, 0, 8);
    $fileType = bin2hex($fileContents);

    if (substr($fileType, 0, 4) === 'd0cf') {
        echo "File '$filePath' adalah format Word 97-2003 (doc).<br/>";
    } elseif (substr($fileType, 0, 4) === '504b') {
        echo "File '$filePath' adalah format Word 2007+ (docx).<br/>";
    } else {
        echo "File '$filePath' memiliki format yang tidak dikenal.<br/>";
    }
}

// Fungsi untuk memeriksa apakah file gambar valid
function isImageValid($filePath)
{
    $imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $imageInfo = @getimagesize($filePath); // Menggunakan @ untuk menghindari warning jika file bukan gambar

    if ($imageInfo === false) {
        echo "File '$filePath' bukan gambar yang valid.<br/>";
        return false;
    }

    if (!in_array($imageInfo['mime'], $imageTypes)) {
        echo "File '$filePath' bukan format gambar yang didukung.<br/>";
        return false;
    }

    echo "File '$filePath' adalah gambar yang valid: " . $imageInfo['mime'] . "<br/>";
    return true;
}

// Fungsi untuk memeriksa folder dan validasi file PDF, Word, dan gambar
function checkFilesInFolder($folderPath)
{
    if (!is_dir($folderPath)) {
        echo "Folder tidak ditemukan: $folderPath<br/>";
        return;
    }

    echo "Memeriksa folder: $folderPath<br/>";
    $files = scandir($folderPath);
    $validCount = 0;
    $invalidCount = 0;

    foreach ($files as $file) {
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;

        if ($file === '.' || $file === '..' || !is_file($filePath)) {
            continue; // Lewati folder dan file bukan file biasa
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Pastikan hanya file dengan ekstensi yang valid yang diproses
        if (!in_array($extension, ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            echo "File '$filePath' tidak didukung (bukan PDF, Word, atau gambar). Dilewati.<br/>";
            continue;
        }

        echo "Memeriksa file: '$file'...<br/>";

        // Validasi file PDF
        if ($extension === 'pdf' && isPdfValidWithFpdi($filePath)) {
            $validCount++;
        }
        // Validasi file Word
        elseif (in_array($extension, ['doc', 'docx']) && isWordValid($filePath)) {
            $validCount++;
        }
        // Validasi gambar
        elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']) && isImageValid($filePath)) {
            $validCount++;
        } else {
            echo "File '$filePath' tidak valid.<br/>";
            $invalidCount++;
        }
    }

    echo "<br/>Pemeriksaan selesai untuk folder '$folderPath'.<br/>";
    echo "Jumlah file valid: $validCount<br/>";
    echo "Jumlah file tidak valid: $invalidCount<br/>";
    echo str_repeat("<br/>", 2); // Tambahkan beberapa baris kosong setelah selesai
}
?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Checker</title>

    <style>
        html,
        body {
            background-color: #0f0f0f;
            color: #f0f0f0;
        }
    </style>
</head>

<body>
    <?php
    // Path folder yang ingin diperiksa
    $foldersToCheck = [
        __DIR__ . DIRECTORY_SEPARATOR . 'put_your_files_here', // Folder di lokasi file PHP
        'F:' . DIRECTORY_SEPARATOR . 'Downloads'              // Folder Downloads di drive F
    ];

    // Periksa setiap folder
    foreach ($foldersToCheck as $folderPath) {
        // echo "Memeriksa folder: $folderPath";

        // echo str_repeat("<br/>", 1); // Tambahkan 2 baris kosong setelah selesai memeriksa setiap folder
        checkFilesInFolder($folderPath);

        // echo str_repeat("<br/>", 2); // Tambahkan 2 baris kosong setelah selesai memeriksa setiap folder
    }
    ?>
</body>

</html>