<?php
namespace App;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/config.php'; // Load SMTP constants

class Mailer {
    public static function sendVerificationEmail($email, $name, $link, $lang = 'en') {
        $mail = new PHPMailer(true);
        try {
            // Localized Content
            $dict = [
                'en' => [
                    'subject' => 'Verify your MyGoToGym Account',
                    'welcome' => 'Welcome to MyGoToGym!',
                    'body'    => "Hello $name, please click the button below to verify your email and activate your account.",
                    'button'  => 'Verify My Account',
                    'footer'  => "If you didn't create an account, you can safely ignore this email."
                ],
                'lv' => [
                    'subject' => 'Apstipriniet savu MyGoToGym kontu',
                    'welcome' => 'Laipni lūdzam MyGoToGym!',
                    'body'    => "Sveiki $name, lūdzu, noklikšķiniet uz zemāk esošās pogas, lai apstiprinātu savu e-pastu un aktivizētu kontu.",
                    'button'  => 'Apstiprināt manu kontu',
                    'footer'  => "Ja neizveidojāt kontu, varat droši ignorēt šo e-pastu."
                ],
                'ru' => [
                    'subject' => 'Подтвердите свою учетную запись MyGoToGym',
                    'welcome' => 'Добро пожаловать в MyGoToGym!',
                    'body'    => "Здравствуйте, $name, пожалуйста, нажмите на кнопку ниже, чтобы подтвердить свой адрес электронной почты и активировать вашу учетную запись.",
                    'button'  => 'Подтвердить мой аккаунт',
                    'footer'  => "Если вы не создавали учетную запись, вы можете спокойно игнорировать это письмо."
                ]
            ];

            // Fallback to English if language not supported
            $content = $dict[$lang] ?? $dict['en'];

            $mail->isSMTP();
            $mail->Host       = SMTP_HOST; 
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME; 
            $mail->Password   = SMTP_PASSWORD; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8'; // Ensure proper character encoding for international characters

            // Use the configured SMTP_USERNAME as the 'From' address
            $mail->setFrom(SMTP_USERNAME, 'MyGoToGym'); 
            $mail->addAddress($email, $name);

            $mail->isHTML(true);
            $mail->Subject = $content['subject'];
            
            // HTML Body for the email
            $mail->Body    = "
                <div style='background: #0b0b0f; color: white; padding: 40px; font-family: sans-serif; border-radius: 20px; text-align: center;'>
                    <h1 style='color: #7c5cff;'>{$content['welcome']}</h1>
                    <p style='font-size: 1.1rem;'>{$content['body']}</p>
                    <br>
                    <a href='$link' style='background: linear-gradient(135deg, #7c5cff, #00d4ff); color: white; padding: 15px 30px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block;'>{$content['button']}</a>
                    <p style='margin-top: 30px; color: rgba(255,255,255,0.4); font-size: 0.8rem;'>{$content['footer']}</p>
                </div>
            ";
            
            // Plain text AltBody for email clients that don't support HTML
            $mail->AltBody = "{$content['welcome']}\n\n{$content['body']}\n\nLink: $link\n\n{$content['footer']}";

            return $mail->send();
        } catch (Exception $e) {
            error_log("Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }

    public static function sendResetEmail($email, $name, $link, $lang = 'en') {
        $mail = new PHPMailer(true);
        try {
            $dict = [
                'en' => [
                    'subject' => 'Reset your MyGoToGym Password',
                    'welcome' => 'Password Reset',
                    'body'    => 'You requested a password reset. Click the button below to set a new password. This link expires in 1 hour.',
                    'button'  => 'Reset Password',
                    'footer'  => 'If you did not request this, please ignore this email.'
                ],
                'lv' => [
                    'subject' => 'Atiestatiet savu MyGoToGym paroli',
                    'welcome' => 'Paroles atiestatīšana',
                    'body'    => 'Jūs pieprasījāt paroles atiestatīšanu. Noklikšķiniet uz pogas, lai iestatītu jaunu paroli. Šī saite ir derīga 1 stundu.',
                    'button'  => 'Atiestatīt paroli',
                    'footer'  => 'Ja jūs to nepieprasījāt, ignorējiet šo e-pastu.'
                ],
                'ru' => [
                    'subject' => 'Сброс пароля MyGoToGym',
                    'welcome' => 'Сброс пароля',
                    'body'    => 'Вы запросили сброс пароля. Нажмите кнопку ниже, чтобы установить новый пароль. Ссылка действительна 1 час.',
                    'button'  => 'Сбросить пароль',
                    'footer'  => 'Если вы не запрашивали это, просто проигнорируйте письмо.'
                ]
            ];

            $content = $dict[$lang] ?? $dict['en'];

            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(SMTP_USERNAME, 'MyGoToGym');
            $mail->addAddress($email, $name);

            $mail->isHTML(true);
            $mail->Subject = $content['subject'];
            $mail->Body    = "
                <div style='background: #0b0b0f; color: white; padding: 40px; font-family: sans-serif; border-radius: 20px; text-align: center;'>
                    <h1 style='color: #7c5cff;'>{$content['welcome']}</h1>
                    <p style='font-size: 1.1rem;'>{$content['body']}</p>
                    <br>
                    <a href='$link' style='background: linear-gradient(135deg, #7c5cff, #00d4ff); color: white; padding: 15px 30px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block;'>{$content['button']}</a>
                    <p style='margin-top: 30px; color: rgba(255,255,255,0.4); font-size: 0.8rem;'>{$content['footer']}</p>
                </div>";
            
            return $mail->send();
        } catch (Exception $e) {
            return false;
        }
    }
}