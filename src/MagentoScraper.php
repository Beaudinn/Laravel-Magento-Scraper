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

use Exception;
use Goutte\Client;
use ApplicationException;
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
    private $options = [];
    private $upsells = [];
    private $crawler;
    private $children = [];

    public $node;

    /**
     *  Construct Magento Scraper Instance
     *
     *  @return void
     */
    public function __construct($config)
    {   
        $this->client   = new Client();
        $this->filters  = $config['filters'];
        $this->brand    = $config['brand'];
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

        $response = $this->client->getResponse()->getStatus();
        if($response !== '200' && $response !== 200){
            throw new ApplicationException('response the client ad url '.$url.' got: '.$response);
        }

        return $this->crawler;
    }

    public function login($filters, $button){
        // select the form and fill in some values
        $form = $this->crawler->selectButton('Inloggen')->form();

        foreach ($filters as $key => $filter) {
            $form[$key] = $filter;
        }

        // submit that form
        $this->crawler = $this->client->submit($form);
    }

    public function scrape($filters, $force = false){

        $response = [];
        foreach ($filters as $key => $filter) {

            $selector = !is_array($filter) ? $filter : $filter[0];

            $node = $currentNode = $this->node ? $this->node : $this->crawler;

            if($selector && $selector !== '')
                $node = $currentNode->filter($selector);

            if(!$node->count()){

                if($force){
                    $response[$key] = null;
                    continue;
                }
                throw new Exception('The current node list is empty. ['.$selector.'] ');
            }

            //If not array and only selector is specified
            if(!is_array($filter)){
                $response[$key] = $this->get($node);
                continue;
            }

            //Call an anonymous function on each node of the list
            if(isset($filter['each']) && $filter['each']){
                $attributes = [];

                
                $attributes[] = $node->each(function(Crawler $node, $i) use($filter, $force){
                    $this->node = $node;
                    return $this->scrape($filter['each'], $force);
                });
            }

            //Extract attribute
            if(isset($filter['extract']) && $filter['extract'])
                $attributes = $node->extract($filter['extract']);

            if(!isset($attributes))
                $attributes = $this->get($node);

            $response[$key]  = count($attributes) > 1 ? $attributes : $attributes[0];
        }

        $this->node = null;
        return $response;
    }

    public function get($node){

        //get HTML tag name
        $nodeName = $node->nodeName();

        //accessing node value
        if(in_array($nodeName, array('h1','p', 'span', 'title', 'label'))){
           $response = $node->text();

        }elseif(in_array($nodeName, array('div', 'table', 'ul'))){
            $response = $node->html();

        }elseif(in_array($nodeName, array('img'))){
            $response = $node->attr('src');

        }elseif(in_array($nodeName, array('input'))){
            $response = $node->attr('value');

        }elseif(in_array($nodeName, array('meta'))){
            $response = $node->attr('content');
        }else{
            $response = null;
        }

        return $response;
    }
    /**
     * 
     */
    public function getOptionsConfig(){
        $crawler = $this->crawler->filter('script');

        $node = $crawler->first();
        var_dump($scriptContents = $node->text()); die();
        if(count($node)){
            
            $scriptContents = $node->text();
            var_dump($scriptContents); die();
            if (($pos = strpos($scriptContents, "config :")) !== FALSE) { 
                $scriptContents = substr($scriptContents, $pos+9); 
                $scriptContents = substr($scriptContents, 0, strpos($scriptContents, "}]") + 2);
                
               return $this->optionsConfig = json_decode($scriptContents, true);
            }
        }

        return false;
    }

    /**
     * This will get all the return Result from our Web Scraper
     *
     * @return array
     */
    public function getProduct( $filters = [] ){

        return $this->product = $this->startProductScraper( $filters  );
    }

    public function getOptions( $filters = [] ){

        return $this->options = $this->startOptionsScraper( $filters );
    }

    public function getUpsell( $filters = [] ){

        return $this->upsells = $this->startUpsellScraper( $filters );
    }

    public function getChildren(){

        return $this->children;

    }

    /**
     * It will handle all the scraping logic, filtering
     * and getting the data from the defined url in our method setScrapeUrl()
     *
     * @return array
     */
    private function startProductScraper( $filters = [] ){

        $filters = array_merge($this->filters['product'], $filters);
        // lets check if there is a product.
        // The use CssSelector Dom Components like jquery for selecting data attributes.
       $check = $this->crawler->filter($filters['name'])->count();

        if (!$check){
            return false;
        }

        $this->contents = [];
        foreach ($this->filters['product'] as $key => $filter) {
            $node = $this->crawler->filter($filter)->first();
            if(!$node)
                continue;
            $nodeName = $node->nodeName();
         

            if(in_array($nodeName, array('h1','p', 'span', 'title', 'label'))){
               $this->contents[$key] = $this->replaceBrand($node->text());

            }elseif(in_array($nodeName, array('div', 'table'))){
                $this->contents[$key] = $this->replaceBrand($node->html());

            }elseif(in_array($nodeName, array('img'))){
                $this->contents[$key] = $node->attr('src');

            }elseif(in_array($nodeName, array('input'))){
                $this->contents[$key] = $this->replaceBrand($node->attr('value'));

            }elseif(in_array($nodeName, array('meta'))){
                $this->contents[$key] = $this->replaceBrand($node->attr('content'));
            }


            if(!isset($this->contents[$key])){
                var_dump($key,$node->nodeName(), 'Not supported'); die();
            }
        }

        
        return $this->contents;
    }

    private function startOptionsScraper( $filters = [] ){

        $this->optionsFilters = array_merge($this->filters['options'], $filters);

        // lets check if our filter has result.
        $countContent = $this->crawler->filter($this->optionsFilters['check'])->count();

        if (!$countContent){
            return false;
        }

        $this->options = [];
        $this->crawler->filter($this->optionsFilters['item'])->each(function(Crawler $baseNode, $i) {
            if(!$baseNode)
                continue;

            $option = [];
            $filterss = $this->optionsFilters;
            unset($filterss['values']);
            unset($filterss['item']);
            unset($filterss['check']);

            foreach ($filterss as $key => $filter) {
                $node = $baseNode->filter($filter)->first();
                $nodeName = $node->nodeName();
                if(!$node)
                    continue;

                if(in_array($nodeName, array('h1','p', 'span', 'title', 'label', "label"))){

                    if($key === 'id'){
                        $option[$key] = $node->attr('class');
                        if(!$option[$key]){
                            $countContent = $node->filter($this->optionsFilters['values']['types']['input'])->count();
                            if($countContent){
                               $item['id'] =  $this->getOptionId($node->filter($this->optionsFilters['values']['types']['input'])->first()->attr('id'));  

                            }
                        }
                    }else{
                        $option[$key] = $this->replaceBrand($node->text());
                    }
                   

                }elseif(in_array($nodeName, array('div'))){
                    $option[$key] = $this->replaceBrand($node->html());

                }elseif(in_array($nodeName, array('img'))){
                    $option[$key] = $node->attr('src');

                }elseif(in_array($nodeName, array('input'))){
                    $option[$key] = $this->replaceBrand($node->attr('value'));

                }elseif(in_array($nodeName, array('meta'))){
                    $option[$key] = $this->replaceBrand($node->attr('content'));
                }


                if(!isset($option[$key])){
                    var_dump($key,$node->nodeName(), 'Not supported'); die();
                }
            }
            var_dump($option['title']);
            list($option['values'], $option['type'],$option['layout']) = $this->scrapeValues($baseNode, $option);
            $this->options[] = $option;
        });

        return $this->options;
    }

    private function scrapeValues( Crawler $node, $itemtet ){
         $type   = '';
         $layout = '';
         $values = [];
         if($node->filter($this->optionsFilters['values']['types']['radio'])->count()){
            $values = $node->filter($this->optionsFilters['values']['types']['radio'])->each(function(Crawler $baseNode, $i) {

                    if(!$baseNode)
                        continue;

                    $value = [];
                    $filterss = $this->optionsFilters['values'];
                    unset($filterss['types']);
                    unset($filterss['description']);
                    foreach ( $filterss as $key => $filter) {
                        $node = $baseNode->filter($filter)->first();
                        $nodeName = $node->nodeName();
                        if(!$node)
                            continue;

                        if(in_array($nodeName, array('h1','p', 'span', 'title', 'label'))){
                           $value[$key] = $this->replaceBrand($node->text());

                        }elseif(in_array($nodeName, array('div'))){
                            $value[$key] = $this->replaceBrand($node->html());

                        }elseif(in_array($nodeName, array('img'))){
                            $value[$key] = $node->attr('src');

                        }elseif(in_array($nodeName, array('input'))){
                            $value[$key] = $this->replaceBrand($node->attr('value'));

                        }elseif(in_array($nodeName, array('meta'))){
                            $value[$key] = $this->replaceBrand($node->attr('content'));
                        }


                        if(!isset($value[$key])){
                            var_dump($key,$node->nodeName(), 'Not supported'); die();
                        }
                    }

                    $value['id']             = trim($baseNode->filter('input')->attr('value'));
                    $value['image']          = $this->optionsConfig[1][$value['id']][0];
                    $value['code']           = $this->optionsConfig[1][$value['id']][1];
                    $value['childs']         = array_merge($this->optionsConfig[1][$value['id']][2], $this->optionsConfig[1][$value['id']][3]);
                    $value['description']    = '';

                    if($baseNode->filter($this->optionsFilters['values']['description'])->count()){

                        $value['description']    = $baseNode->filter($this->optionsFilters['values']['description'])->text();
                    }

                    return $value;
            });
        };
        
        if(count($values) > 0){
            if($values[0]['image']){

               $layout = 'grid';
            }else{
                
                $layout = 'grid';
            }
            $type = 'radio';
        }
        
        if(count($values) <= 0){

            $values = $node->filter($this->optionsFilters['values']['types']['select'])->each(function(Crawler $node, $i) {
                $item = [];
                $value = $node->attr('value');
                
                if($value && $value !== ''){
                    $item = [];
                    $item['id'] = trim($value);
                    $item['title'] = trim($node->text());
                    $item['childs'] = array_merge($this->optionsConfig[1][$item['id']][2], $this->optionsConfig[1][$item['id']][3]);
                    return $item;
                }
            });

            array_shift($values);

            if(count($values) > 0){
                $layout = 'grid';
                $type = 'radio';
            }
        }

        $countContent = $node->filter($this->optionsFilters['values']['types']['input'])->count();
        if(count($values) === 0 && $countContent){
            $item = [];
            $layout = 'grid';
            $type = 'field';
        }


        foreach ($values as $value) {
            if(!isset($value['id']) ){
                var_dump('sdfsdfsdfsdf',$itemtet,$values);
            }
            $this->children[$value['id']] = $value['childs'];
        }
        return [$values, $type, $layout];
    }

    private function getOptionId( $class ){

        preg_match_all('/([\d]+)/',trim($class), $match);
        
        if(isset($match[1][0])){
            return (int) $match[1][0];
        }

        return 0;
    }

    private function startUpsellScraper( $filters = [] ){

        $this->optionsFilters = array_merge($this->filters['upsell'], $filters);

        // lets p if our filter has result.
        $countContent = $this->crawler->filter($this->optionsFilters['input'])->count();

        if (!$countContent){
            return false;
        }
        $upsells = [];
        // loop through in each "wrapper elements" to get the data that we need.
        $upsells = $this->crawler->filter($this->optionsFilters['input'])->each(function(Crawler $node, $i) {
            return $node->attr('value');
        });
        
        return $upsells;
    }

    private function replaceBrand( $string ){

        $string = preg_replace('/\b'.$this->brand['old'].'\b/u', $this->brand['new'], $string );
        $string = preg_replace('/\b'.strtolower($this->brand['old']).'\b/u', strtolower($this->brand['new']), $string );

        return $string;
    }
}
