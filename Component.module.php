<?php

namespace ProcessWire;

use Altivebir\Component\Element;

/**
 * Component Module for ProcessWire
 *
 * @author			: İskender TOTOĞLU, @ukyo (community), @trk (Github)
 * @website			: https://www.altivebir.com
 */
class Component extends WireData implements Module, ConfigurableModule
{
 
    protected array $components = [];

    protected array $loaded = [];

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
            'version' => 2,
            'summary' => '',
            'href' => 'https://www.altivebir.com',
            'author' => 'İskender TOTOĞLU | @ukyo(community), @trk (Github), https://www.altivebir.com',
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
    }

    /**
     * @inheritDoc
     *
     * @return void
     */
    public function ready()
    {
        $this->applyTemplateFileMethods();
    }

    protected function setComponents(): void
    {
        /** @var Config $config */
        $config = $this->wire()->config;

        $files = array_merge(
            glob("{$config->paths->siteModules}**/components/*/templates/template.php", 0 | GLOB_NOSORT) ?: [],
            glob("{$config->paths->templates}components/*/templates/template.php", 0 | GLOB_NOSORT) ?: []
        );

        foreach ($files as $filename) {
            $name = explode(DIRECTORY_SEPARATOR, dirname(dirname($filename)));
            $this->components[end($name)] = $filename;
        }
    }

    public function getComponents(): array
    {
        return $this->components;
    }

    public function getComponent(string $component): array
    {
        if (isset($this->loaded[$component])) {
            return $this->loaded[$component];
        }

        $data = [];

        if (isset($this->components[$component])) {
            
            $path = dirname(dirname($this->components[$component]));
            
            if (file_exists("{$path}/component.php")) {
                $data = require "{$path}/component.php";
                if ($data instanceof \Closure) {
                    $data = $data();
                }
                $data['config'] = "{$path}/component.php";
            }

            if ($data) {
                if (!isset($data['name'])) {
                    $data['name'] = $component;
                }
                $data['params'] = isset($data['params']) && is_array($data['params']) ? $data['params'] : [];
                $data['template'] = $this->components[$component];
            }

            $this->loaded[$component] = $data;
        }

        return $data;
    }

    public function ___applyTemplateFileMethods(): void
    {
        $this->wire()->addHookMethod('TemplateFile::el', function (HookEvent $e) {
            $name = $e->arguments(0);
            $attrs = $e->arguments(1);
            $contents = $e->arguments(2);
            
            $e->return = new Element($name ?: 'div', is_array($attrs) ? $attrs : [], $contents);
        });

        $this->wire()->addHookMethod('TemplateFile::attrs', function (HookEvent $e) {
            $attrs = $e->arguments(0);
            if (!is_array($attrs)) {
                $attrs = [];
            }
            $e->return = Element::attrs($attrs);
        });
    }

    public function ___transform(array $component): array
    {
        if (isset($component['transform']) && $component['transform'] instanceof \Closure) {
            $component['params'] = $component['transform']($component['params'], $component);
        }
        return $component['params'];
    }

    public function ___getAttrs(array $attrs, array $component): array
    {
        if (!isset($attrs['id'])) {
            $attrs['id'] = '';
        }

        if (!isset($attrs['class'])) {
            $attrs['class'] = [];
        } else if (isset($attrs['class']) && is_string($attrs['class'])) {
            $attrs['class'] = [$attrs['class']];
        }

        if (isset($component['attrs'])) {
            if ($component['attrs'] instanceof \Closure) {
                $attrs = $component['attrs']($attrs, $component);
            } else if (is_array($component['attrs'])) {
                $attrs = array_merge($attrs, $component['attrs']);
            }
        }

        return $attrs;
    }

    public function ___applyFunctions(array $component): void
    {   
        if (isset($component['fn']) && is_array($component['fn'])) {
            foreach ($component['fn'] as $name => $fn) {
                $this->wire()->addHookMethod("TemplateFile::{$name}", $fn);
            }
        }
    }

    public function ___cacheable(): bool
    {
        return !$this->wire()->config->debug || !$this->wire()->user->isSuperuser();
    }

    public function ___renderReady(array $component): array
    {
        return $component;
    }

    public function ___render(string $component, array $params = [], array $attrs = [], string|null $cacheName = null, int|Page|string|null $cacheExpire = null): string
    {
        $component = $this->getComponent($component);

        if (!$component) {
            return '';
        }

        $component['params'] = array_merge($component['params'], $params);
        $component['params'] = $this->transform($component);

        $render = true;

        if (isset($component['render'])) {
            if ($component['render'] instanceof \Closure) {
                $render = $component['render']($component['params']);
            } else {
                $render = $component['render'];
            }
        }

        if (!$render) {
            return '';
        }
        
        $component['fn'] = $this->applyFunctions($component);
        $component['attrs'] = $this->getAttrs($attrs, $component);
        $component = $this->renderReady($component);

        if ($this->cacheable()) {
            
            $cache = [
                'name' => $cacheName,
                'expire' => $cacheExpire
            ];
    
            if (!$cache['name'] && !$cache['expire'] && isset($component['cache'])) {

                if ($component['cache'] instanceof \Closure) {
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
                $output = $this->wire()->cache->renderFile($component['template'], $cache['expire'], [
                    'name' => $this->className() . "-{$component['name']}-{$cache['name']}",
                    'vars' => $component
                ]);
            }

        }
        
        $output = $this->wire()->files->render($component['template'], $component);

        if (isset($component['output']) && $component['output'] instanceof \Closure) {
            return $component['output']($output);
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