<?php
/**
 * Rubedo -- ECM solution
 * Copyright (c) 2014, WebTales (http://www.webtales.fr/).
 * All rights reserved.
 * licensing@webtales.fr
 *
 * Open Source License
 * ------------------------------------------------------------------------------------------
 * Rubedo is licensed under the terms of the Open Source GPL 3.0 license.
 *
 * @category   Rubedo
 * @package    Rubedo
 * @copyright  Copyright (c) 2012-2013 WebTales (http://www.webtales.fr)
 * @license    http://www.gnu.org/licenses/gpl.html Open Source GPL 3.0 license
 */

namespace RubedoAPITest\Rest\V1;

use Rubedo\Services\Manager;
use RubedoAPI\Rest\V1\ContentsRessource;

class ContentsRessourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \RubedoAPI\Rest\V1\ContentsRessource
     */
    protected $ressource;

    function setUp()
    {
        $this->ressource = new ContentsRessource();
        parent::setUp();
    }

    function tearDown()
    {
        Manager::resetMocks();
        parent::tearDown();
    }

    public function testDefinition()
    {
        $this->assertNotNull($this->ressource->getDefinition()->getVerb('get'));
    }
} 