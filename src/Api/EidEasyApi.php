<?php

namespace EidEasy\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class EidEasyApi
{
    private $guzzle;
    private $clientId;
    private $secret;
    private $apiUrl;

    public function __construct($clientId = null, $secret = null, $apiUrl = null)
    {
        $this->clientId = $clientId ?? env('EID_CLIENT_ID');
        $this->secret   = $secret ?? env('EID_SECRET');
        $this->apiUrl   = $apiUrl ?? env('EID_API_URL', null) ?? "https://id.eideasy.com";

        if (function_exists("app")) {
            $this->guzzle = app(Client::class);
        } else {
            $this->guzzle = new Client();
        }
    }

    /**
     * @param string $docId
     */
    public function getSignedFile(string $docId): array
    {
        return $this->sendRequest('/api/signatures/download-signed-file', [
            'client_id' => $this->clientId,
            'secret'    => $this->secret,
            'doc_id'    => $docId,
        ]);
    }

    /**
     * @param $files array
     * @param null|string $containerType
     * @param null|string $profile
     */
    public function prepareFiles($files, $containerType = 'xades', $profile = 'LT'): array
    {
        return $this->sendRequest('/api/signatures/prepare-files-for-signing', [
            'client_id'      => $this->clientId,
            'secret'         => $this->secret,
            'container_type' => $containerType,
            'baseline'       => $profile,
            'files'          => $files
        ]);
    }

    protected function sendRequest($path, $body = [], $method = 'POST'): array
    {
        try {
            if ($method === 'POST') {
                $response = $this->guzzle->post($this->apiUrl . $path, [
                    'headers' => [
                        'Accept' => 'application/json'
                    ],
                    'json'    => $body,
                ]);
            } elseif ($method === 'GET') {
                $response = $this->guzzle->get($this->apiUrl . $path, [
                    'headers' => [
                        'Accept' => 'application/json'
                    ],
                    'query'   => $body,
                ]);
            } else {
                $response = $this->guzzle->request($method, $this->apiUrl . $path, [
                    'headers' => [
                        'Accept' => 'application/json'
                    ],
                    'json'    => $body,
                ]);
            }
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if (!$response) {
                [
                    'status'  => 'error',
                    'message' => 'No response body: ' . $e->getMessage(),
                ];
            }
            $body     = $response->getBody()->getContents();
            $jsonBody = json_decode($body);
            if (!$jsonBody) {
                return [
                    'status'  => 'error',
                    'message' => 'Response not json: ' . $body,
                ];
            }

            return $jsonBody;
        }

        return json_decode($response->getBody()->getContents());
    }
}
