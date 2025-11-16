<?php
defined('PREVENT_DIRECT_ACCESS') or exit('No direct script access allowed');

/**
 * Helper: mail_helper.php
 * 
 * Automatically generated via CLI.
 */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


function sendMail($recipient, $subject, $body, $attachmentPath = null)
{
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF; // Disable debug output
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ak4unt@gmail.com';
        $mail->Password = 'mdxj rksc outd iqwj';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        //Recipients
        $mail->setFrom('ak4unt@gmail.com', 'Vehicle Rental System');
        $mail->addAddress($recipient);

        //Attachments
        // $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
        // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

        //Content
        $mail->isHTML(true);                                  //Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);

        if (!empty($attachmentPath) && file_exists($attachmentPath)) {
            $mail->addAttachment($attachmentPath);
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Send verification email to new user
 */
function sendVerificationEmail($email, $name, $token) {
    try {
        if (empty($token)) {
            error_log("sendVerificationEmail: Token is empty for $email");
            return false;
        }
        
        $verificationUrl = rtrim($_SERVER['HTTP_HOST'], '/') . '/Vehicle-Rental/api/auth/verify-email?token=' . urlencode($token);
        
        error_log("sendVerificationEmail: Verification URL = http://$verificationUrl");
        
        $subject = 'Verify Your Email - Vehicle Rental System';
        
        $body = "
            <h2>Welcome to Vehicle Rental System!</h2>
            <p>Hi $name,</p>
            <p>Thank you for registering with us. Please verify your email address by clicking the link below:</p>
            <p><a href='http://$verificationUrl' style='background-color: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Verify Email Address</a></p>
            <p>Or copy and paste this link in your browser:</p>
            <p>http://$verificationUrl</p>
            <p>This link will expire in 24 hours.</p>
            <p>If you did not create an account, please ignore this email.</p>
            <br>
            <p>Best regards,<br>Vehicle Rental System Team</p>
        ";
        
        error_log("sendVerificationEmail: Calling sendMail for $email");
        $result = sendMail($email, $subject, $body);
        error_log("sendVerificationEmail: sendMail returned " . ($result ? 'true' : 'false'));
        
        return $result;
    } catch (Exception $e) {
        error_log("sendVerificationEmail exception: " . $e->getMessage());
        return false;
    }
}