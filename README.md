# Component Module for ProcessWire CMS/CMF

Module will check :

- `site/modules/**/components/*/templates/template.php`
- `site/templates/components/*/templates/template.php`

directories for the components. If you want to use default params create a `component.php` file in `Component` root dir.

### Template Components

```
└── site/templates/components
    └── name-of-component
        ├── component.php (optional)
        ├── layout-default.php (optional)
        ├── layout-home.php (optional)
        ├── layout-contact.php (optional)
        ├── fields.php (optional)
        └── templates
            └── template.php (required)
```

### Module Components

```
└── site/modules/name-of-module/components
    └── name-of-component
        ├── component.php (optional)
        ├── layout-default.php (optional)
        ├── layout-home.php (optional)
        ├── layout-contact.php (optional)
        ├── fields.php (optional)
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
### All `component.php` file settings

```php
<?php

namespace ProcessWire;

return [
    'title' => 'Title of component',
    'params' => [
        'foo' => '',
    ],
    'params' => [
        
    ],
    'parent' => [],
    // cache the output
    'cache' => function (array $component): array {
        return [
            'name' => 'name-of-cache',
            'expire' => 'expire-time-of-cache'
        ];
    },
    'metadata' => function (array $params, array $component): void {
        // add some metadata for component
    },
    'defaults' => function (array $params, array $component): array {
        $defaults = [
            'foo' => 'bar'
        ];
        return $defaults;
    },
    'transform' => function (array $params, array $component): array {
        // do something with params
        return $params;
    },
    // 'attrs' => [
    //     // add more attrs
    // ],
    'attrs' => function (array $attrs, array $component): array {
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
    'render' => function(array $params, array $component): bool {
        return count($params);
    },
    // Do something with output
    // 'output' => function(string $output, array $component): bool {
    //     return str_replace('<alert />', component('alert', ['content' => 'Warning !']), $output);
    // }
];
```

### Component array output

```php
[
    'name' => 'name-of-component', // string
    'dir' => 'path-of-component', // string
    'templateFile' => 'template-file-of-component', // string
    'componentFile' => null, // optional component configuration file, ?string
    'fieldsFile' => null, // optional fields configuration file, ?string
    'layouts' => [], // optional ready to use layout files, you can overwrite params by passing `layout` array of layout file list
    'title' => 'title-of-component', // string 
    'params' => [], // array
    'parent' => null, // ?array
    'cache' => null, // ?callable
    'metadata' => null, // ?callable
    'defaults' => null, // ?array|callable
    'transform' => null, // ?callable
    'attrs' => null, // ?array|callable
    'fn' => null, // ?array
    'render' => null, // ?callable 
    'output' => null // ?callable
];
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
            'expire' => $expire
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
    },
    // Do something with output
    // 'output' => function(string $output): bool {
    //     return str_replace('<alert />', component('alert', ['content' => 'Warning !']), $output);
    // }
];
```

### Create `heading` Template File **required**

```php
// site/templates/components/heading/templates/template.php
<?php

namespace ProcessWire;

/**
 * @var WireFileTools $files
 * @var string  $__name Name of the component
 * @var string  $__title Title of component
 * @var string  $__dir Directory of component
 * @var string  $__template Template file of component
 * @var string  $__component Component file of component
 * @var string  $__fields Fields file of component
 * @var array   $__layouts Layouts of component
 * @var ?array  $parent Parent component
 * @var array   $params Parameters of component
 * @var array   $attrs Attributes of component
 * @var array   $children Children of component
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