<?php

namespace ProcessWire;

/**
 * 
 */
function component(string $component, array $params = []): string {
    return wire('component')->render($component, $params);
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
