<?php

if ($argc < 3) {
    fwrite(STDERR, "Usage: php scripts/openapi_to_postman.php <openapi.json> <postman.json>\n");
    exit(1);
}

[$script, $openApiPath, $postmanPath] = $argv;

$openApi = json_decode(file_get_contents($openApiPath), true);

if (!is_array($openApi)) {
    fwrite(STDERR, "Unable to read OpenAPI document: {$openApiPath}\n");
    exit(1);
}

$baseUrl = $openApi['servers'][0]['url'] ?? '{{base_url}}';
$baseUrl = rtrim($baseUrl, '/');

$collection = [
    'info' => [
        'name' => trim(($openApi['info']['title'] ?? 'External App API') . ' - Postman'),
        'description' => $openApi['info']['description'] ?? '',
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'item' => [],
    'variable' => [
        [
            'key' => 'base_url',
            'value' => $baseUrl,
            'type' => 'string',
        ],
        [
            'key' => 'access_token',
            'value' => '',
            'type' => 'string',
        ],
        [
            'key' => 'mandate_reference',
            'value' => 'DO0000123',
            'type' => 'string',
        ],
    ],
];

$folders = [];

foreach (($openApi['paths'] ?? []) as $path => $operations) {
    foreach ($operations as $method => $operation) {
        $tag = normalizeTag($operation['tags'][0] ?? 'External App');
        $folders[$tag] ??= [
            'name' => $tag,
            'item' => [],
        ];

        $folders[$tag]['item'][] = postmanItem($method, $path, $operation, $openApi);
    }
}

$collection['item'] = array_values($folders);

file_put_contents(
    $postmanPath,
    json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
);

function postmanItem(string $method, string $path, array $operation, array $openApi): array
{
    $method = strtoupper($method);
    $postmanPath = preg_replace('/\{([^}]+)\}/', ':$1', $path);
    $rawPath = ltrim($postmanPath, '/');
    $query = [];
    $pathVariables = [];

    foreach (($operation['parameters'] ?? []) as $parameter) {
        $name = $parameter['name'] ?? null;

        if (!$name) {
            continue;
        }

        if (($parameter['in'] ?? null) === 'query') {
            $query[] = [
                'key' => $name,
                'value' => sampleValue($parameter['schema'] ?? [], $parameter),
                'description' => $parameter['description'] ?? '',
                'disabled' => empty($parameter['required']),
            ];
        }

        if (($parameter['in'] ?? null) === 'path') {
            $pathVariables[] = [
                'key' => $name,
                'value' => $name === 'reference' ? '{{mandate_reference}}' : sampleValue($parameter['schema'] ?? [], $parameter),
                'description' => $parameter['description'] ?? '',
            ];
        }
    }

    $summary = $operation['summary'] ?? '';

    $item = [
        'name' => $summary ?: "{$method} {$path}",
        'request' => [
            'method' => $method,
            'header' => [
                [
                    'key' => 'Accept',
                    'value' => 'application/json',
                ],
            ],
            'url' => [
                'raw' => '{{base_url}}/' . $rawPath,
                'host' => ['{{base_url}}'],
                'path' => explode('/', $rawPath),
            ],
            'description' => trim(($operation['description'] ?? '') . "\n\n" . $summary),
        ],
    ];

    if ($query) {
        $item['request']['url']['query'] = $query;
    }

    if ($pathVariables) {
        $item['request']['url']['variable'] = $pathVariables;
    }

    if (!str_contains($path, '/token')) {
        $item['request']['auth'] = [
            'type' => 'bearer',
            'bearer' => [
                [
                    'key' => 'token',
                    'value' => '{{access_token}}',
                    'type' => 'string',
                ],
            ],
        ];
    }

    $schema = $operation['requestBody']['content']['application/json']['schema'] ?? null;

    if ($schema) {
        $item['request']['header'][] = [
            'key' => 'Content-Type',
            'value' => 'application/json',
        ];
        $item['request']['body'] = [
            'mode' => 'raw',
            'raw' => json_encode(samplePayload($schema, $openApi), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'options' => [
                'raw' => [
                    'language' => 'json',
                ],
            ],
        ];
    }

    return $item;
}

function normalizeTag(string $tag): string
{
    return match ($tag) {
        'PaymentMandate' => 'Payment Mandates',
        'PaymentCollection' => 'Payment Collections',
        default => $tag,
    };
}

function samplePayload(array $schema, array $openApi): array
{
    $schema = resolveSchema($schema, $openApi);
    $payload = [];

    foreach (($schema['properties'] ?? []) as $name => $property) {
        $payload[$name] = sampleValue(resolveSchema($property, $openApi), $property);
    }

    return $payload;
}

function resolveSchema(array $schema, array $openApi): array
{
    if (isset($schema['$ref'])) {
        $parts = explode('/', ltrim($schema['$ref'], '#/'));
        $resolved = $openApi;

        foreach ($parts as $part) {
            $resolved = $resolved[$part] ?? [];
        }

        return is_array($resolved) ? $resolved : [];
    }

    if (isset($schema['allOf'])) {
        $merged = [
            'type' => 'object',
            'properties' => [],
            'required' => [],
        ];

        foreach ($schema['allOf'] as $part) {
            $resolved = resolveSchema($part, $openApi);
            $merged['properties'] = array_merge($merged['properties'], $resolved['properties'] ?? []);
            $merged['required'] = array_values(array_unique(array_merge($merged['required'], $resolved['required'] ?? [])));
        }

        return $merged;
    }

    return $schema;
}

function sampleValue(array $schema, array $source = []): mixed
{
    if (array_key_exists('example', $source)) {
        return $source['example'];
    }

    if (array_key_exists('example', $schema)) {
        return $schema['example'];
    }

    $type = $schema['type'] ?? 'string';

    return match ($type) {
        'integer' => 1,
        'number' => 1000,
        'boolean' => true,
        'array' => [],
        'object' => new stdClass(),
        default => ($schema['format'] ?? null) === 'date' ? date('Y-m-d') : '',
    };
}
