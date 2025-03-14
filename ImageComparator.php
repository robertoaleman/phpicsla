<?php

/* PHP Image Comparison with Simple Learning Algorithm v1.02
Author: Roberto Aleman
ventics.com */

class ImageComparator {
    private $maxFileSize;
    private $allowedTypes;
    private $uploadDir;
    private $learningFile;

    public function __construct($maxFileSize, $allowedTypes, $uploadDir = 'uploads/', $learningFile = 'learning.csv') {
        $this->maxFileSize = $maxFileSize;
        $this->allowedTypes = $allowedTypes;
        $this->uploadDir = $uploadDir;
        $this->learningFile = $learningFile;
    }

    public function compareImages($imagePath1, $imagePath2) {
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

    public function learning($imagePath1, $imagePath2, $calculatedSimilarity, $response) {
        $data = [$imagePath1, $imagePath2, $calculatedSimilarity, $response];

        $file = fopen($this->learningFile, 'a');
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

    public function analyzeLearning() {
        $file = fopen($this->learningFile, 'r');

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

    public function validateImages($image1, $image2) {
        $errors = [];

        if ($image1['error'] !== UPLOAD_ERR_OK || $image2['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading images.";
        }

        if ($image1['size'] > $this->maxFileSize || $image2['size'] > $this->maxFileSize) {
            $errors[] = "Image size exceeds the limit of " . ($this->maxFileSize / 1024 / 1024) . "MB.";
        }

        if (!in_array($image1['type'], $this->allowedTypes) || !in_array($image2['type'], $this->allowedTypes)) {
            $errors[] = "Only JPEG, PNG, or GIF images are allowed.";
        }

        return $errors;
    }

    public function processUploads($image1, $image2) {
        $errors = $this->validateImages($image1, $image2);

        if (empty($errors)) {
            $imagePath1 = $this->uploadDir . basename($image1['name']);
            $imagePath2 = $this->uploadDir . basename($image2['name']);

            if (move_uploaded_file($image1['tmp_name'], $imagePath1) && move_uploaded_file($image2['tmp_name'], $imagePath2)) {
                return ['imagePath1' => $imagePath1, 'imagePath2' => $imagePath2];
            } else {
                return ['error' => "Error saving images."];
            }
        } else {
            return ['errors' => $errors];
        }
    }
}
?>