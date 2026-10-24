<?php

arch('no debugging statements are left in the code')
    ->expect(['dd', 'dump', 'ddd', 'ray', 'var_dump', 'die'])
    ->not->toBeUsed();
