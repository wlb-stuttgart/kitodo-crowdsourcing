<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Wlb\Crowdsourcing\Common\Solr;

use Solarium\Client;
use Solarium\Core\Client\Adapter\Curl;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Wlb\Crowdsourcing\Services\ExtensionConfigurationService;

class SolrClient
{
    /**
     * @var Client The Solr client service object
     */
    protected Client $client;


    /**
     * @var SolrClient
     */
    protected static SolrClient $instance;


    protected function __construct()
    {
        $adapter         = new Curl();
        $eventDispatcher = new EventDispatcher();
        $config          = $this->getSolrConfig();
        $this->client    = new Client($adapter, $eventDispatcher, $config);
    }

    /**
     * @return SolrClient
     */
    public static function getInstance(): SolrClient
    {
        if (empty(self::$instance)) {
            self::$instance = new SolrClient();
        }

        return self::$instance;
    }

    /**
     * Getter for $client property
     *
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @return array[]
     */
    private function getSolrConfig()
    {
        $host = ExtensionConfigurationService::getInstance()->getConfigurationValue('solrHost');
        $port = ExtensionConfigurationService::getInstance()->getConfigurationValue('solrPort');
        $path = ExtensionConfigurationService::getInstance()->getConfigurationValue('solrPath');
        $core = ExtensionConfigurationService::getInstance()->getConfigurationValue('solrCore');

        $config = [
            'endpoint' => [
                'solr' => [
                    'host' => $host,
                    'port' => $port,
                    'path' => $path,
                    'core' => $core
                ],
            ],
        ];

        return $config;
    }


}