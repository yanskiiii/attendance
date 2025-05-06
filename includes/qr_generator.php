<?php
require_once 'vendor/autoload.php';
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Font\NotoSans;

function generateUserQRCode($userData) {
    // Create QR code data
    $qrData = json_encode([
        'user_id' => $userData['id'],
        'username' => $userData['username'],
        'full_name' => $userData['full_name'],
        'email' => $userData['email'],
        'registration_date' => date('Y-m-d H:i:s'),
        'timestamp' => time(),
        'token' => md5($userData['id'] . time() . 'secret_key')
    ]);

    // Create QR code
    $qrCode = QrCode::create($qrData)
        ->setSize(300)
        ->setMargin(10)
        ->setEncoding(new Encoding('UTF-8'))
        ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
        ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
        ->setForegroundColor(new Color(44, 62, 80)) // Dark blue color
        ->setBackgroundColor(new Color(255, 255, 255)); // White background

    // Create label
    $label = Label::create($userData['username'])
        ->setTextColor(new Color(44, 62, 80))
        ->setFont(new NotoSans(20))
        ->setAlignment(new LabelAlignmentCenter());

    // Create writer
    $writer = new PngWriter();

    // Generate QR code with label
    $result = $writer->write($qrCode, null, $label);

    // Save QR code
    $qrPath = 'assets/qrcodes/' . $userData['username'] . '_' . time() . '.png';
    $result->saveToFile($qrPath);

    return $qrPath;
}

function getQRCodePath($username) {
    $qrDir = 'assets/qrcodes/';
    $files = glob($qrDir . $username . '_*.png');
    
    if (!empty($files)) {
        // Get the most recent QR code
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        return $files[0];
    }
    
    return null;
}
?> 