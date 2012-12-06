<?php
use Symfony\CS\FixerInterface;

$srcPath = realpath(__DIR__  . '/src');

$finder = Symfony\CS\Finder\DefaultFinder::create()
          ->filter(function ($fileinfo) use ($srcPath) {
                return (false !== stripos($fileinfo->getRealPath(), $srcPath));
            });

return Symfony\CS\Config\Config::create()
       ->finder($finder);
