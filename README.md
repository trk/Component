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
```

## Requirements

* ProcessWire `3.0` or newer
* PHP `8.1` or newer

## Installation

Install the module from the [modules directory](https://modules.processwire.com/modules/component/)

Via `Composer`:

```shell
composer require trk/component
```

Via `git clone`:

```shell
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
    // cache the output
    'cache' => function (array $component): array {
        $params = $component['params'];

        $name = null;
        $expire = null;
        
        if (isset($params['page']) && $params['page'] instanceof Page) {
            $name = $params['page']->id;
            $expire = $params['page'];
        }

        return [
            'name' => $page->id,
            'expire' => $page
        ];
    },
    'transform' => function (array $params): array {
        // do something with params
        return $params;
    },
    // 'attrs' => [
    //     // add more attrs
    // ],
    'attrs' => function (array $attrs): array {
        // do something with attrs
        return $attrs;
    },
    'fn' => [
        // add hello method to TemplateFile and use it like $this->hello(); in template
        'hello' => function (HookEvent $e) {
            $e->return = 'Hello World';
        }
    ],
    // Check component has required parameters
    'render' => function(array $params): bool {
        return strlen($params['content']) ?: false;
    }
];
```

### Create `heading` Template File **required**

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

// $this->hello(); added dynamically as function
// $this->attrs(array $attrs); Accept array of attributes, return is string

echo "<{$params['tag']}{$this->attrs($attrs)}>{$params['content']}</{$params['tag']}>";

```

### Print `heading` Component

```php
echo component('heading', [
    'content' => 'Hello World !',
]);
```