<?php

namespace ProcessWire;

/**
 * 
 */
function component(string $component, array $params = [], array $attrs = []): string {
    return wire('component')->render($component, $params, $attrs);
}

function componentAttrs(array $attrs = []): string {
    $output = '';
    
    foreach ($attrs as $key => $value) {
        if (is_bool($value)) {
            $value = $value ? " true" : " false";
        } else if (is_array($value)) {
            $value = implode(' ', $value);
        }
        $output .= " $key=\"" . htmlspecialchars($value) . "\"";
    }
    return $output ? " {$output}" : '';
}

function getComponentTemplate(string $dir, string $template): string {
    $explode = explode(DIRECTORY_SEPARATOR, dirname($dir));
    $component = end($explode);
    $templatesPath = wire('config')->paths->templates . 'components/templates';
    $tpl = '';
    
    foreach ([
        "{$templatesPath}/{$component}-{$template}.php",
        "{$templatesPath}/{$component}/template-{$template}.php",
        "{$dir}/template-default.php",
    ] as $filename) {
        if ($tpl) {
            continue;
        }
        if (file_exists($filename)) {
            $tpl = $filename;
        }
    }
    
    return $tpl;
}
