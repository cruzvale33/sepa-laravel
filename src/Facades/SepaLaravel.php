<?php

namespace SepaLaravel\SepaLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SepaLaravel\SepaLaravel\SepaLaravel
 */
class SepaLaravel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SepaLaravel\SepaLaravel\SepaLaravel::class;
    }
}
