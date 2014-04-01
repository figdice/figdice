<?php
spl_autoload_register( function($class) {
        if (substr($class, 0, 8) == 'figdice\\') {
                require_once  dirname(__FILE__) . '/src/figdice/' . str_replace('\\', '/', substr($class, 8)) . '.php';
        }
});

