<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * EmailService
 * 
 * Handles all email sending operations for the Constituency Development System.
 */
class EmailService
{
    private PHPMailer $mailer;
    private string $fromEmail;
    private string $fromName;
    private string $templatePath;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configureSMTP();
        
        $this->fromEmail = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@constituency.gov';
        $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'Constituency Hub';
        $this->templatePath = dirname(__DIR__, 2) . '/templates/email/';
    }

    private function configureSMTP(): void
    {
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $_ENV['MAIL_USERNAME'] ?? '';
            $this->mailer->Password = $_ENV['MAIL_PASSWORD'] ?? '';
            $this->mailer->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
            $this->mailer->Port = (int)($_ENV['MAIL_PORT'] ?? 587);
            $this->mailer->CharSet = 'UTF-8';
        } catch (Exception $e) {
            error_log('SMTP configuration error: ' . $e->getMessage());
        }
    }

    private function loadTemplateConfig(string $templateName): ?array
    {
        $jsonPath = $this->templatePath . $templateName . '.json';
        if (!file_exists($jsonPath)) return null;
        return json_decode(file_get_contents($jsonPath), true);
    }

    private function loadTemplateContent(string $filename): ?string
    {
        $htmlPath = $this->templatePath . $filename;
        if (!file_exists($htmlPath)) return null;
        return file_get_contents($htmlPath);
    }

    private function replacePlaceholders(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', (string)$value, $template);
        }
        return $template;
    }

    private function buildEmailFromTemplate(string $templateName, array $variables = []): ?array
    {
        $config = $this->loadTemplateConfig($templateName);
        if (!$config) return null;
        
        $contentHtml = $this->loadTemplateContent($config['content_file']);
        if (!$contentHtml) return null;
        
        $allVariables = array_merge([
            'app_url' => $_ENV['APP_URL'] ?? 'http://localhost:5173',
            'support_email' => $this->fromEmail,
            'year' => date('Y'),
        ], $variables);
        
        $content = $this->replacePlaceholders($contentHtml, $allVariables);
        
        // Simple base template fallback if base.html missing
        $basePath = $this->templatePath . 'base.html';
        $baseTemplate = file_exists($basePath) ? file_get_contents($basePath) : '{{content}}';
        
        $finalHtml = $this->replacePlaceholders($baseTemplate, array_merge($allVariables, ['content' => $content]));
        
        return [
            'subject' => $this->replacePlaceholders($config['subject'], $allVariables),
            'body' => $finalHtml,
        ];
    }

    public function sendWelcomeEmail(User $user): bool
    {
        return $this->sendTemplateEmail($user, 'welcome', [
            'user_name' => htmlspecialchars($user->getFullName()),
            'user_email' => htmlspecialchars($user->email),
        ]);
    }

    public function sendEmailVerificationEmail(User $user, string $verificationUrl): bool
    {
        return $this->sendTemplateEmail($user, 'email_verification', [
            'user_name' => htmlspecialchars($user->getFullName()),
            'verification_url' => $verificationUrl,
        ]);
    }

    public function sendPasswordResetEmail(User $user, string $token): bool
    {
        $resetUrl = ($_ENV['FRONTEND_URL'] ?? 'http://localhost:5173') . "/reset-password?token={$token}&email=" . urlencode($user->email);
        return $this->sendTemplateEmail($user, 'password_reset', [
            'user_name' => htmlspecialchars($user->getFullName()),
            'reset_url' => $resetUrl,
        ]);
    }

    public function sendPasswordChangedEmail(User $user): bool
    {
        return $this->sendTemplateEmail($user, 'password_changed', [
            'user_name' => htmlspecialchars($user->getFullName()),
            'timestamp' => date('F j, Y \a\t g:i A T'),
        ]);
    }

    private function sendTemplateEmail(User $user, string $template, array $vars): bool
    {
        try {
            $emailData = $this->buildEmailFromTemplate($template, $vars);
            if (!$emailData) return false;

            $this->mailer->clearAddresses();
            $this->mailer->setFrom($this->fromEmail, $this->fromName);
            $this->mailer->addAddress($user->email, $user->getFullName());
            
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $emailData['subject'];
            $this->mailer->Body = $emailData['body'];
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email Error ({$template}): " . $e->getMessage());
            return false;
        }
    }

    public function send(string $to, string $subject, string $body, ?string $fromName = null): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->setFrom($this->fromEmail, $fromName ?? $this->fromName);
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            return $this->mailer->send();
        } catch (Exception $e) {
            return false;
        }
    }
}
