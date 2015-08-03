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

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
/**
 * This is the magentoscraper class.
 *
 * @author Beaudinn Greve <beaudinngreve@gmail.com>
 */
class MagentoScraper
{

    private $client;
    private $filters;
    private $optionsConfig;

    /**
     *  Construct Magento Scraper Instance
     *
     *  @return void
     */
    public function __construct($config)
    {   
        $this->client    = new Client();
        $this->filters = $config['filters'];
    }

    /**
     * Setup our scraper data. Which includes the url that
     * we want to scrape
     *
     * @param (String) $url = default is NULL
     *        (String) $method = Method Types its either POST || GET
     * @return void
     */
    public function setScrapeUrl($url = NULL, $method = 'GET')
    {
        $this->crawler = $this->client->request($method, $url);
        return $this->crawler;
    }

    /**
     * 
     */
    private function setOptionsConfig(){

        $this->crawler->filter('script')->each(function(Crawler $node, $i) {

            $scriptContents = $node->text();
            if (($pos = strpos($scriptContents, "config :")) !== FALSE) { 
                $scriptContents = substr($scriptContents, $pos+9); 
                $scriptContents = substr($scriptContents, 0, strpos($scriptContents, "}]") + 2);
                
                return $this->optionsConfig = json_decode($scriptContents, true);
            }

        });
    }

    /**
     * This will get all the return Result from our Web Scraper
     *
     * @return array
     */
    public function getProduct( $filters ){

        return $this->product = $this->startProductScraper( $filters );
    }

    public function getOptions(){

        return $this->options = $this->startOptionsScraper();
    }

    /**
     * It will handle all the scraping logic, filtering
     * and getting the data from the defined url in our method setScrapeUrl()
     *
     * @return array
     */
    private function startProductScraper( $filters ){

        // lets check if there is a product.
        // The use CssSelector Dom Components like jquery for selecting data attributes.
       $check = $this->crawler->filter($filters['product']['name'])->count();

        if ($check) {

            $this->contents =  [
                        'name'              => $this->crawler->filter($this->filters['product']['name'])->text(),
                        'short_description' => $this->replaceBrand($this->crawler->filter($this->filters['product']['short_description'])->text()),
                        'description'       => $this->replaceBrand($this->crawler->filter($this->filters['product']['description'])->html()),
                        'image'             => [$this->crawler->filter($this->filters['product']['image'])->attr('src')],
                        'sku'               => $this->crawler->filter($this->filters['product']['sku'])->first()->text(),
                        'meta_title'        => $this->replaceBrand($this->crawler->filter($this->filters['product']['meta_title'])->text()),
                        'meta_keyword'      => $this->replaceBrand($this->crawler->filter($this->filters['product']['meta_keyword'])->attr('content')),
                        'meta_description'  => $this->replaceBrand($this->crawler->filter($this->filters['product']['meta_description'])->attr('content')),
                ];
        }
        return $this->contents;
    }

    private function startOptionsScraper(){

        // lets check if our filter has result.
        $countContent = $this->crawler->filter($this->filters['options']['check'])->count();

        if ($countContent) {
            // loop through in each "wrapper elements" to get the data that we need.
            $this->crawler->filter($this->filters['options']['item'])->each(function(Crawler $node, $i) {

                    $item = [];
                    $item['title']     = preg_replace("/[&*$]+/", "", $node->filter($this->filters['options']['title'])->text());
                    $item['id']        = $this->getOptionId($node->attr('class'));

                    if(!$item['id']){

                        $countContent = $node->filter($this->filters['options']['values']['types']['input'])->count();
                        if($countContent){
                           $item['id'] =  $this->getOptionId($node->filter($this->filters['options']['values']['types']['input'])->first()->attr('id'));  

                        }

                        if(!$item['id']){
                            return;
                        }
                    }

                    $item['layout']     = $this->optionsConfig[0][$item['id']][1];
                    
                    list($item['values'], $item['type']) = $this->scrapeValues($node);

                    $this->options[] = $item;
                
            });
        }
        return $this->options;
    }

    private function scrapeValues( Crawler $node ){
         $type = '';
         
        //Radio options
        $values = $node->filter($this->filters['options']['values']['types']['radio'])->each(function(Crawler $node, $i) {

            $item = [];
            $item['id']             = trim($node->filter('input')->attr('value'));
            $item['title']          = $node->filter($this->filters['options']['values']['title'])->text();
            $item['sku']            = $node->filter($this->filters['options']['values']['sku'])->text();
            $item['image']          = $this->optionsConfig[1][$item['id']][0];
            $item['code']           = $this->optionsConfig[1][$item['id']][1];
            $item['childs']         = array_merge($this->optionsConfig[1][$item['id']][2], $this->optionsConfig[1][$item['id']][3]);
            $item['description']    = '';

            if($node->filter($this->filters['options']['values']['description'])->count()){

                $item['description']    = $node->filter($this->filters['options']['values']['description'])->text();
            }

            return $item;
        });

        if(count($values) > 0){
            $type = 'radio';
        }
        
        if(count($values) <= 0){

            $values = $node->filter($this->filters['options']['values']['types']['select'])->each(function(Crawler $node, $i) {

                $value = $node->attr('value');
                if($value){
                    $item = [];
                    $item['id'] = trim($value);
                    $item['title'] = trim($value->text());
                    $item['childs'] = array_merge($jsonconfig[1][$item['id']][2], $jsonconfig[1][$item['id']][3]);
                }
                return $item;
            });

            if(count($values) > 0){
                $type = 'drop_down';
            }
        }

        $countContent = $node->filter($this->filters['options']['values']['types']['input'])->count();
        if(count($values) === 0 && $countContent){
            $item = [];
            $type = 'field';
        }

        return [$values, $type];
    }
}
