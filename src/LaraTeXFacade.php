<?php

namespace Ismaelw\LaraTeX;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Ismaelw\LaraTeX\Skeleton\SkeletonClass
 */
class LaraTeXFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laratex';
    }
}
