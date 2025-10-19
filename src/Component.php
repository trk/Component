<?php

namespace Totoglu\Component;

use ProcessWire\WireFileTools;

use function ProcessWire\wire;

class Component
{
    public string $name;

    protected array $component = [];

    public Component|null $parent = null;

    public array $defaults = [];

    public array $fields = [];

    public array $props = [];

    public array $attrs = [];

    public array $children = [];

    public array $tags = [];

    public function __construct(string $name, array $component = [])
    {
        $this->name = $name;

        if ($component) {
            $this->component = $component;

            $this->name = $this->name ?: $component['name'];

            if ($component['parent']) {
                $this->parent = $component['parent'] instanceof Component ? $component['parent'] : new Component($component['parent']);
            }

            $this->props = $component['params'] ?? [];
            $this->attrs = $component['attrs'] ?? [];
            $this->children = $component['children'] ?? [];
            $this->tags = $component['tags'] ?? [];
        }
    }

    public function getParent(Component $parent, string $name, ?string $property = null)
    {
        if ($parent->name == $name) {
            return $property ? $parent->prop($property, '') : $parent;
        } else if ($parent->parent()) {
            return $this->getParent($parent->parent(), $name, $property);
        }

        return null;
    }

    public function addParent(Component $parent)
    {
        $this->parent = $parent;
    }

    public function parent(): Component|null
    {
        return $this->parent;
    }

    public function addProps(array $props): void
    {
        $this->props = array_merge($this->props, $props);
    }

    public function addProp(string $key, mixed $value = null): void
    {
        $this->props[$key] = $value;
    }

    public function props(): array
    {
        return $this->props;
    }

    public function prop(string $key, mixed $value = null): mixed
    {
        return $this->props[$key] ?? $value;
    }

    public function addAttrs(array $attrs): void
    {
        $this->attrs = array_merge($this->attrs, $attrs);
    }

    public function addAttr(string $key, mixed $value = null): void
    {
        $this->attrs[$key] = $value;
    }

    public function attrs(): array
    {
        return $this->attrs;
    }

    public function attr(string $key, mixed $value = null): mixed
    {
        return $this->attrs[$key] ?? $value;
    }

    public function addChildren(array $children): void
    {
        foreach ($children as $child) {
            $this->addChild($child);
        }
    }

    public function children(): array
    {
        return $this->children;
    }

    public function addChild(array $child): void
    {
        $child['parent'] = $this;
        $this->children[] = $child;
    }

    public function child(int $index)
    {
        return $this->children[$index] ?? null;
    }

    public function tags(): array
    {
        return $this->tags;
    }

    public function addTags(array $tags): void
    {
        $this->tags = array_merge($this->tags, $tags);
    }

    public function addTag(string $name, string $tag): void
    {
        $this->tags[$name] = $tag;
    }

    public function tag(string $name): string|null
    {
        return $this->tags[$name] ?? null;
    }

    /**
     * !@TODO
     * get templates files, template*, field*, layout* and return bigger filemtime
     * use this when make cache control
     */
    public function modified(): int
    {
        return 0;
    }

    public function render(array $props = [], array $attrs = []): string
    {
        /** @var WireFileTools $files */
        $files = wire('files');

        return '';
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
