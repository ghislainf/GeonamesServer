<?php
return array(
    'geonames_server' => array(
        'elasticsearch' => array(
            'url'   => 'http://localhost:9200/',
            'type'  => 'geonames',  // Type index elasticsearch
            'index' => 'cities'     // Index elasticsearch
        ),
        'installer' => array(
            'countries' => 'all',  // Countries indexes, ex : "all" OR "DE" OR "FR,DE"
        ),
        'demo' => array(
            'enable' => true
        )
    )
);
