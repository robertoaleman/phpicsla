<?php

/* PHP Image Comparison with Simple Learning Algorithm v1.02
Author: Roberto Aleman
ventics.com */

include 'config.php';
include 'ImageComparator.php';

$comparator = new ImageComparator($maxFileSize, $allowedTypes, $uploadDir, $learningFile);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadResults = $comparator->processUploads($_FILES['image1'], $_FILES['image2']);

    if (isset($uploadResults['imagePath1'], $uploadResults['imagePath2'])) {
        $imagePath1 = $uploadResults['imagePath1'];
        $imagePath2 = $uploadResults['imagePath2'];

        echo '<div style="display: flex;">';
        echo '<img src="' . $imagePath1 . '" style="max-width: 300px; margin-right: 10px;">';
        echo '<img src="' . $imagePath2 . '" style="max-width: 300px;">';
        echo '</div><br>';

        $similarity = $comparator->compareImages($imagePath1, $imagePath2);
        echo "Similarity percentage: " . $similarity . "%\n";

        echo '<form method="post" action="feedback.php">';
        echo '<input type="hidden" name="imagePath1" value="' . $imagePath1 . '">';
        echo '<input type="hidden" name="imagePath2" value="' . $imagePath2 . '">';
        echo '<input type="hidden" name="calculatedSimilarity" value="' . $similarity . '">';
        echo 'Is the similarity calculation adequate? (Does it correctly reflect whether the images are similar or not?)<br>';
        echo '<input type="radio" name="response" value="y"> Yes<br>';
        echo '<input type="radio" name="response" value="n"> No<br>';
        echo '<input type="submit" value="Submit">';
        echo '</form>';

        $learningResults = $comparator->analyzeLearning();
        if ($learningResults) {
            echo "Average similarity for similar images: " . $learningResults['averageSimilar'] . "%\n";
            echo "Average similarity for different images: " . $learningResults['averageDifferent'] . "%\n";
        }
    } elseif (isset($uploadResults['errors'])) {
        foreach ($uploadResults['errors'] as $error) {
            echo $error . "<br>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Image Comparison</title>
</head>
<body>
    <h1>Compare Images</h1>
    <form method="post" enctype="multipart/form-data">
        <label for="image1">Image 1:</label>
        <input type="file" name="image1" id="image1" accept="image/*" required><br><br>

        <label for="image2">Image 2:</label>
        <input type="file" name="image2" id="image2" accept="image/*" required><br><br>

        <input type="submit" value="Compare">
    </form>
</body>
</html>