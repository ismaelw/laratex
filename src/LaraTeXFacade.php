<?php declare(strict_types=1);

namespace Websta\LaraTeX;

use Illuminate\Support\Facades\Facade;

class LaraTeXFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'laratex';
    }
}
