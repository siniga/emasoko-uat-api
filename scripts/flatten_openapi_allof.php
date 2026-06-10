<?php

if ($argc < 3) {
    fwrite(STDERR, "Usage: php scripts/flatten_openapi_allof.php <input-openapi.json> <output-openapi.json>\n");
    exit(1);
}

[$script, $inputPath, $outputPath] = $argv;

$openApi = json_decode(file_get_contents($inputPath), true);

if (!is_array($openApi)) {
    fwrite(STDERR, "Unable to read OpenAPI document: {$inputPath}\n");
    exit(1);
}

$flattened = flattenNode($openApi, $openApi);

file_put_contents(
    $outputPath,
    json_encode($flattened, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
);

function flattenNode(mixed $node, array $openApi): mixed
{
    if (!is_array($node)) {
        return $node;
    }

    if (isset($node['allOf']) && is_array($node['allOf'])) {
        return flattenAllOf($node, $openApi);
    }

    foreach ($node as $key => $value) {
        $node[$key] = normalizeScalarSchema(flattenNode($value, $openApi));
    }

    return $node;
}

function flattenAllOf(array $schema, array $openApi): array
{
    $merged = [
        'type' => 'object',
        'properties' => [],
        'required' => [],
    ];

    foreach ($schema['allOf'] as $part) {
        $resolved = resolveRef($part, $openApi);
        $resolved = flattenNode($resolved, $openApi);

        foreach (($resolved['properties'] ?? []) as $name => $property) {
            $merged['properties'][$name] = normalizeScalarSchema($property);
        }

        $merged['required'] = array_values(array_unique(array_merge(
            $merged['required'],
            $resolved['required'] ?? []
        )));
    }

    foreach ($schema as $key => $value) {
        if ($key === 'allOf') {
            continue;
        }

        if ($key === 'properties') {
            $merged['properties'] = array_merge($merged['properties'], $value);
            continue;
        }

        if ($key === 'required') {
            $merged['required'] = array_values(array_unique(array_merge($merged['required'], $value)));
            continue;
        }

            $merged[$key] = normalizeScalarSchema(flattenNode($value, $openApi));
    }

    if (empty($merged['required'])) {
        unset($merged['required']);
    }

    return $merged;
}

function normalizeScalarSchema(mixed $schema): mixed
{
    if (!is_array($schema)) {
        return $schema;
    }

    if (
        ($schema['type'] ?? null) === 'object'
        && !isset($schema['properties'])
        && array_key_exists('example', $schema)
    ) {
        if (is_int($schema['example'])) {
            $schema['type'] = 'integer';
        } elseif (is_float($schema['example'])) {
            $schema['type'] = 'number';
        } elseif (is_string($schema['example'])) {
            $schema['type'] = 'string';
        } elseif (is_bool($schema['example'])) {
            $schema['type'] = 'boolean';
        }
    }

    return $schema;
}

function resolveRef(array $schema, array $openApi): array
{
    if (!isset($schema['$ref'])) {
        return $schema;
    }

    $parts = explode('/', ltrim($schema['$ref'], '#/'));
    $resolved = $openApi;

    foreach ($parts as $part) {
        $resolved = $resolved[$part] ?? [];
    }

    return is_array($resolved) ? $resolved : [];
}
