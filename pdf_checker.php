<?php

function checkPdfInFolder($folderPath)
{
    if (!is_dir($folderPath)) {
        echo "Folder tidak ditemukan: $folderPath<br/>";
        return;
    }

    $files = scandir($folderPath);
    foreach ($files as $file) {
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;

        // Abaikan '.' dan '..'
        if ($file === '.' || $file === '..') {
            continue;
        }

        if (is_file($filePath)) {
            echo "Memeriksa file: '$file'...<br/>";
            if (isPdfFile($filePath)) {
                echo "File '$file' di folder '$folderPath' adalah PDF yang valid.<br/>";
            } else {
                echo "File '$file' di folder '$folderPath' bukan PDF yang valid.<br/>";
            }
        }
    }
}

function isPdfFile($filePath)
{
    // Periksa ekstensi file terlebih dahulu
    $fileExt = pathinfo($filePath, PATHINFO_EXTENSION);
    if (strtolower($fileExt) !== 'pdf') {
        echo "File '$filePath' bukan PDF (ekstensi salah).<br/>";
        return false;
    }

    // Baca file untuk memverifikasi header dan trailer
    $handle = fopen($filePath, 'rb');
    if (!$handle) {
        echo "Tidak bisa membuka file: $filePath<br/>";
        return false;
    }

    // Baca lebih banyak byte dari file untuk memeriksa header
    $header = fread($handle, 8);  // Membaca 8 byte pertama
    fclose($handle);

    // Tampilkan header yang ditemukan untuk debugging
    echo "Header yang ditemukan: " . bin2hex($header) . "<br/>";  // Mengonversi header menjadi format hex

    // Periksa apakah file dimulai dengan '%PDF'
    if (substr($header, 0, 4) !== '%PDF') {
        echo "File '$filePath' bukan PDF (header salah).<br/>";
        return false;
    }

    // Memeriksa versi PDF dalam header
    if (!preg_match('/^%PDF-\d+\.\d+/', $header)) {
        echo "File '$filePath' tidak memiliki versi PDF yang valid.<br/>";
        return false;
    }

    // Dapatkan ukuran file
    $fileSize = filesize($filePath);

    // Pastikan file memiliki cukup panjang untuk trailer (minimum 5 byte)
    if ($fileSize < 5) {
        echo "File '$filePath' terlalu kecil untuk menjadi PDF yang valid.<br/>";
        return false;
    }

    // Pindahkan pointer ke 5 byte terakhir untuk memeriksa trailer
    $handle = fopen($filePath, 'rb');
    fseek($handle, $fileSize - 5);
    $trailer = fread($handle, 5);
    fclose($handle);

    // Trailer PDF biasanya diakhiri dengan '%%EOF'
    if ($trailer === '%%EOF') {
        // Validasi lebih lanjut pada objek 'xref' dalam PDF
        if (containsXref($filePath)) {
            return true;
        } else {
            echo "File '$filePath' tidak memiliki objek 'xref' yang valid.<br/>";
            return false;
        }
    }

    echo "File '$filePath' bukan PDF (trailer tidak valid).<br/>";
    return false;
}

function containsXref($filePath)
{
    // Periksa apakah file mengandung objek 'xref'
    $handle = fopen($filePath, 'rb');
    if (!$handle) {
        return false;
    }

    // Mencari string 'xref' dalam file PDF
    $fileContent = fread($handle, 1000); // Membaca sebagian file untuk mencari 'xref'
    fclose($handle);

    if (strpos($fileContent, 'xref') !== false) {
        return true; // Temukan objek 'xref', file memiliki struktur PDF yang valid
    }

    return false; // Tidak ditemukan objek 'xref'
}

// Path folder yang ingin diperiksa
$foldersToCheck = [
    __DIR__ . DIRECTORY_SEPARATOR . 'put_your_files_here', // Folder di lokasi file PHP
    'F:' . DIRECTORY_SEPARATOR . 'Downloads'              // Folder Downloads di drive F
];

// Periksa setiap folder
foreach ($foldersToCheck as $folderPath) {
    echo "Memeriksa folder: $folderPath<br/>";
    checkPdfInFolder($folderPath);
    echo str_repeat("<br/>", 2); // Tambahkan 2 baris kosong setelah selesai memeriksa setiap folder
}
