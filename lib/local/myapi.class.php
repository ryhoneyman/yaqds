<?php

include_once 'common/apibase.class.php';

class MyAPI extends LWPLib\APIBase
{
   protected $version  = 1.0;
   
   /**
    * __construct
    *
    * @param  LWPLib\Debug|null $debug
    * @param  array|null $options
    * @return void
    */
    public function __construct($debug = null, $options = null)
    {
        parent::__construct($debug,$options);

        $this->authType('auth.header.bearer');

        $this->loadUris([
            'v1-authenticate'              => '/v1/auth/token',
            'v1-data-provider-query'       => '/v1/data/provider/query/{{database}}',
            'v1-data-provider-query-table' => '/v1/data/provider/query/{{database}}/{{table}}',
            'v1-data-provider-modify'      => '/v1/data/provider/modify/{{database}}',
            'v1-item'                      => '/v1/item/{{id}}',
            'v1-item-search'               => '/v1/item/search',
            'v1-item-description'          => '/v1/item/description/{{id}}',
            'v1-spell'                     => '/v1/spell/{{id}}',
            'v1-spell-data'                => '/v1/data/spell/',
        ]);
    }

    /**
     * v1SpellData
     * 
     * @return bool|array
     */
    public function v1SpellData()
    {
        $request = [
            'params' => [],
            'options' => [
                'method' => 'GET',
            ],
        ];

        if (!$this->makeRequest('v1-spell-data','auth,json',$request)) { 
            $this->error($this->clientError());
            return false; 
        }

        return $this->clientResponse();
    } 
    
    /**
     * v1Spell
     *
     * @param  int $spellId
     * @return bool|array
     */
    public function v1Spell(int $spellId)
    {
        $request = [
            'params' => ['id' => $spellId],
            'options' => [
                'method' => 'GET',
            ],
        ];

        if (!$this->makeRequest('v1-spell','auth,json',$request)) { 
            $this->error($this->clientError());
            return false; 
        }

        return $this->clientResponse();
    } 

    /**
     * v1Item
     *
     * @param  int $itemId
     * @return bool|array
     */
    public function v1Item(int $itemId)
    {
        $request = [
            'params' => ['id' => $itemId],
            'options' => [
                'method' => 'GET',
            ],
        ];

        if (!$this->makeRequest('v1-item','auth,json',$request)) { 
            $this->error($this->clientError());
            return false; 
        }

        return $this->clientResponse();
    } 

    /**
     * v1ItemSearch
     *
     * @param  int $itemName
     * @return bool|array
     */
    public function v1ItemSearch(int $itemName, bool $likeSearch, $maxReturns = null)
    {
        $request = [
            'data' => [
                'name' => $itemName,
                'like' => $likeSearch,
                'max'  => $maxReturns,
            ],
            'options' => [
                'method' => 'POST',
            ],
        ];
 
        if (!$this->makeRequest('v1-item','auth,json',$request)) { 
            $this->error($this->clientError());
            return false; 
        }
    
        return $this->clientResponse();
    } 
           
    /**
        * v1DataProviderBindQuery
        *
        * @param  string $database
        * @param  string $statement
        * @param  string|null $types
        * @param  array|null $data
        * @param  array|null $options
        * @return mixed
        */
    public function v1DataProviderBindQuery($database, $statement, $types = null, $data = null, $options = null)
    {
        $single = $options['single'] ?: false;

        $request = [
            'params' => ['database' => $database],
            'data' => [
                'statement' => $statement, 
                'types'     => $types,
                'data'      => $data,
                'single'    => $single
            ],
            'options' => [
                'method' => 'POST',
            ]
        ];

        if (!$this->makeRequest('v1-data-provider-query','auth,json',$request)) { 
            $this->error($this->clientError());
            return false; 
        }

        return $this->clientResponse();
    }

    /**
        * v1DataProviderTableData
        *
        * @param  string $database
        * @param  string $table
        * @param  array|null $options
        * @return mixed
        */
    public function v1DataProviderTableData($database, $table, $options = null)
    {
        $options = (is_array($options)) ? $options : [];

        $request = [
            'params' => ['database' => $database, 'table' => $table],
            'data' => $options,
            'options' => [
                'method' => 'POST',
            ]
        ];

        if (!$this->makeRequest('v1-data-provider-query-table','auth,json',$request)) { 
            $this->error($this->clientError());
            return false; 
        }

        return $this->clientResponse();
    }

    /**
    * v1Authenticate
    *
    * @param  string $clientId
    * @param  string $clientSecret
    * @return bool
    */
    public function v1Authenticate($clientId, $clientSecret)
    {
        $request = [
            'data' => ['client_id' => $clientId, 'client_secret' => $clientSecret],
            'options' => [
                'timeout' => 15,
            ],
        ];

        $requestResult = $this->makeRequest('v1-authenticate','json',$request);

        if ($requestResult === false) { $this->error($this->clientError()); return false; }

        $token = $this->clientResponseValue('token');

        if (!$token) { $this->error('Could not authenticate'); return false; }

        $this->authToken = $token;

        return true;
    } 
}