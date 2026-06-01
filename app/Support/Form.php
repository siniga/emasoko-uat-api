<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class Form
{
    public static function open(array $options = []): HtmlString
    {
        $method = strtoupper($options['method'] ?? 'POST');
        $formMethod = in_array($method, ['GET', 'POST'], true) ? $method : 'POST';

        $attributes = [
            'method' => $formMethod,
            'action' => self::action($options),
        ];

        if (($options['files'] ?? false) || ($options['enctype'] ?? null) === 'multipart/form-data') {
            $attributes['enctype'] = 'multipart/form-data';
        }

        $attributes = array_merge($attributes, self::cleanOptions($options, [
            'url',
            'route',
            'action',
            'method',
            'files',
        ]));

        $html = '<form'.self::attributes($attributes).'>';

        if ($formMethod !== $method) {
            $html .= self::hidden('_method', $method);
        }

        return new HtmlString($html);
    }

    public static function close(): HtmlString
    {
        return new HtmlString('</form>');
    }

    public static function token(): HtmlString
    {
        return self::hidden('_token', csrf_token());
    }

    public static function text(string $name, mixed $value = null, array $options = []): HtmlString
    {
        return self::input('text', $name, $value, $options);
    }

    public static function email(string $name, mixed $value = null, array $options = []): HtmlString
    {
        return self::input('email', $name, $value, $options);
    }

    public static function number(string $name, mixed $value = null, array $options = []): HtmlString
    {
        return self::input('number', $name, $value, $options);
    }

    public static function date(string $name, mixed $value = null, array $options = []): HtmlString
    {
        return self::input('date', $name, $value, $options);
    }

    public static function password(string $name, array $options = []): HtmlString
    {
        return self::input('password', $name, null, $options);
    }

    public static function file(string $name, array $options = []): HtmlString
    {
        return self::input('file', $name, null, $options);
    }

    public static function hidden(string $name, mixed $value = null, array $options = []): HtmlString
    {
        return self::input('hidden', $name, $value, $options);
    }

    public static function textarea(string $name, mixed $value = null, array $options = []): HtmlString
    {
        $attributes = array_merge(['name' => $name], $options);

        return new HtmlString('<textarea'.self::attributes($attributes).'>'.e((string) $value).'</textarea>');
    }

    public static function select(string $name, array $list = [], mixed $selected = null, array $options = []): HtmlString
    {
        $html = '<select'.self::attributes(array_merge(['name' => $name], $options)).'>';

        foreach ($list as $value => $display) {
            if (is_array($display)) {
                $html .= '<optgroup label="'.e((string) $value).'">';

                foreach ($display as $groupValue => $groupDisplay) {
                    $html .= self::option($groupValue, $groupDisplay, $selected);
                }

                $html .= '</optgroup>';
                continue;
            }

            $html .= self::option($value, $display, $selected);
        }

        return new HtmlString($html.'</select>');
    }

    public static function checkbox(string $name, mixed $value = 1, mixed $checked = null, array $options = []): HtmlString
    {
        if (self::isChecked($value, $checked)) {
            $options['checked'] = true;
        }

        return self::input('checkbox', $name, $value, $options);
    }

    public static function radio(string $name, mixed $value = null, mixed $checked = null, array $options = []): HtmlString
    {
        if (self::isChecked($value, $checked)) {
            $options['checked'] = true;
        }

        return self::input('radio', $name, $value, $options);
    }

    public static function input(string $type, string $name, mixed $value = null, array $options = []): HtmlString
    {
        $attributes = array_merge([
            'type' => $type,
            'name' => $name,
        ], $options);

        if ($value !== null && $type !== 'file') {
            $attributes['value'] = $value;
        }

        return new HtmlString('<input'.self::attributes($attributes).'>');
    }

    private static function action(array $options): string
    {
        if (array_key_exists('url', $options)) {
            return (string) $options['url'];
        }

        if (array_key_exists('route', $options)) {
            return is_array($options['route'])
                ? route($options['route'][0], array_slice($options['route'], 1))
                : route($options['route']);
        }

        if (array_key_exists('action', $options)) {
            return is_array($options['action'])
                ? action($options['action'][0], array_slice($options['action'], 1))
                : action($options['action']);
        }

        return url()->current();
    }

    private static function option(mixed $value, mixed $display, mixed $selected): string
    {
        $attributes = ['value' => $value];

        if (self::isSelected($value, $selected)) {
            $attributes['selected'] = true;
        }

        return '<option'.self::attributes($attributes).'>'.e((string) $display).'</option>';
    }

    private static function isSelected(mixed $value, mixed $selected): bool
    {
        if (is_array($selected)) {
            return in_array((string) $value, array_map('strval', $selected), true);
        }

        return $selected !== null && (string) $value === (string) $selected;
    }

    private static function isChecked(mixed $value, mixed $checked): bool
    {
        if (is_bool($checked)) {
            return $checked;
        }

        if ($checked === null || $checked === '') {
            return false;
        }

        return $checked === 'checked' || (string) $value === (string) $checked;
    }

    private static function cleanOptions(array $options, array $skip): array
    {
        foreach ($skip as $key) {
            unset($options[$key]);
        }

        return $options;
    }

    private static function attributes(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $key => $value) {
            if (is_int($key)) {
                $html .= ' '.$value;
                continue;
            }

            if ($value === false || $value === null) {
                continue;
            }

            if ($value === true) {
                $html .= ' '.e((string) $key);
                continue;
            }

            $html .= ' '.e((string) $key).'="'.e((string) $value).'"';
        }

        return $html;
    }
}
