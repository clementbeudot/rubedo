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
 * Console Accessible controller.
 * Can be called by Phing or directly by the command php public/index.php
 *
 * This dump/Restore controller will verify the migration zip files stored in data/migration
 */

namespace Inovia\DumpRestore\Controller;

use Rubedo\Services\Manager;
use Zend\Mvc\Controller\AbstractActionController;

class DumpRestoreController extends AbstractActionController {

    public function restoreAction()
    {
        /* @var $migrationService \Inovia\DumpRestore\Service\Migration */
        $migrationService = Manager::getService('inovia.service.migration');
        $migrationService->migrate();

    }

}