<?php

/* PHP Image Comparison with Simple Learning Algorithm v1.02
Author: Roberto Aleman
ventics.com */

include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imagePath1 = $_POST['imagePath1'];
    $imagePath2 = $_POST['imagePath2'];
    $calculatedSimilarity = $_POST['calculatedSimilarity'];
    $response = $_POST['response'];

    $csvFile = 'learning.csv';
    $data = [$imagePath1, $imagePath2, $calculatedSimilarity, $response];

    $file = fopen($csvFile, 'a');
    fputcsv($file, $data);
    fclose($file);

    if ($response == 'y') {
        echo "Thank you for your confirmation!\n";

    } elseif ($response == 'n') {
        echo "Thank you for your correction!\n";

    } else {
        echo "Invalid response.\n";

    }
}
echo "<a href='img_compare.php'>Go Back</a>";
?>