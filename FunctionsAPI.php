<?php

namespace ProcessWire;

function component(string|array $component, array $params = [], array $attrs = [], string|null $cacheName = null, int|Page|string|null $cacheExpire = null): string {
    return wire('component')->render($component, $params, $attrs, $cacheName, $cacheExpire);
}

function renderComponentChildren(array $children, ?array $parent = null): string {
    return wire('component')->renderChildren($children, $parent);
}

function renderComponentChild(array $child, ?array $parent = null): string {
    return wire('component')->renderChild($child, $parent);
}

function loadComponent(string $name, array $params = [], array $attrs = []): ?array
{
    return wire('component')->loadComponent($name, $params, $attrs);
}
