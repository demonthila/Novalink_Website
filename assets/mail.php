<?php
/**
 * Handles both:
 * - Contact form (name, email, message)
 * - Job application form (name, email, phone, cover_letter, position, portfolio, CV attachment)
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo 'There was a problem with your submission, please try again.';
    exit;
}

$name  = trim($_POST['name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$message = trim($_POST['message'] ?? '');

// Contact form: name, email, message (no CV)
$isContactForm = !empty($message) && !isset($_FILES['cv']) && empty($_POST['cover_letter'] ?? '');

if ($isContactForm) {
    // ---- CONTACT FORM ----
    if (empty($name) || empty($email) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo 'Please fill in all required fields (name, email, and message) with a valid email address.';
        exit;
    }

    $recipient = 'info@novalinkinnovations.com';
    $subject   = 'Contact Form – ' . mb_substr($message, 0, 50) . (mb_strlen($message) > 50 ? '…' : '');
    $bodyText  = "New message from the Novalink website contact form.\n\n";
    $bodyText .= "Name: {$name}\n";
    $bodyText .= "Email: {$email}\n\n";
    $bodyText .= "Message:\n{$message}\n";

    $headers  = "From: {$name} <{$email}>\r\n";
    $headers .= "Reply-To: {$email}\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    if (mail($recipient, $subject, $bodyText, $headers)) {
        http_response_code(200);
        echo 'Thank you! Your message has been sent. We will get back to you soon.';
    } else {
        http_response_code(500);
        echo 'Sorry, we could not send your message. Please try again or email us at info@novalinkinnovations.com.';
    }
    exit;
}

// ---- JOB APPLICATION FORM ----
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

$recipient = 'careers@novalinkinnovations.com';
$subject   = 'New Job Application – ' . ($position ?: 'Unspecified role');

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

$emailBody  = "--{$boundary}\r\n";
$emailBody .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
$emailBody .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$emailBody .= "{$bodyText}\r\n";
$emailBody .= "--{$boundary}\r\n";
$emailBody .= "Content-Type: {$cvMime}; name=\"{$cvFilename}\"\r\n";
$emailBody .= "Content-Transfer-Encoding: base64\r\n";
$emailBody .= "Content-Disposition: attachment; filename=\"{$cvFilename}\"\r\n\r\n";
$emailBody .= chunk_split(base64_encode($cvContent)) . "\r\n";
$emailBody .= "--{$boundary}--";

if (mail($recipient, $subject, $emailBody, $headers)) {
    http_response_code(200);
    echo 'Thank you! Your application has been submitted.';
} else {
    http_response_code(500);
    echo 'Oops! Something went wrong and we could not send your application.';
}
