<?php
/*
Plugin Name:        ISP Blade Controller
Plugin URI:         https://github.com/treyduffy/insyncplus
Description:        ISP Customized Blade Controller Plugin
Version:            9.0.4
Author:             Flightless Nerds | Darren Jacoby
Author URI:         http://github.com/treyduffy/
License:            MIT License
License URI:        http://opensource.org/licenses/MIT
GitHub Plugin URI:  treyduffy/controller
GitHub Branch:      master
*/

namespace Sober\Controller;

/**
 * Functions
 */
function loader()
{
    $loader = new Loader();
    foreach ($loader->getData() as $template => $class) {
	    $class::employ();
        // Pass data filter
        add_filter('sage/template/' . $template . '-data/data', function ($data) use ($loader, $class) {
            $controller = new $class();
            $controller->__setup();
            return array_merge($loader->getAppData(), $loader->getPostData(), $controller->__setTreeData($data), $controller->__getData());
        });
        // Class alais
        class_alias($class, (new \ReflectionClass($class))->getShortName());
    }
}

function debugger()
{
    if (function_exists('\\App\\sage')) {
        \App\sage('blade')->compiler()->directive('debug', function ($type) {
            $debugger = ($type === '' ? '"controller"' : $type);
            return '<?php (new \Sober\Controller\Module\Debugger(get_defined_vars(), ' .  $debugger . ')); ?>';
        });
    }
}

/**
 * Hooks
 */
if (function_exists('add_action')) {
    add_action('init', __NAMESPACE__ . '\loader');
    add_action('init', __NAMESPACE__ . '\debugger');
}
