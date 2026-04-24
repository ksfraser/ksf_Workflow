<?php

require_once __DIR__ . '/../../ksf_Calendar/vendor/autoload.php';

$base = __DIR__ . '/../src/Ksfraser/Workflow/';

spl_autoload_register(function ($class) use ($base) {
    if (strpos($class, 'Ksfraser\\Workflow\\') === 0) {
        $rel = str_replace('Ksfraser\\Workflow\\', '', $class);
        $path = $base . str_replace('\\', '/', $rel) . '.php';
        if (file_exists($path)) require $path;
    }
});