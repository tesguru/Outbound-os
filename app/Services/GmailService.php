<?php

namespace App\Services;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Google\Service\Gmail\Draft;
use App\Models\GmailAccount;
use Illuminate\Support\Facades\Log;

class GmailService
{
    protected $client;
    protected $gmail;
    protected $account;

    public function __construct(GmailAccount $account)
    {
        $this->account = $account;

        $this->client = new Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect'));
        $this->client->setScopes([
            Gmail::GMAIL_COMPOSE,
            Gmail::GMAIL_MODIFY,
            Gmail::GMAIL_LABELS,
            Gmail::GMAIL_READONLY,
        ]);

        $this->refreshIfExpired();
        $this->gmail = new Gmail($this->client);
    }

    protected function refreshIfExpired()
    {
        $token = is_array($this->account->google_token)
            ? $this->account->google_token
            : json_decode($this->account->google_token, true);

        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired()) {
            if ($this->account->google_refresh_token) {
                $this->client->fetchAccessTokenWithRefreshToken($this->account->google_refresh_token);
                $newToken = $this->client->getAccessToken();
                $this->account->update(['google_token' => json_encode($newToken)]);
            }
        }
    }

    public function createDraft($to, $subject, $body)
    {
        try {
            $rawMessage = $this->createRawMessage($to, $subject, $body);

            $message = new Message();
            $message->setRaw($rawMessage);

            $draft = new Draft();
            $draft->setMessage($message);

            $createdDraft = $this->gmail->users_drafts->create('me', $draft);

            return [
                'success'    => true,
                'draft_id'   => $createdDraft->getId(),
                'message_id' => $createdDraft->getMessage()->getId(),
                'thread_id'  => $createdDraft->getMessage()->getThreadId(),
            ];

        } catch (\Exception $e) {
            Log::error('createDraft failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createFollowUpDraft($to, $subject, $body, $threadId, $inReplyTo = null, $references = null)
    {
        try {
            $rawMessage = $this->createRawMessage($to, $subject, $body, $inReplyTo, $references);

            $message = new Message();
            $message->setRaw($rawMessage);
            $message->setThreadId($threadId);

            $draft = new Draft();
            $draft->setMessage($message);

            $createdDraft = $this->gmail->users_drafts->create('me', $draft);

            return [
                'success'    => true,
                'draft_id'   => $createdDraft->getId(),
                'message_id' => $createdDraft->getMessage()->getId(),
                'thread_id'  => $createdDraft->getMessage()->getThreadId(),
            ];

        } catch (\Exception $e) {
            Log::error('createFollowUpDraft failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function createRawMessage($to, $subject, $body, $inReplyTo = null, $references = null)
    {
        $from = $this->account->email;

        $headers = [
            'From'                     => $from,
            'To'                       => $to,
            'Subject'                  => $subject,
            'MIME-Version'             => '1.0',
            'Content-Type'             => 'text/plain; charset=utf-8',
            'Content-Transfer-Encoding'=> '7bit',
        ];

        if ($inReplyTo)  $headers['In-Reply-To'] = $inReplyTo;
        if ($references) $headers['References']  = $references;

        $headerStr = '';
        foreach ($headers as $key => $value) {
            $headerStr .= "$key: $value\r\n";
        }

        $rawMessage = $headerStr . "\r\n" . $body;
        return rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');
    }

    public function getOrCreateLabel($labelName)
    {
        try {
            $labels = $this->gmail->users_labels->listUsersLabels('me');

            foreach ($labels->getLabels() as $label) {
                if ($label->getName() === $labelName) {
                    return [
                        'success'    => true,
                        'label_id'   => $label->getId(),
                        'label_name' => $label->getName(),
                    ];
                }
            }

            $newLabel = new \Google\Service\Gmail\Label();
            $newLabel->setName($labelName);
            $newLabel->setLabelListVisibility('labelShow');
            $newLabel->setMessageListVisibility('show');

            $createdLabel = $this->gmail->users_labels->create('me', $newLabel);

            return [
                'success'    => true,
                'label_id'   => $createdLabel->getId(),
                'label_name' => $createdLabel->getName(),
            ];

        } catch (\Exception $e) {
            Log::error('getOrCreateLabel failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function addLabelToThread($threadId, $labelId)
    {
        try {
            $mods = new \Google\Service\Gmail\ModifyThreadRequest();
            $mods->setAddLabelIds([$labelId]);
            $this->gmail->users_threads->modify('me', $threadId, $mods);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getThreadMessages($threadId)
    {
        try {
            $thread   = $this->gmail->users_threads->get('me', $threadId);
            $messages = $thread->getMessages();

            if (empty($messages)) {
                return ['success' => false, 'error' => 'No messages found'];
            }

            $lastMessage = end($messages);
            $headers     = $lastMessage->getPayload()->getHeaders();

            $messageId  = null;
            $references = null;
            $subject    = null;

            foreach ($headers as $header) {
                if ($header->getName() === 'Message-ID') $messageId  = $header->getValue();
                if ($header->getName() === 'References')  $references = $header->getValue();
                if ($header->getName() === 'Subject')     $subject    = $header->getValue();
            }

            return [
                'success'    => true,
                'message_id' => $messageId,
                'references' => $references,
                'subject'    => $subject,
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function threadHasBounce(string $threadId): bool
    {
        try {
            $thread   = $this->gmail->users_threads->get('me', $threadId, ['format' => 'metadata']);
            $messages = $thread->getMessages();

            foreach ($messages as $message) {
                $full    = $this->gmail->users_messages->get('me', $message->getId(), ['format' => 'metadata']);
                $payload = $full->getPayload();
                if (!$payload) continue;

                foreach ($payload->getHeaders() as $header) {
                    $name  = strtolower($header->getName());
                    $value = strtolower($header->getValue());

                    if ($name === 'return-path' && trim($value) === '<>') return true;
                    if ($name === 'content-type' && str_contains($value, 'multipart/report')) return true;
                    if ($name === 'x-failed-recipients') return true;
                    if ($name === 'auto-submitted' && $value !== 'no') return true;
                    if ($name === 'from') {
                        if (
                            str_contains($value, 'mailer-daemon') ||
                            str_contains($value, 'postmaster') ||
                            str_contains($value, 'mail delivery') ||
                            str_contains($value, 'delivery subsystem')
                        ) return true;
                    }
                    if ($name === 'subject') {
                        if (
                            str_contains($value, 'delivery failed') ||
                            str_contains($value, 'undeliverable') ||
                            str_contains($value, 'mail delivery failed') ||
                            str_contains($value, 'delivery status notification') ||
                            str_contains($value, 'returned mail') ||
                            str_contains($value, 'failed to deliver') ||
                            str_contains($value, 'unable to deliver') ||
                            str_contains($value, 'non-delivery') ||
                            str_contains($value, 'undelivered mail')
                        ) return true;
                    }
                }
            }

            return false;

        } catch (\Exception $e) {
            Log::error('threadHasBounce failed', ['thread_id' => $threadId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function getContactName(string $email): ?string
    {
        try {
            $peopleService = new \Google\Service\PeopleService($this->client);
            $results = $peopleService->people_connections->listPeopleConnections(
                'people/me',
                ['personFields' => 'names,emailAddresses']
            );

            foreach ($results->getConnections() ?? [] as $person) {
                foreach ($person->getEmailAddresses() ?? [] as $emailAddress) {
                    if (strtolower($emailAddress->getValue()) === strtolower($email)) {
                        $names = $person->getNames();
                        if (!empty($names)) {
                            return $names[0]->getDisplayName();
                        }
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('getContactName failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public static function extractNamesFromEmail($email)
    {
        $emailLower = strtolower($email);
        $parts      = explode('@', $emailLower);
        $username   = $parts[0];
        $domain     = $parts[1] ?? '';

        $freeDomains = ['gmail.com','yahoo.com','outlook.com','hotmail.com','aol.com','icloud.com','protonmail.com','zoho.com','yandex.com','mail.com'];

        if (in_array($domain, $freeDomains)) {
            $cleanUsername = preg_replace('/[0-9._-]+/', ' ', $username);
            $companyName   = self::formatName($cleanUsername ?: $username);
            $firstName     = self::formatName(explode('.', $username)[0]);
        } else {
            $domainPart  = explode('.', $domain)[0];
            $companyName = self::formatName($domainPart);
            $firstName   = self::formatName(explode('.', $username)[0]);
        }

        if (!$firstName || strlen($firstName) < 2) $firstName = 'there';

        return [
            'company_name' => $companyName,
            'first_name'   => $firstName,
        ];
    }

    private static function formatName($name)
    {
        if (!$name) return '';
        $name = str_replace(['_', '-'], ' ', $name);
        $name = preg_replace('/\s+/', ' ', trim($name));
        return ucwords(strtolower($name));
    }
}