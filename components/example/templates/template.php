<?php

namespace ProcessWire;

/**
 * @var Files $files
 * @var array $params
 * @var array $attrs
 */

$template = $this->getComponentTemplate(__DIR__, $params['theme']);

echo $files->render($template, compact('files', 'params', 'attrs'));
