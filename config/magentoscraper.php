<?php

/*
 * This file is part of Laravel MagentoScraper.
 *
 * (c) Beaudinn Greve <beaudinngreve@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [


    'baseurl'   => 'http://www.probo.nl',
    'skuPrefix' => 'XPR-10',

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    |
    | This defines the CssSelector for selecting data attributes
    | Its like jquery :)
    |
    | Default to [].
    |
    */

    'filters' => [

        'product' => [
                'name'              => '.product-name h1',
                'short_description' => '.short-description .std p',
                'description'       => '.tabs-panels .panel .std',
                'image'             => '.img-box .product-image img',
                'meta_title'        => 'title',
                'sku'               => '.no-display input',
                'meta_keyword'      => 'meta[name=keywords]',
                'meta_description'  => 'meta[name=description]',
        ],

        'options' => [
                'check'         => '.optionTabs',
                'item'          => 'div.optionTabs div div.product-options div.optionOuter',
                'title'         => 'dt label',
                'values'        => [
                                    'types' => [
                                        'radio'    => 'dd div.input-box ul li',
                                        'select'    => 'dd div.input-box select option',
                                        'input'     => 'dd div.input-box input',
                                    ],
                                    'title'         => 'span.optiontitle',
                                    'sku'           => 'span.optionsku', 
                                    'description'   => 'span.label div div.tooltip'


                ]
        ]
    ],

];
