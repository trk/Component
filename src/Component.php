<?php

namespace Altivebir\Component;

use ProcessWire\Config;

use function ProcessWire\wire;

class Component
{
    protected array $components = [];

    protected array $loaded = [];

    public function __construct()
    {
        $this->setComponents();
    }

    protected function setComponents(): void
    {
        /** @var Config $config */
        $config = wire('config');

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

    protected function getFunctions(): array
    {
        return [];
    }

    public function render(string $component, array $params = []): string
    {
        $component = $this->getComponent($component);

        if (!$component) {
            return '';
        }

        $component['params'] = array_merge($component['params'], $params);

        $render = true;

        if (isset($component['render'])) {
            if ($component['render'] instanceof \Closure) {
                $render = $component['render']($params);
            } else {
                $render = $component['render'];
            }
        }

        $component['fn'] = isset($component['fn']) && is_array($component['fn']) ? array_merge($this->getFunctions(), $component['fn']) : $this->getFunctions();

        return $render ? wire('files')->render($component['template'], $component) : '';
    }
}
