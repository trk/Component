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
        // do some stuff
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

    public function ___getAttrs(array $attrs): array
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

    public function ___getFunctions(array $component): array
    {
        $functions = [
            //
        ];
        
        if (isset($component['fn']) && is_array($component['fn'])) {
            $functions += $component['fn'];
        }

        return $functions;
    }

    public function ___renderReady(array $component): array
    {
        return $component;
    }

    public function render(string $component, array $params = [], array $attrs = []): string
    {
        $component = $this->getComponent($component);

        if (!$component) {
            return '';
        }

        $component['params'] = array_merge($component['params'], $params);

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

        $component['attrs'] = $this->getAttrs($attrs);
        $component['fn'] = $this->getFunctions($component);
        $component = $this->renderReady($component);
        
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