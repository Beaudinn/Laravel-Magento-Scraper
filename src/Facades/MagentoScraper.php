<?php

/*
 * This file is part of Laravel MagentoScraper.
 *
 * (c) Graham Campbell <graham@mineuk.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BeaudinnGreve\MagentoScraper\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * This is the magentoscraper facade class.
 *
 * @author Beaudinn Greve <beaudinngreve@gmail.com>
 */
class MagentoScraper extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'magentoscraper';
    }
}
