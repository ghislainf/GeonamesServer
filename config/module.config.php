<?php
return array(
    'elasticsearch' => array(
        'url'   => 'http://localhost:9200/',
        'type'  => 'geonames',
        'index' => 'cities'
    ),
    'installer' => array(
        'dataLocalPath'  => 'data/geonamesServer',
        'countries' => 'FR',
        'translateName' => false
    ),
    'controllers' => array(
        'invokables' => array(
            'GeonamesServer\Controller\Console' => 'GeonamesServer\Controller\ConsoleController',
            'GeonamesServer\Controller\Api' => 'GeonamesServer\Controller\ApiController'
        ),
    ),
    'router' => array(
        'routes' => array(
            'geonames-search' => array(
                'type'    => 'Segment',
                'options' => array(
                    'route'    => '/geonames/_search/:query[/:page][/:size]',
                    'constraints' => array(
                        'query' => '[^/]{3,}',
                        'page'  => '[0-9]+',
                        'size'  => '[0-9]+'
                    ),
                    'defaults' => array(
                        'controller' => 'GeonamesServer\Controller\Api',
                        'action' => 'search',
                    ),
                ),
            ),
            'geonames-get' => array(
                'type'    => 'Segment',
                'options' => array(
                    'route'    => '/geonames/_get/:geonameids',
                    'constraints' => array(
                        'geonameids' => '[0-9]{2,}(,[0-9]{2,})*',
                    ),
                    'defaults' => array(
                        'controller' => 'GeonamesServer\Controller\Api',
                        'action' => 'get',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'strategies' => array(
            'ViewJsonStrategy',
        ),
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
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