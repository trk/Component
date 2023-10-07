# Component Module for ProcessWire CMS/CMF



Module will check :

- `site/modules/**/components/*/templates/template.php`
- `site/templates/components/*/templates/template.php`

directories for the components. If you want to use default params create a `component.php` file in `Component` root dir.

### Template Components

```
└── site/templates/components
    └── name-of-component
        ├── component.php (not required)
        └── templates
            └── template.php (required)
```

### Module Components

```
└── site/modules/name-of-module/components
    └── name-of-component
        ├── component.php (not required)
        └── templates
            └── template.php (required)
````

## Requirements

* ProcessWire `3.0` or newer
* PHP `7.0` or newer

## Installation

Install the module from the [modules directory](https://modules.processwire.com/modules/component/):

Via `Composer`:

```
composer require trk/component
```

Via `git clone`:

```
cd your-processwire-project-folder/
cd site/modules/
git clone https://github.com/trk/Component.git
```

### Create `heading` Component Config File **not required**

```php
// site/templates/components/heading/component.php
<?php

namespace ProcessWire;

return [
    // Set default params
    'params' => [
        'content' => '',
        'tag' => 'h1',
        'size' => '',
        'decoration' => '',
        'transform' => '',
        'color' => '',
        'align' => ''
    ],
    // Check component has required parameters
    'render' => function(array $params): bool {
        return strlen($params['content']) ?: false;
    }
];
```

### Create `heading` Template File **required**

```php
// site/templates/components/heading/templates/template.php
<?php

namespace ProcessWire;

/**
 * @var array $params
 * @var array $attrs
 */

if ($params['size']) {
    $attrs['class'][] = "uk-{$params['size']}";
}

if ($params['decoration']) {
    $attrs['class'][] = "uk-{$params['decoration']}";
}

if ($params['transform']) {
    $attrs['class'][] = "uk-text-{$params['transform']}";
}

if ($params['color']) {
    $attrs['class'][] = "uk-text-{$params['transform']}";
}

if ($params['align']) {
    $attrs['class'][] = "uk-text-{$params['align']}";
}

echo "<{$params['tag']}{$component->attrs($attrs)}>{$params['content']}</{$params['tag']}>";

```

### Print `heading` Component

```php
echo component('heading', [
    'content' => 'Hello World !',
]);
```