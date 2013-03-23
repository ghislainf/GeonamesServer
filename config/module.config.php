<?php
return array(
    'geonames_server' => array(
        'installer' => array(
            'dataLocalPath'  => 'data/geonamesServer',
            'translateName' => false
        )
    ),
    'controllers' => array(
        'invokables' => array(
            'GeonamesServer\Controller\Console'=> 'GeonamesServer\Controller\ConsoleController',
            'GeonamesServer\Controller\Api'    => 'GeonamesServer\Controller\ApiController',
            'GeonamesServer\Controller\Demo'   => 'GeonamesServer\Controller\DemoController'
        )
    ),
    'router' => array(
        'routes' => array(
            'geonames' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/geonames',
                    'defaults' => array(
                        'controller' => 'GeonamesServer\Controller\Demo',
                        'action' => 'index',
                    )
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'geonames-search' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/_search/:query[/:page][/:size]',
                            'constraints' => array(
                                'query' => '[^/]{3,}',
                                'page'  => '[0-9]+',
                                'size'  => '[0-9]+'
                            ),
                            'defaults' => array(
                                'controller' => 'GeonamesServer\Controller\Api',
                                'action' => 'search',
                            )
                        )
                    ),
                    'geonames-get' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/_get/:geonameids',
                            'constraints' => array(
                                'geonameids' => '[0-9]{2,}(,[0-9]{2,})*',
                            ),
                            'defaults' => array(
                                'controller' => 'GeonamesServer\Controller\Api',
                                'action' => 'get',
                            )
                        )
                    )
                )
            )
        )
    ),
    'view_manager' => array(
        'strategies' => array(
            'ViewJsonStrategy',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
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
    )
);
