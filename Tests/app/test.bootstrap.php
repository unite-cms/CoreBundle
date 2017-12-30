<?php

passthru(sprintf('rm -rf %s',__DIR__. '/var'));
require __DIR__.'/../../vendor/autoload.php';