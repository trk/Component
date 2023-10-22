<?php

namespace ProcessWire;

return [
    'params' => [
        'theme' => 'default',
        'content' => '',
        'tag' => 'h1',
        'size' => '',
        'divider' => false,
        'bullet' => false,
        'line' => false,
        'decoration' => '',
        'transform' => '',
        'color' => '',
        'align' => ''
    ],
    // 'attrs' => [
    //     // 
    // ],
    // 'attrs' => function (array $attrs, array $component): array {
    //     // 
    //     return $attrs;
    // },
    // 'cache' => [
    //     'name' => null,
    //     'expire' => null
    // ],
    // 'cache' => function (array $component): array {
    //     return [
    //         'name' => null,
    //         'expire' => null
    //     ];
    // },
    'transform' => function (array $params, array $component): array {
        if ($params['content'] && $params['line']) {
            $params['content'] = "<span>{$params['content']}</span>";
        }
        return $params;
    },
    // 'fn' => [
    //     // 'hello' => function (HookEvent $e) {
    //     //     $e->return = 'Hello World';
    //     // }
    // ],
    'render' => function (array $params): bool { 
        return $params['content'] ? true : false;
    },
    // Do something with output
    // 'output' => function (string $output): string {
    //     return $output;
    // }
];
