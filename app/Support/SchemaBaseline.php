<?php

namespace App\Support;

class SchemaBaseline
{
    public static function manifest(): array
    {
        return json_decode(file_get_contents(database_path('schema/baseline.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function normalize(string $sql, string $database): string
    {
        // Counters and dump-account ownership are not portable schema definitions.
        $sql = preg_replace('/\sAUTO_INCREMENT=\d+/', '', $sql);
        $sql = preg_replace('/DEFINER=`[^`]*`@`[^`]*`\s*/', '', $sql);
        $sql = str_replace('SQL SECURITY DEFINER', 'SQL SECURITY INVOKER', $sql);
        $sql = str_replace('`' . str_replace('`', '``', $database) . '`.', '', $sql);

        return trim(str_replace("\r\n", "\n", $sql));
    }

    public static function fingerprint(string $sql, string $database): string
    {
        return hash('sha256', self::normalize($sql, $database));
    }
}
