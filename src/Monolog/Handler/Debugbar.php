<?php

namespace Snowair\Debugbar\Monolog\Handler;

use  Monolog\Handler\AbstractProcessingHandler;
use Snowair\Debugbar\PhalconDebugbar;

class Debugbar extends AbstractProcessingHandler
{

    protected $_levelMap = array(
        0=>'EMERGENCY',
        1=>'CRITICAL',
        2=>'ALERT',
        3=>'ERROR',
        4=>'WARNING',
        5=>'NOTICE',
        6=>'INFO',
        7=>'DEBUG',
        8=>'CUSTOM',
        9=>'SPECIAL'
    );

    /**
     * @param integer $level  The minimum logging level at which this handler will be triggered
     * @param Boolean $bubble Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct( PhalconDebugbar $debugbar)
    {
        $this->_debugbar = $debugbar;
        parent::__construct();
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     *
     * @return void
     */
    protected function write( array $record )
    {
        if ($this->_debugbar->hasCollector('log') && $this->_debugbar->shouldCollect('log') ) {
            $type = array_keys( $this->_levelMap,$record['level_name']);
            $this->_debugbar->getCollector('log')
                ->add( $record['message'], end($type), microtime(true), $record['context']);
        }
    }
}