<?php

declare(strict_types=1);

// Standalone checkout uses its own vendor/; inside the mono repo, borrow the root autoloader.
if (is_file($own = dirname(__DIR__).'/vendor/autoload.php')) {
    require $own;
} else {
    $loader = require dirname(__DIR__, 3).'/vendor/autoload.php';
    $loader->addPsr4('Survos\\MenuBundle\\', dirname(__DIR__).'/src');
    $loader->addPsr4('Survos\\MenuBundle\\Tests\\', __DIR__);
}
