<?php
return array(
    'elasticsearch' => array(
        'scheme' => 'http',
        'host'   => 'localhost',
        'port'   => 9200,
        'type'   => 'geonames',
        'index'  => 'cities'
    ),
    'installer' => array(
        'dataLocalPath'  => 'data/geonamesServer',
        'countries' => 'FR',
        'translateName' => false
    ),
    'controllers' => array(
        'invokables' => array(
            'GeonamesServer\Controller\Console' => 'GeonamesServer\Controller\ConsoleController'
        ),
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
                'geonames_status' => array(
                    'options' => array(
                        'route'    => 'geonames_status',
                        'defaults' => array(
                            'controller' => 'GeonamesServer\Controller\Console',
                            'action'     => 'status'
                        )
                    )
                ),
                'geonames_install' => array(
                    'options' => array(
                        'route'    => 'geonames_install',
                        'defaults' => array(
                            'controller' => 'GeonamesServer\Controller\Console',
                            'action'     => 'install'
                        )
                    )
                )
            )
        )
    ),
);