<?php

namespace ProcessWire;

/**
 * @var Files $files
 * @var array $params
 * @var array $attrs
 */

$template = getComponentTemplate(__DIR__, $params['theme']);

echo $files->render($template, compact('files', 'params', 'attrs'));
