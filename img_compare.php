<?php
/* PHP Image Comparison with Simple Learning Algorithm v1.02
Author: Roberto Aleman
ventics.com */

include 'config.php';

// Function to compare images and calculate the similarity percentage
function compareImages($imagePath1, $imagePath2) {
    $image1 = imagecreatefromstring(file_get_contents($imagePath1));
    $image2 = imagecreatefromstring(file_get_contents($imagePath2));

    $width1 = imagesx($image1);
    $height1 = imagesy($image1);
    $width2 = imagesx($image2);
    $height2 = imagesy($image2);

    $width = min($width1, $width2);
    $height = min($height1, $height2);
    $resizedImage1 = imagecreatetruecolor($width, $height);
    $resizedImage2 = imagecreatetruecolor($width, $height);
    imagecopyresampled($resizedImage1, $image1, 0, 0, 0, 0, $width, $height, $width1, $height1);
    imagecopyresampled($resizedImage2, $image2, 0, 0, 0, 0, $width, $height, $width2, $height2);

    imagefilter($resizedImage1, IMG_FILTER_GRAYSCALE);
    imagefilter($resizedImage2, IMG_FILTER_GRAYSCALE);

    $totalDifference = 0;
    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $color1 = imagecolorat($resizedImage1, $x, $y);
            $color2 = imagecolorat($resizedImage2, $x, $y);
            $gray1 = ($color1 >> 16) & 0xFF;
            $gray2 = ($color2 >> 16) & 0xFF;
            $totalDifference += abs($gray1 - $gray2);
        }
    }

    $maxDifference = $width * $height * 255;
    $similarity = 100 - ($totalDifference / $maxDifference * 100);

    return $similarity;
}

// Function to store learning data in the CSV file
function learning($imagePath1, $imagePath2, $calculatedSimilarity) {
    echo "Are the images similar? (y/n): ";
    $response = trim(fgets(STDIN));

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

// Function to read the CSV file and analyze the learning data
function analyzeLearning() {
    $csvFile = 'learning.csv';
    $file = fopen($csvFile, 'r');

    if ($file) {
        $similarData = [];
        $differentData = [];

        while (($row = fgetcsv($file)) !== false) {
            $similarity = floatval($row[2]);
            $response = $row[3];

            if ($response == 'y') {
                $similarData[] = $similarity;
            } elseif ($response == 'n') {
                $differentData[] = $similarity;
            }
        }

        fclose($file);

        $averageSimilar = count($similarData) > 0 ? array_sum($similarData) / count($similarData) : 0;
        $averageDifferent = count($differentData) > 0 ? array_sum($differentData) / count($differentData) : 0;

        return ['averageSimilar' => $averageSimilar, 'averageDifferent' => $averageDifferent];
    }

    return null;
}

// Function to validate uploaded images
function validateImages($image1, $image2) {
    global $max_file_size, $allowed_types;

    $errors = [];

    if ($image1['error'] !== UPLOAD_ERR_OK || $image2['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Error uploading images.";
    }

    if ($image1['size'] > $max_file_size || $image2['size'] > $max_file_size) {
        $errors[] = "Image size exceeds the limit of " . ($max_file_size / 1024 / 1024) . "MB.";
    }

    if (!in_array($image1['type'], $allowed_types) || !in_array($image2['type'], $allowed_types)) {
        $errors[] = "Only JPEG, PNG, or GIF images are allowed.";
    }

    return $errors;
}

// Process the form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = validateImages($_FILES['image1'], $_FILES['image2']);

    if (empty($errors)) {
        $imagePath1 = 'uploads/' . basename($_FILES['image1']['name']);
        $imagePath2 = 'uploads/' . basename($_FILES['image2']['name']);

        if (move_uploaded_file($_FILES['image1']['tmp_name'], $imagePath1) && move_uploaded_file($_FILES['image2']['tmp_name'], $imagePath2)) {
            // Display the uploaded images side by side
            echo '<div style="display: flex;">';
            echo '<img src="' . $imagePath1 . '" style="max-width: 300px; margin-right: 10px;">';
            echo '<img src="' . $imagePath2 . '" style="max-width: 300px;">';
            echo '</div><br>';

            $similarity = compareImages($imagePath1, $imagePath2);
            echo "Similarity percentage: " . $similarity . "%\n";

            // Form for user feedback (corrected question)
            echo '<form method="post" action="feedback.php">';
            echo '<input type="hidden" name="imagePath1" value="' . $imagePath1 . '">';
            echo '<input type="hidden" name="imagePath2" value="' . $imagePath2 . '">';
            echo '<input type="hidden" name="calculatedSimilarity" value="' . $similarity . '">';
            echo 'Is the similarity calculation adequate? (Does it correctly reflect whether the images are similar or not?)<br>';
            echo '<input type="radio" name="response" value="y"> Yes<br>';
            echo '<input type="radio" name="response" value="n"> No<br>';
            echo '<input type="submit" value="Submit">';
            echo '</form>';

            $learningResults = analyzeLearning();
            if ($learningResults) {
                echo "Average similarity for similar images: " . $learningResults['averageSimilar'] . "%\n";
                echo "Average similarity for different images: " . $learningResults['averageDifferent'] . "%\n";
            }
        } else {
            echo "Error saving images.";
        }
    } else {
        foreach ($errors as $error) {
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