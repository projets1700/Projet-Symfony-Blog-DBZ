<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AutoIllustrationGenerator
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $apiKey,
        private readonly string $imageModel,
    ) {
    }

    public function isEnabled(): bool
    {
        return is_string($this->apiKey) && '' !== trim($this->apiKey);
    }

    public function generateAndStore(string $articleTitle, string $articleSubject): ?string
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            $prompt = sprintf(
                'Create a dynamic anime-style illustration related to Dragon Ball Z. Subject: %s. Title context: %s. No text, no watermark, no logo.',
                $articleSubject,
                $articleTitle
            );

            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/images/generations', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->imageModel,
                    'prompt' => $prompt,
                    'size' => '1024x1024',
                    'quality' => 'medium',
                ],
                'timeout' => 60,
            ]);

            $payload = $response->toArray(false);
            $base64 = $payload['data'][0]['b64_json'] ?? null;
            if (!is_string($base64) || '' === $base64) {
                return null;
            }

            $imageBinary = base64_decode($base64, true);
            if (!is_string($imageBinary) || '' === $imageBinary) {
                return null;
            }

            $targetDir = dirname(__DIR__, 2).'/public/uploads/generated';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $filename = 'auto-'.date('Ymd-His').'-'.substr(bin2hex(random_bytes(3)), 0, 6).'.png';
            $targetPath = $targetDir.'/'.$filename;
            file_put_contents($targetPath, $imageBinary);

            return '/uploads/generated/'.$filename;
        } catch (\Throwable) {
            return null;
        }
    }
}
