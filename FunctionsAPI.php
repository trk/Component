<?php

namespace ProcessWire;

/**
 * 
 */
function component(string $component, array $params = [], array $attrs = []): string {
    return wire('component')->render($component, $params, $attrs);
}

function getComponentTemplate(string $dir, string $template = ''): string {
    $explode = explode(DIRECTORY_SEPARATOR, dirname($dir));
    $component = end($explode);
    $templatesPath = wire('config')->paths->templates . 'components/templates';
    $tpl = '';
    
    if ($template) {
        $paths = [
            "{$templatesPath}/{$component}/template-{$template}.php",
            "{$templatesPath}/{$component}-{$template}.php",
            "{$dir}/template-{$template}.php",
        ];
    } else {
        $paths = [
            "{$templatesPath}/{$component}/template-default.php",
            "{$templatesPath}/{$component}-default.php",
        ];
    }

    $paths[] = "{$dir}/template-default.php";
    
    foreach ($paths as $filename) {
        if ($tpl) {
            continue;
        }
        if (file_exists($filename)) {
            $tpl = $filename;
        }
    }
    
    return $tpl;
}
