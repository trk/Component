<?php

namespace ProcessWire;

use Totoglu\Component\Element;

/**
 * Component Module for ProcessWire
 *
 * @author			: İskender TOTOĞLU, @ukyo (community), @trk (Github)
 * @website			: https://www.totoglu.com
 */
class Component extends WireData implements Module, ConfigurableModule
{
    protected array $bootstrap = [];

    protected array $components = [];

    protected array $extras = [
        'layouts' => [],
        'templates' => [],
        'fields' => []
    ];

    protected array $loaded = [];

    protected array $defaults = [
        'params' => [],
        'parent' => null,
        'children' => [],
        'cache' => null,
        'metadata' => null,
        'defaults' => null,
        'transform' => null,
        'attrs' => null,
        'fn' => null,
        'render' => null,
        'output' => null
    ];

    /**
     * Return module info
     *
     * @return array
     */
    public static function getModuleInfo()
    {
        return [
            'title' => 'Component',
            'description' => 'Module help you to create and use set of components to utilise in your ProcessWire page templates.',
            'version' => 3,
            'summary' => '',
            'href' => 'https://www.totoglu.com',
            'author' => 'İskender TOTOĞLU | @ukyo(community), @trk (Github), https://www.totoglu.com',
            'requires' => [
                'PHP>=8.1',
                'ProcessWire>=3.0'
            ],
            'installs' => [],
            'permissions' => [],
            'icon' => 'cogs',
            'autoload' => 1000,
            'singular' => true
        ];
    }

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        // Load composer libraries
        require __DIR__ . '/vendor/autoload.php';
    }

    public function wired()
    {
        $this->wire('component', $this);
    }

    /**
     * Initialize module
     *
     * @return void
     */
    public function init()
    {
        $this->setComponents();

        $this->wire()->addHookMethod('TemplateFile::el', function(HookEvent $event) {
            $tag = $event->arguments(0) ?: 'div';
            $attrs = $event->arguments(1) ?: [];
            $contents = $event->arguments(2) ?: false;
            $event->return = new Element($tag, $attrs, $contents);
        });

        $this->wire()->addHookMethod('TemplateFile::attrs', function(HookEvent $event) {
            $attrs = $event->arguments(0);
            if (!is_array($attrs)) {
                $attrs = [];
            }
            $event->return = Element::attrs($attrs);
        });

        foreach (['loadComponentTemplate', 'loadComponent', 'renderChildren', 'renderChild'] as $fn) {
            $this->wire()->addHookMethod("TemplateFile::{$fn}", function(HookEvent $event) {
                $event->return = call_user_func_array([$this, $event->method], $event->arguments);
            });
        }
    }

    /**
     * @inheritDoc
     *
     * @return void
     */
    public function ready()
    {
        $bootstrap = $this->getPath('components') . '/bootstrap.php';
        
        if (file_exists($bootstrap)) {
            $bootstrap = include $bootstrap;
            if (is_array($bootstrap)) {
                $this->bootstrap = $bootstrap;
            }
        }

        $this->applyTemplateFileMethods();
    }

    protected function isClosure(mixed $value): bool
    {
        return $value instanceof \Closure;
    }

    /**
     * Get bootstrap
     * 
     * @return array
     */
    public function getBootstrap(): array
    {
        return $this->bootstrap;
    }

    private function getPaths(): array
    {
        /** @var Config $config */
        $config = $this->wire()->config;

        $paths = [
            'cache' => $config->paths->cache . $this->className(),
            'components' => "{$config->paths->templates}components",
            'layouts' => "{$config->paths->templates}components/_layouts",
            'templates' => "{$config->paths->templates}components/_templates",
            'fields' => "{$config->paths->templates}components/_fields",
        ];

        return $paths;
    }

    private function getPath(string $name = 'components'): ?string
    {
        $paths = $this->getPaths();
        $path = $paths[$name] ?? null;
        
        if ($path && is_dir($path)) {
            return $path;
        }

        if(is_writable(dirname($path)) && $this->wire()->files->mkdir($path)) {
			$this->message("Created directory: $path");
            return $path;
		}

        return null;
    }

    /**
     * Find files matching patterns across multiple paths
     * 
     * @param string ...$patterns One or more glob patterns
     * @return array Array of matching file paths
     */
    private function globFiles(string ...$patterns): array 
    {
        $results = [];
        foreach ($patterns as $pattern) {
            // Try with GLOB_BRACE first
            $result = glob($pattern, GLOB_NOSORT | GLOB_BRACE);
            // If no results with GLOB_BRACE, try without it
            if (!$result) {
                $result = glob($pattern, GLOB_NOSORT) ?: [];
            }
            
            $results = array_merge($results, $result);
        }
        
        return array_unique($results);
    }

    /**
     * Set components
     * 
     * @return void
     */
    protected function setComponents(): void
    {
        /** @var Config $config */
        $config = $this->wire()->config;

        $args = [
            "{$config->paths->siteModules}**/components/*/templates/template.{php,json}"
        ];

        if ($path = $this->getPath('components')) {
            $args[] = "{$path}/*/templates/template.{php,json}";
        }
        
        foreach ($this->globFiles(...$args) as $filename) {
            $dir = dirname(dirname($filename));
            $name = basename($dir);
            
            $component = array_merge([
                'name' => $name,
                'dir' => $dir,
                'templateFile' => $filename,
                'componentFile' => null,
                'title' => $name,
            ], $this->defaults);

            if (file_exists("{$dir}/component.php")) {
                $component['componentFile'] = "{$dir}/component.php";
            }

            $this->components[$name] = $component;
        }

        if (count($this->components)) {
            $this->setComponentExtras('layouts');
            $this->setComponentExtras('templates');
            $this->setComponentExtras('fields');
        }
    }

    /**
     * Get all registered components
     * 
     * @return array Array of registered components
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * Set component extra
     * 
     * @param string $group The name of the extra to set. 'layouts', 'templates', 'fields'
     * @param string $componentName The name of the component to set
     * @param string $name The name of the extra to set
     * @param string|array $data The data to set
     * @return void
     */
    protected function setComponentExtra(string $group, string $name, string|array $data): void
    {
        $this->extras[$group][$name] = $data;
    }

    /**
     * Set component extras
     * 
     * @param string $group The name of the extra to set. 'layouts', 'templates', 'fields'
     * @return void
     */
    protected function setComponentExtras(string $group): void
    {
        if ($path = $this->getPath($group)) {
            if ($group === 'templates') {
                $args = [
                    "{$path}/*/*.php",
                    "{$path}/*.php"
                ];
            } else {
                $args = [
                    "{$path}/*/*.{php,json}",
                    "{$path}/*.{php,json}"
                ];
            }

            foreach ($this->globFiles(...$args) as $filename) {
                $pathinfo = pathinfo($filename);
                $component = basename($pathinfo['dirname']);
                if (str_starts_with($component, "_")) {
                    $component = substr($component, 1);
                }
                
                if ($component === $group) {
                    $component = $group;
                }
                // else if (!isset($this->components[$component])) {
                //     continue;
                // }
                
                $name = "{$component}.{$pathinfo['filename']}";
                $this->setComponentExtra($group, $name, $filename);
            }
        }
    }

    /**
     * Get all registered components extras by extra name
     * 
     * @param string $group The name of the extra to get. 'layouts', 'templates', 'fields'
     * @return array Array of registered components extras
     */
    public function getExtras(string $group): array
    {
        $extras = $this->extras[$group] ?? [];
        ksort($extras);
        return $extras;
    }

    /**
     * Get component extras by extra name
     * 
     * @param string $group The name of the extra to get. 'layouts', 'templates', 'fields'
     * @param string $component The name of the component to get
     * @return array Array of registered components extras
     */
    public function getComponentExtras(string $group, string $component): array
    {
        $extras = [];
        foreach ($this->extras[$group] as $key => $value) {
            if (!str_starts_with($key, "{$component}.")) {
                continue;
            }
            $extras[$key] = $value;
        }
        ksort($extras);
        return $extras;
    }

    /**
     * Get component extra by extra name, component name and name
     * 
     * @param string $group The name of the extra to get. 'layouts', 'templates', 'fields'
     * @param string $name The name of the extra to get
     * @return array|string|null The extra or null if not found
     */
    public function getComponentExtra(string $group, string $name): array|string|null
    {
        return $this->extras[$group][$name] ?? null;
    }

    /**
     * Load component extra by extra name, component name and name
     * 
     * @param string $group The name of the extra to load. 'layouts', 'templates', 'fields'
     * @param string $name The name of the extra to load
     * @return array|string|null The loaded extra or null if not found
     */
    public function loadExtra(string $group, string $name): array|string|null
    {
        $extra = $this->getComponentExtra($group, $name);
        if ($extra) {
            if ($group === 'templates') {
                return $extra;
            } else {
                if (is_array($extra)) {
                    return $extra['data'] ?? null;
                } else {
                    $ext = pathinfo($extra, PATHINFO_EXTENSION);
                    if ($ext === 'json') {
                        $data = json_decode(file_get_contents($extra), true);
                    } else {
                        $data = include $extra;
                    }
                    if (is_array($data)) {
                        $this->setComponentExtra($group, $name, [
                            'filename' => $extra,
                            'data' => $data
                        ]);
                        return $data;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Get all registered layouts
     * 
     * @return array Array of registered layouts
     */
    public function getLayouts(): array
    {
        return $this->getExtras('layouts');
    }

    /**
     * Get component layouts
     * 
     * @param string $component The name of the component to get
     * @return array Array of registered component layouts
     */
    public function getComponentLayouts(string $component): array
    {
        return $this->getComponentExtras('layouts', $component);
    }

    /**
     * Get component layout by name
     * 
     * @param string $name The name of the layout to get
     * @return array|string|null The layout or null if not found
     */
    public function getComponentLayout(string $name): array|string|null
    {
        return $this->getComponentExtra('layouts', $name);
    }

    /**
     * Load component layout by name
     * 
     * @param string $name The name of the layout to get
     * @return array|string|null The loaded layout or null if not found
     */
    public function loadComponentLayout(string $name): array|string|null
    {
        return $this->loadExtra('layouts', $name);
    }

    /**
     * Get all registered templates
     * 
     * @return array Array of registered templates
     */
    public function getTemplates(): array
    {
        return $this->getExtras('templates');
    }

    /**
     * Get component templates
     * 
     * @param string $component The name of the component to get
     * @return array Array of registered component templates
     */
    public function getComponentTemplates(string $component): array
    {
        return $this->getComponentExtras('templates', $component);
    }

    /**
     * Get component template by name
     * 
     * @param string $name The name of the template to get
     * @return array|string|null The template or null if not found
     */
    public function getComponentTemplate(string $name): array|string|null
    {
        return $this->getComponentExtra('templates', $name);
    }

    /**
     * Load component template by name
     * 
     * @param string $component The name of the component to get
     * @param string $name The name of the template to get
     * @return ?string The loaded template or null if not found
     */
    public function loadComponentTemplate(string $component, ?string $name = null): ?string
    {
        if (is_null($name)) {
            return null;
        }

        // first try to load from component
        $tpl = $this->loadExtra('templates', "{$component}.{$name}");
        if (!$tpl && $component != 'templates') {
            // then try to load from templates
            $tpl = $this->loadExtra('templates', "templates.{$name}");
        }

        if (!$tpl) {
            // then try to load from component
            $component = $this->getComponent($component);

            $filename = $component['dir'] . '/templates/template-' . $name . '.php';
            if (file_exists($filename)) {
                $tpl = $filename;
            }
        }

        return is_string($tpl) ? $tpl : null;
    }

    /**
     * Get all registered fields
     * 
     * @return array Array of registered fields
     */
    public function getFields(): array
    {
        return $this->getExtras('fields');
    }

    /**
     * Get component fields
     * 
     * @param string $component The name of the component to get
     * @return array Array of registered component fields
     */
    public function getComponentFields(string $component): array
    {
        return $this->getComponentExtras('fields', $component);
    }

    /**
     * Get component field by name
     * 
     * @param string $name The name of the field to get
     * @return array|string|null The field or null if not found
     */
    public function getComponentFieldsData(string $name): array|string|null
    {
        return $this->getComponentExtra('fields', $name);
    }

    /**
     * Load component layout by name
     * 
     * @param string $name The name of the field to get
     * @return array|string|null The loaded field or null if not found
     */
    public function loadComponentFields(string|array $nameOrFields, string $prefix = '', array $exclude = [], array $include = [], array $overwrite = []): array|string|null
    {
        $fields = is_array($nameOrFields) ? $nameOrFields : $this->loadExtra('fields', $nameOrFields);
        
        if (is_array($fields) && (count($exclude) || count($include) || count($overwrite) || $prefix)) {
            
            if ($fields['children']) {

                foreach ($fields['children'] as $key => $field) {

                    if ($overwrite && isset($overwrite[$key])) {

                        if (isset($field['options']) && isset($overwrite[$key]['options'])) {
                            $overwrite[$key]['options'] = array_merge($field['options'], $overwrite[$key]['options']);
                        }

                        $fields['children'][$key] = array_merge($fields['children'][$key], $overwrite[$key]);
                    }
                    
                    if ($exclude && in_array($key, $exclude) && isset($fields['children'][$key])) {
                        unset($fields['children'][$key]);
                    }

                    if ($include && !in_array($key, $include) && isset($fields['children'][$key])) {
                        unset($fields['children'][$key]);
                    }

                    if ($prefix && isset($fields['children'][$key])) {
                        $fields['children'][$prefix . $key] = $fields['children'][$key];
                        unset($fields['children'][$key]);
                    }
                    
                }

            } else {
                
                foreach ($fields as $key => $field) {

                    if ($overwrite && isset($overwrite[$key])) {

                        if (isset($field['options']) && isset($overwrite[$key]['options'])) {
                            $overwrite[$key]['options'] = array_merge($field['options'], $overwrite[$key]['options']);
                        }

                        $fields[$key] = array_merge($fields[$key], $overwrite[$key]);
                    }

                    if ($exclude && in_array($key, $exclude) && isset($fields[$key])) {
                        unset($fields[$key]);
                    }

                    if ($include && !in_array($key, $include) && isset($fields[$key])) {
                        unset($fields[$key]);
                    }

                    if ($prefix && isset($fields[$key])) {
                        $fields[$prefix . $key] = $fields[$key];
                        unset($fields[$key]);
                    }

                }

            }

        }

        return $fields;
    }
    
    /**
     * Load component fields array, cache the result
     * 
     * Example usage:
     * 
     * loadComponentFieldsArray('fields.text');
     * or
     * loadComponentFieldsArray('text.text');
     * 
     * from component fields file:
     * $fields = [
     *   '_text' => [
     *     'type' => 'fieldset',
     *     'children' => [
     *       'text_style' => [
     *         'type' => 'select',
     *         'options' => ['meta', 'lead', 'small']
     *       ],
     *       'text_color' => [
     *         'type' => 'select', 
     *         'options' => ['muted', 'primary', 'success']
     *       ]
     *     ]
     *   ],
     *   '_general' => [
     *     'component' => 'fields.general',
     *     'exclude' => ['item_animation']
     *   ],
     *   '_advanced' => 'fields.advanced',
     * ];
     * 
     * @param string $component The name of the component to load
     * @param array|null $fields The fields to load
     * @return array The loaded fields
     */
    public function loadComponentFieldsArray(string $component, ?array $fields = null): array
    {
        $files = [
            __FILE__
        ];

        if (!$fields && isset($this->extras['fields'][$component])) {
            $data = $this->extras['fields'][$component];
            if (is_array($data)) {
                $files[] = $data['filename'];
                $fields = $data['data'];
            } else {
                $files[] = $data;
                $fields = $this->loadComponentFields($component);
            }
        }
        
        if (isset($fields['children'])) {
            foreach ($fields['children'] as $key => $field) {

                $extra = null;
                if (is_array($field) && isset($field['component']) && isset($this->extras['fields'][$field['component']])) {
                    $extra = $this->extras['fields'][$field['component']];
                } else if (is_string($field) && isset($this->extras['fields'][$field])) {
                    $extra = $this->extras['fields'][$field];
                }

                if (!$extra) {
                    continue;
                }

                if (is_array($extra)) {
                    $extraName = $field['component'];
                    $files["{$key},{$extraName}"] = $extra['filename'];
                } else {
                    $extraName = $field;
                    $files["{$key},{$extraName}"] = $extra;
                }
            }

        } else {
            foreach ($fields as $key => $field) {

                $extra = null;
                if (is_array($field) && isset($field['component']) && isset($this->extras['fields'][$field['component']])) {
                    $extraName = $field['component'];
                    $extra = $this->extras['fields'][$field['component']];
                } else if (is_string($field) && isset($this->extras['fields'][$field])) {
                    $extraName = $field;
                    $extra = $this->extras['fields'][$field];
                }

                if (!$extra) {
                    continue;
                }

                if (is_array($extra)) {
                    $files["{$key},{$extraName}"] = $extra['filename'];
                } else {
                    $files["{$key},{$extraName}"] = $extra;
                }

            }
        }

        if (count($files)) {

            // Add the file that called this function
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            foreach ($backtrace as $trace) {
                if (isset($trace['file']) && !in_array($trace['file'], $files)) {
                    $files[] = $trace['file'];
                }
            }

            $lastModified = 0;
            foreach (array_values($files) as $file) {
                $mtime = filemtime($file);
                if ($mtime > $lastModified) {
                    $lastModified = $mtime;
                }
            }
            
            $filename = $component;
            if (isset($this->wire()->user->language) && !$this->wire()->user->language->isDefault()) {
                $filename .= '-' . $this->wire()->user->language->id;
            }
            $filename = md5($filename);
            $cacheFile = $this->getPath('cache') . "/{$filename}.php";

            if (file_exists($cacheFile) && $lastModified < filemtime($cacheFile)) {
                $fields = include $cacheFile;
            } else {
                
                if (isset($fields['children'])) {

                    foreach ($files as $key => $file) {
                        
                        if (!is_string($key)) {
                            continue;
                        }
                        list($fieldName, $extraName) = explode(',', $key);
                        
                        $value = $fields['children'][$fieldName];
                        
                        $prefix = '';
                        $exclude = [];
                        $include = [];
                        $overwrite = [];

                        if (is_array($value)) {
                            $extraName =
                            $prefix = $value['prefix'] ?? '';
                            $exclude = $value['exclude'] ?? [];
                            $include = $value['include'] ?? [];
                            $overwrite = $value['overwrite'] ?? [];
                        }
                        $fields['children'][$fieldName] = $this->loadComponentFields($extraName, $prefix, $exclude, $include, $overwrite);
                    }

                } else {

                    foreach ($files as $key => $file) {

                        if (!is_string($key)) {
                            continue;
                        }

                        list($fieldName, $extraName) = explode(',', $key);
                        
                        $value = $fields[$fieldName];
                        
                        $prefix = '';
                        $exclude = [];
                        $include = [];
                        $overwrite = [];

                        if (is_array($value)) {
                            $extraName = $value['component'] ?? $extraName;
                            $prefix = $value['prefix'] ?? '';
                            $exclude = $value['exclude'] ?? [];
                            $include = $value['include'] ?? [];
                            $overwrite = $value['overwrite'] ?? [];
                        }
                        
                        $fields[$fieldName] = $this->loadComponentFields($extraName, $prefix, $exclude, $include, $overwrite);
                    }

                }

                // Write cache file with all used functions and variables
                $content = "<?php\n\n";
                $content .= "namespace ProcessWire;\n\n";
                
                // Export fields array
                $content .= "return " . var_export($fields, true) . ";";
                
                // Write to cache file
                $this->wire()->files->filePutContents($cacheFile, $content);
            }

        }

        return $fields;
    }

    /**
     * Get component by name
     * 
     * @param string $name The name of the component to get
     * @return array The component or null if not found
     */
    public function getComponent(string $name): array
    {
        if (!isset($this->components[$name])) {
            return [];
        }

        if (isset($this->loaded[$name])) {
            return $this->loaded[$name];
        }

        $component = $this->components[$name];

        if ($component['componentFile']) {
            $ext = pathinfo($component['componentFile'], PATHINFO_EXTENSION);
            if ($ext === 'json') {
                $componentData = json_decode(file_get_contents($component['componentFile']), true);
            } else {
                $componentData = include $component['componentFile'];
            }
            if (is_array($componentData)) {
                $component = array_merge($component, $componentData);
            }
        }

        $this->loaded[$name] = $component;

        return $component;
    }

    protected function apply(array $component, array $keys, array $params = []): void
    {
        foreach ($keys as $key) {
            if (!isset($component[$key])) {
                continue;
            }

            $method = '___apply' .strtoupper($key);

            if (method_exists($this, $method)) {
                if (!empty($params)) {
                    $component = $this->$method(component: $component, params: $params);
                } else {
                    $component = $this->$method(component: $component);
                }
            }
        }
    }

    public function ___getAttrs(array $attrs, array $component): array
    {
        if (!isset($attrs['id'])) {
            $attrs['id'] = strtolower($component['name'] . '-' . uniqid());
        }

        if (!isset($attrs['class'])) {
            $attrs['class'] = [];
        } else if (isset($attrs['class']) && is_string($attrs['class'])) {
            $attrs['class'] = [$attrs['class']];
        }

        if (is_callable($component['attrs'])) {
            $attrs = $component['attrs']($attrs, $component);
        } else if (is_array($component['attrs'])) {
            $attrs = array_merge($attrs, $component['attrs']);
        }

        return $attrs;
    }


    public function ___applyTemplateFileMethods(): void
    {
        // apply template file method hooks
    }

    /**
     * Applies default parameters to a component.
     *
     * This function merges the default parameters defined in the component with the provided parameters.
     * If the defaults are defined as a callable, it executes the callable to get the default parameters.
     *
     * @param array $component The component array containing default parameters.
     * @param array $params The parameters to merge with the default parameters.
     * @return array The merged parameters.
     */
    public function ___applyDefaults(array $component, array $params): array
    {
        if ($this->isClosure($component['defaults'])) {
            $defaults = $component['defaults']($component, $params);
            if (is_array($defaults)) {
                $component['params'] = array_merge($component['params'], $defaults);
            }
        } else if (is_array($component['defaults'])) {
            $component['params'] = array_merge($component['params'], $component['defaults']);
        }

        return $component['params'];
    }

    public function ___applyLayout(array $component, array $params): array
    {
        $layout = isset($params['layout']) ? $this->loadComponentLayout($params['layout']) : null;
        
        if (is_array($layout) && isset($layout['params']) && is_array($layout['params'])) {
            $component['params'] = array_merge($component['params'], $layout['params']);
        }

        return $component['params'];
    }

    /**
     * Applies a transformation to the component
     *
     * @param array $parameters The component parameters array containing the transformation callable.
     * @return array The transformed component parameters
     */
    public function ___applyParametersTransform(array $params): array
    {
        return $params;
    }

    /**
     * Applies a transformation to the component parameters.
     *
     * This function checks if a transformation callable is defined in the component
     * and applies it to the component parameters if it exists.
     *
     * @param array $component The component array containing the transformation callable.
     * @return array The transformed parameters.
     */
    public function ___applyTransform(array $component): array
    {
        if (is_callable($component['transform'])) {
            $component['params'] = $component['transform']($component['params'], $component);
        }
        return $component['params'];
    }

    public function ___applyMetadata(array $component): void
    {
        if ($this->isClosure($component['metadata'])) {
            $component['metadata'] = $component['metadata']($component);
        } else if (is_array($component['metadata'])) {
            foreach ($component['metadata'] as $key => $value) {
                $this->wire()->metadata->set($key, $value);
            }
        }
    }

    public function ___applyFunctions(array $component): void
    {   
        if (is_array($component['fn'])) {
            foreach ($component['fn'] as $name => $fn) {
                $this->wire()->addHookMethod("TemplateFile::{$name}", $fn);
            }
        }
    }

    public function ___isCacheable(array $component): bool
    {
        return !$this->wire()->config->debug || !$this->wire()->user->isSuperuser();
    }

    public function ___renderChildren(array $children, ?array $parent = null): string
    {
        $output = '';

        foreach ($children as $child) {
            $output .= $this->renderChild($child, $parent);
        }

        return $output;
    }

    public function ___renderChild(string|array $component, ?array $parent = null): string
    {
        if (is_array($component)) {
            $component['parent'] = $parent;
            return $this->render($component);
        }

        return $component;
    }

    public function ___loadComponent(string $name, array $params = [], array $attrs = []): ?array
    {
        $component = $this->getComponent($name);

        if (!$component) {
            return null;
        }

        $component['params'] = $this->applyDefaults($component, $params);
        $component['params'] = array_merge($component['params'], $params);
        $component['params'] = $this->applyLayout($component, $params);
        
        if (!$component['children'] && isset($component['params']['children'])) {
            $component['children'] = $component['params']['children'];
        }

        $component['fn'] = $this->applyFunctions($component);
        $component['attrs'] = $this->getAttrs($attrs, $component);

        if (isset($component['load']) && is_callable($component['load'])) {
            $component = $component['load']($component);
        }

        return $component;
    }

    public function ___renderReady(array $component): array
    {
        return $component;
    }

    public function ___render(string|array $name, array $params = [], array $attrs = [], string|null $cacheName = null, int|Page|string|null $cacheExpire = null): string
    {
        if (is_array($name)) {
            $component = $name;
            $component['params'] = array_merge($component['params'], $params);
        } else {
            $component = $this->loadComponent($name, $params, $attrs);
        }

        if (!$component) {
            return '';
        }

        $component['params'] = $this->applyTransform($component);
        
        $render = true;

        if ($component['render']) {
            if (is_callable($component['render'])) {
                $render = $component['render']($component['params'], $component);
            } else {
                $render = $component['render'];
            }
        }
        
        if (!$render) {
            return '';
        }

        $this->apply($component, ['metadata']);
        
        $component = $this->renderReady($component);

        if (!isset($component['params']['template'])) {
            $component['params']['template'] = null;
        }

        $parameters = [
            '__name' => $component['name'],
            '__element' => $component['element'] ?? false,
            '__container' => $component['container'] ?? false,
            '__title' => $component['title'],
            '__dir' => "{$component['dir']}/templates",
            '__template' => $component['templateFile'],
            '__component' => $component['componentFile'],
            'parent' => $component['parent'],
            'params' => $component['params'],
            'attrs' => $component['attrs'],
            'children' => $component['children']
        ];
        
        $parameters['component'] = $parameters;
        
        $parameters = $this->applyParametersTransform($parameters);

        $cached = false;

        $output = '';
        
        if ($this->isCacheable($component)) {
            
            $cache = [
                'name' => $cacheName,
                'expire' => $cacheExpire
            ];
    
            if ($component['cache'] && !$cache['name'] && !$cache['expire']) {

                if (is_callable($component['cache'])) {
                    $component['cache'] = $component['cache']($component);
                }
    
                if (is_array($component['cache'])) {
                    if (isset($component['cache']['name'])) {
                        $cache['name'] = $component['cache']['name'];
                    }
                    if (isset($component['cache']['expire'])) {
                        $cache['expire'] = $component['cache']['expire'];
                    }
                }
                
            }
    
            // if we use directly this method, result directly stored in database, check for cache name and expire before store output
            if ($cache['name'] && $cache['expire']) {
                $cached = true;
                $output = $this->wire()->cache->renderFile($component['templateFile'], $cache['expire'], [
                    'name' => $this->className() . "-{$component['name']}-{$cache['name']}",
                    'vars' => $parameters
                ]);
            }

        }

        if (!$output && !$cached) {
            $output = $this->wire()->files->render($component['templateFile'], $parameters);
        }

        if ($output) {
            if (is_callable($component['output'])) {
                return $component['output']($output);
            }
        }

        return $output;
    }

    /**
	 * Module configurations
	 * 
	 * @param InputfieldWrapper $inputfields
	 *
	 */
	public function getModuleConfigInputfields(InputfieldWrapper $inputfields)
    {
        /** @var Modules $modules */
        $modules = $this->wire()->modules;

        $cachePrefix = "cache.{$this->className()}-*";
        $cached = $this->wire()->cache->get($cachePrefix);

        // clear cache action
        if ($this->input->post->get("clear_cache") ? true : false) {
            $this->wire()->cache->deleteFor($cachePrefix);
            $this->message(sprintf($this->_('%d components cache data cleared successfully'), count($cached)));
            $cached = $this->wire()->cache->get($cachePrefix);
        }

        /** @var InputfieldFieldset @fieldset */
        $fieldset = $modules->get('InputfieldFieldset');
        $fieldset->label = $this->_('Clear components cache data');
        $fieldset->icon = 'icon-refresh';
        $fieldset->collapsed = Inputfield::collapsedYes;

        /**
         * @var InputfieldCheckbox $checkbox
         */
        $checkbox = $modules->get('InputfieldCheckbox');
        $checkbox->attr('name', "clear_cache");
        $checkbox->attr('value', 1);
        $checkbox->label = $this->_('Clear component cache data ?');
        $checkbox->description = sprintf($this->_('There are currently %d components cached'), count($cached));
        $checkbox->checkboxLabel = $this->_('Clear cache data');

        $fieldset->add($checkbox);

        $inputfields->add($fieldset);
        
        return $inputfields;
    }
}