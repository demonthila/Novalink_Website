<?php
/**
 * Job application handler with attachment support.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo 'There was a problem with your submission, please try again.';
    exit;
}

$name        = trim($_POST['name'] ?? '');
$email       = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone       = trim($_POST['phone'] ?? '');
$coverLetter = trim($_POST['cover_letter'] ?? '');
$portfolio   = trim($_POST['portfolio'] ?? '');
$position    = trim($_POST['position'] ?? '');

if (
    empty($name) ||
    empty($email) ||
    empty($phone) ||
    empty($coverLetter) ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !isset($_FILES['cv'])
) {
    http_response_code(400);
    echo 'Please complete all required fields and attach your CV.';
    exit;
}

$cvFile = $_FILES['cv'];
$allowedExtensions = ['pdf', 'doc', 'docx'];
$cvExtension = strtolower(pathinfo($cvFile['name'], PATHINFO_EXTENSION));
$maxFileSize = 8 * 1024 * 1024; // 8MB limit

if ($cvFile['error'] !== UPLOAD_ERR_OK || !in_array($cvExtension, $allowedExtensions, true) || $cvFile['size'] > $maxFileSize) {
    http_response_code(400);
    echo 'CV upload failed. Please upload a PDF or DOC file up to 8MB.';
    exit;
}

$cvContent  = file_get_contents($cvFile['tmp_name']);
$cvFilename = basename($cvFile['name']);
$cvMime     = mime_content_type($cvFile['tmp_name']) ?: 'application/octet-stream';

$recipient = "careers@novalinkinnovations.com"; // You can change the email recipient address here in the future if needed
$subject   = 'New Job Application - ' . ($position ?: 'Unspecified role');

$bodyText  = "A new job application has been submitted.\n\n";
$bodyText .= "Applicant name: {$name}\n";
$bodyText .= "Email: {$email}\n";
$bodyText .= "Phone: {$phone}\n";
$bodyText .= "Position: " . ($position ?: 'Not provided') . "\n";
$bodyText .= "Portfolio: " . ($portfolio ?: 'Not provided') . "\n\n";
$bodyText .= "Cover letter:\n{$coverLetter}\n";

$boundary = '==Multipart_Boundary_' . md5(time());
$headers  = "From: {$name} <{$email}>\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

$message  = "--{$boundary}\r\n";
$message .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$message .= "{$bodyText}\r\n";

$message .= "--{$boundary}\r\n";
$message .= "Content-Type: {$cvMime}; name=\"{$cvFilename}\"\r\n";
$message .= "Content-Transfer-Encoding: base64\r\n";
$message .= "Content-Disposition: attachment; filename=\"{$cvFilename}\"\r\n\r\n";
$message .= chunk_split(base64_encode($cvContent)) . "\r\n";
$message .= "--{$boundary}--";

if (mail($recipient, $subject, $message, $headers)) {
    http_response_code(200);
    echo 'Thank you! Your application has been submitted.';
} else {
    http_response_code(500);
    echo 'Oops! Something went wrong and we could not send your application.';
}
?>
