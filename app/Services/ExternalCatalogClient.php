<?php

namespace App\Services;

use RuntimeException;

class ExternalCatalogClient
{
    /**
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function getJson(string $url, array $query): array
    {
        $context = stream_context_create([
            'http' => [
                'header' => "Accept: application/json\r\n",
                'ignore_errors' => true,
                'method' => 'GET',
                'timeout' => 10,
            ],
        ]);

        $response = file_get_contents($url.'?'.http_build_query($query), false, $context);

        if ($response === false) {
            throw new RuntimeException('External request failed.');
        }

        $decoded = json_decode($response, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('External response is not valid JSON.');
        }

        return $decoded;
    }
}
