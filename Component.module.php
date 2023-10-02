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
        $this->wire('component', new \Altivebir\Component\Component());
    }

    /**
     * Initialize module
     *
     * @return void
     */
    public function init()
    {
        // do some stuff
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