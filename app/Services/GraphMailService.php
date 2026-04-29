<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class GraphMailService
{
    protected $tenantId;
    protected $clientId;
    protected $clientSecret;
    protected $userId;

    public function __construct()
    {
        $this->tenantId = env('GRAPH_TENANT_ID');
        $this->clientId = env('GRAPH_CLIENT_ID');
        $this->clientSecret = env('ID_Secret') ?? env('GRAPH_CLIENT_SECRET');
        $this->userId = env('GRAPH_USER_ID') ?? config('mail.from.address') ?? 'no-reply@grupoindi.com';
    }

    /**
     * Obtiene el token de acceso de Microsoft Azure
     */
    protected function getAccessToken()
    {
        $client = new Client();
        $url = "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token";

        try {
            $response = $client->post($url, [
                'verify' => false, // Desactivar verificación SSL para local
                'form_params' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents());
            if (isset($data->access_token)) {
                Log::info("Token MS Graph obtenido exitosamente. Prefijo: " . substr($data->access_token, 0, 10) . "...");
                return $data->access_token;
            } else {
                Log::error("Respuesta de Token MS Graph no contiene access_token: " . json_encode($data));
                return null;
            }
        } catch (\Exception $e) {
            Log::error("Error obteniendo Token de MS Graph: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envía un correo electrónico con soporte para archivos adjuntos
     */
    public function send($to, $subject, $content, $attachments = [])
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return false;
        }

        $client = new Client();
        $url = "https://graph.microsoft.com/v1.0/users/{$this->userId}/sendMail";

        // Preparar destinatarios
        $toRecipients = [];
        $emails = is_array($to) ? $to : [$to];
        foreach ($emails as $email) {
            $toRecipients[] = [
                'emailAddress' => [
                    'address' => $email,
                ],
            ];
        }

        // Preparar adjuntos
        $graphAttachments = [];
        foreach ($attachments as $attachment) {
            $isInline = isset($attachment['isInline']) && $attachment['isInline'];
            
            $item = [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => $attachment['name'] ?? 'attachment',
                'contentType' => $attachment['contentType'] ?? 'application/octet-stream',
                'contentBytes' => base64_encode($attachment['content'] ?? (isset($attachment['path']) ? file_get_contents($attachment['path']) : '')),
            ];

            if ($isInline) {
                $item['isInline'] = true;
                $item['contentId'] = $attachment['contentId'] ?? $attachment['name'];
            }

            $graphAttachments[] = $item;
        }

        $message = [
            'subject' => $subject,
            'body' => [
                'contentType' => 'HTML',
                'content' => $content,
            ],
            'toRecipients' => $toRecipients,
        ];

        if (!empty($graphAttachments)) {
            $message['attachments'] = $graphAttachments;
        }

        try {
            $response = $client->post($url, [
                'verify' => false, // Desactivar verificación SSL para local
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'message' => $message,
                    'saveToSentItems' => 'true',
                ],
            ]);

            return $response->getStatusCode() === 202;
        } catch (\Exception $e) {
            Log::error("Error enviando correo vía MS Graph: " . $e->getMessage());
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                Log::error("Respuesta error MS Graph: " . $e->getResponse()->getBody()->getContents());
            }
            return false;
        }
    }
}
