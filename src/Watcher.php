<?php

namespace Totoglu\Component;

use ProcessWire\Page;
use ProcessWire\Template;

use function ProcessWire\wire;

class Watcher
{
    protected array $watch = [];

    public function __construct() {}

    public function watch(Page|Template|array|string|int $watch): Watcher
    {
        if (!$watch) {
            return $this;
        }

        if ($watch instanceof Page) {
            $this->watch[] = $watch->modified;
            if ($watch->template && $watch->template->filenameExists()) {
                $this->watch[] = $watch->template->filename();
            }
        } else if ($watch instanceof Template && $watch->template->filenameExists()) {
            $this->watch[] = $watch->filename();
        } else if (is_array($watch)) {
            $this->watch = array_merge($this->watch, $watch);
        } else if (is_string($watch)) {
            if (strpos($watch, '/') !== false) {
                $this->watch[] = $watch;
            } else {
                /** @var \ProcessWire\Component $module */
                $module = wire('component');
                if ($component = $module->loadComponent($watch)) {
                    $this->watch($component['files']);
                    $this->watch(array_values($module->getComponentTemplates($watch)));

                    foreach ($module->getComponentLayouts($watch) as $key => $value) {
                        $files[] = is_array($value) ? $value['filename'] : $value;
                    }

                    foreach ($module->getComponentFields($watch) as $key => $value) {
                        $files[] = is_array($value) ? $value['filename'] : $value;
                    }
                }
            }
        } else {
            $this->watch[] = $watch;
        }
        return $this;
    }

    public function modified(): int
    {
        $files = $this->watch;
        $times = [];

        $files = array_unique($files);
        foreach ($files as $filename) {
            if (!is_file($filename) && !is_int($filename)) {
                continue;
            }
            $times[] = is_int($filename) ? $filename : filemtime($filename);
        }

        return max($times);
    }
}
