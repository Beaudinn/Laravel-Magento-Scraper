<?php

/*
 * This file is part of Laravel MagentoScraper.
 *
 * (c) Beaudinn Greve <beaudinngreve@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BeaudinnGreve\MagentoScraper;
use Illuminate\Support\ServiceProvider;

/**
 * This is the magentoscraper service provider class.
 *
 * @author Beaudinn Greve <beaudinngreve@gmail.com>
 */
class MagentoScraperServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $source = realpath(__DIR__.'/../config/magentoscraper.php');

        $this->publishes([$source => config_path('magentoscraper.php')]);

        $this->mergeConfigFrom($source, 'magentoscraper');
    }


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
 
        $this->registerMagentoScraper();
    }



    /**
     * Register the scraper class.
     *
     * @return void
     */
    protected function registerMagentoScraper()
    {


        $this->app['magentoscraper'] = $this->app->share(function($app)
        {
            return new MagentoScraper($app['config']['magentoscraper']);
        });

        
        $this->app->alias('magentoscraper', 'BeaudinnGreve\MagentoScraper\MagentoScraper');

    }


    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return array();
    }
}
