<?php

namespace Paymongo\Services;

class BaseService {
    /** @var \Paymongo\PaymongoClient PayMongo client */
    public $client;
    /** @var \Paymongo\HttpClient HTTP client */
    public $httpClient;
    
    public function __construct($client)
    {
        $this->client = $client;
        $this->httpClient = new \Paymongo\HttpClient($this->client->config['api_key']);
    }
}