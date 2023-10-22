<?php

namespace ProcessWire;

/**
 * @var Files $files
 * @var array $params
 * @var array $attrs
 */

// $attrs['class'] += [
//     "uk-heading-{$params['size']}" => $params['size'],
//     "uk-heading-divider" => $params['divider'],
//     "uk-heading-bullet" => $params['bullet'],
//     "uk-heading-line" => $params['line'],
//     "uk-text-{$params['decoration']}" => $params['decoration'],
//     "uk-text-{$params['transform']}" => $params['transform'],
//     "uk-text-{$params['color']}" => $params['color'],
//     "uk-text-{$params['align']}" => $params['align'],
// ];

// echo $this->el($params['tag'], $attrs, $params['content']);

if ($params['size']) {
    $attrs['class'][] = "uk-heading-{$params['size']}";
}

if ($params['divider']) {
    $attrs['class'][] = "uk-heading-divider";
}

if ($params['bullet']) {
    $attrs['class'][] = "uk-heading-bullet";
}

if ($params['line']) {
    $attrs['class'][] = "uk-heading-line";
}

if ($params['decoration']) {
    $attrs['class'][] = "uk-text-{$params['decoration']}";
}

if ($params['transform']) {
    $attrs['class'][] = "uk-text-{$params['transform']}";
}

if ($params['color']) {
    $attrs['class'][] = "uk-text-{$params['color']}";
}

if ($params['align']) {
    $attrs['class'][] = "uk-text-{$params['align']}";
}

echo "<{$params['tag']}{$this->attrs($attrs)}>{$params['content']}</{$params['tag']}>";