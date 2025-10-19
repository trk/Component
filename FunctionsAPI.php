<?php

namespace ProcessWire;

function component(string|array $component, array $params = [], array $attrs = [], string|null $cacheName = null, int|Page|string|null $cacheExpire = null): string
{
    return wire('component')->render($component, $params, $attrs, $cacheName, $cacheExpire);
}

function renderComponentChildren(array $children, ?array $parent = null): string
{
    return wire('component')->renderChildren($children, $parent);
}

function renderComponentChild(array $child, ?array $parent = null): string
{
    return wire('component')->renderChild($child, $parent);
}

function loadComponent(string $name, array $params = [], array $attrs = []): ?array
{
    return wire('component')->loadComponent($name, $params, $attrs);
}

function setComponentOptions(string|array $components, string $field, array $options = [])
{
    if (is_array($components)) {
        foreach ($components as $component) {
            $key = "component.{$component}.options.{$field}";
            $options = array_merge(setting($key) ?: [], $options);
            setting($key, $options);
        }
    } else {
        $key = "component.{$components}.options.{$field}";
        $options = array_merge(setting($key) ?: [], $options);
        setting($key, $options);
    }
}

function getComponentOptions(string $component, string $field, array $options = [])
{
    return array_merge($options, setting("component.{$component}.options.{$field}") ?: []);
}

function setComponentOption(string $component, string $field, array $option)
{
    $options = getComponentOptions($component, $field);
    $options += $option;
    setComponentOptions($component, $field, $options);
}

function getComponentOption(string $component, string $field, array $option = [])
{
    $options = getComponentOptions($component, $field);
    $options += $option;
    return $options;
}

function setComponentDefaults(string|array $components, array $defaults = [])
{
    if (is_array($components)) {
        foreach ($components as $component) {
            $defaults = array_merge(setting("component.{$component}.defaults") ?: [], $defaults);
            setting("component.{$component}.defaults", $defaults);
        }
    } else {
        $defaults = array_merge(setting("component.{$components}.defaults") ?: [], $defaults);
        setting("component.{$components}.defaults", $defaults);
    }
}

function setComponentDefault(string $component, string $key, mixed $value)
{
    $defaults = getComponentDefaults($component);
    $defaults[$key] = $value;
    setComponentDefaults($component, $defaults);
}

function getComponentDefaults(string $component, array $defaults = []): array
{
    // get and merge defaults for given component
    $defaults = array_merge(setting("component.{$component}.defaults") ?: [], $defaults);
    // set merged defaults for given component
    // setting("component.{$component}.defaults", $defaults);

    return $defaults;
}

function getComponentDefault(string $component, string $key, mixed $default = null)
{
    $defaults = getComponentDefaults($component);
    return $defaults[$key] ?? $default;
}
