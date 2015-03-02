<?php

/**
 * Register application modules
 */
$application->registerModules(array(
    'frontend' => array(
        'className' => 'Myphalcon\Frontend\Module',
        'path' => __DIR__ . '/../app/frontend/Module.php'
    )
));
