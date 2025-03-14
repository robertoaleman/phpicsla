<?php
/* PHP Image Comparison with Simple Learning Algorithm v1.02
Author: Roberto Aleman
ventics.com */

include 'config.php';
include 'ImageComparator.php';

$comparator = new ImageComparator($maxFileSize, $allowedTypes, $uploadDir, $learningFile);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imagePath1 = $_POST['imagePath1'];
    $imagePath2 = $_POST['imagePath2'];
    $calculatedSimilarity = $_POST['calculatedSimilarity'];
    $response = $_POST['response'];

    $comparator->learning($imagePath1, $imagePath2, $calculatedSimilarity, $response);
}

echo "<a href='example.php'>Go Back</a>";
?>