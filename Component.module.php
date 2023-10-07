<?php

namespace ProcessWire;

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
            'version' => 1,
            'summary' => '',
            'href' => 'https://www.altivebir.com',
            'author' => 'İskender TOTOĞLU | @ukyo(community), @trk (Github), https://www.altivebir.com',
            'requires' => [
                'PHP>=7.0',
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
        $this->wire()->addHookMethod('TemplateFile::attrs', function (HookEvent $e) {
            $attrs = $e->arguments(0, []);
            if (is_array($attrs)) {
                $e->return = componentAttrs($attrs);
            }
        });
    }

    public function ___transform(array $component): array
    {
        if (isset($component['transform']) && $component['transform'] instanceof \Closure) {
            $component['params'] = $component['transform']($component['params']);
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

    public function ___renderReady(array $component): array
    {
        return $component;
    }

    public function ___render(string $component, array $params = [], array $attrs = [], string $cacheName = '', int|Page|string|null $expire = null): string
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

        if ($cacheName && $expire && ($this->wire()->config->debug || $this->wire()->user->isSuperuser())) {
            $expire = 0;
        }

        // if we use directly this method, result directly stored in database, check for cache name and expire before store output
        if ($cacheName && $expire) {
            return $this->wire()->cache->renderFile($component['template'], $expire, [
                'name' => "{$component['name']}-{$cacheName}",
                'vars' => $component
            ]);
        }
        
        return $this->wire()->files->render($component['template'], $component);
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

        // do some stuff
        
        return $inputfields;
    }
}