<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed exclusively to Inovia Team.
 *
 * @copyright  Copyright (c) 2015 Inovia Team (http://www.inovia.fr)
 * @license      All rights reserved
 * @author       The Inovia Dev Team
 *
 */

return array(
    'console' => array(
        'router' => array(
            'routes' => array(
                'restore' => array(
                    'options' => array(
                        'route'    => 'restore',
                        'defaults' => array(
                            'controller' => 'Inovia\DumpRestore\Controller\DumpRestore',
                            'action'     => 'restore'
                        )
                    )
                )
            )
        )
    ),
    'controllers' => array(
        'invokables' => array(
            'Inovia\\DumpRestore\\Controller\\DumpRestore' => 'Inovia\\DumpRestore\\Controller\\DumpRestoreController',
        )
    ),
    'service_manager' => array(
        'invokables' => array(
            'inovia.service.migration'      => 'Inovia\\DumpRestore\\Service\\Migration',
            'inovia.collection.migration'   => 'Inovia\\DumpRestore\\Collection\\Migration'
        ),
        'aliases' => array(
            'Migrations' => 'inovia.collection.migration',
        ),
    ),
);