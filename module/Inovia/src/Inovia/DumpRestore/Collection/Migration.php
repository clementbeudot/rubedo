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

namespace Inovia\DumpRestore\Collection;

use Rubedo\Collection\AbstractCollection;
use Rubedo\Services\Manager;
use WebTales\MongoFilters\Filter;

class Migration extends AbstractCollection {

    const COLLECTION_NAME = 'Migrations';

    protected $_indexes = array(
        array(
            'keys' => array(
                'migration_number' => 1
            ),
            'options' => array(
                'unique' => true
            )
        ),
    );


    /**
     * Call the parent method init.
     * Usualy used to define which fields are readable.
     * @throws \Rubedo\Exceptions\Server
     */
    protected function _init()
    {
        parent::_init();
    }

    /**
     * Set the collection name
     */
    public function __construct()
    {
        $this->_collectionName = self::COLLECTION_NAME;
        parent::__construct();
    }

    /**
     * Create a new entry in the Migrations collection
     * Each time a migration is executed we add the number
     * of the migration so we can know which migrations to execute
     *
     * @see \Rubedo\Interfaces\IDataAccess::create
     * @param array $obj
     *            data object
     * @param array $options
     * @return array
     */
    public function create(array $obj, $options = array())
    {
        if (isset($obj['migration_number'])) {
            $migrationNumberFiltered    = Filter::factory()
                ->addFilter(
                    Filter::factory('Value')
                        ->setName('migration_number')
                        ->setValue(
                            new \MongoRegex("/^".$obj["migration_number"]."$/i")
                        )
                );

            $rawMigration     = Manager::getService("Migrations")->getList($migrationNumberFiltered);

            if (count($rawMigration["data"]) !== 0) {
                throw new \Inovia\Exceptions\Migration('Failed to create new Migration. It seems that it already exists', "Exception45");
            }
        }

        $returnValue = parent::create($obj, $options);

        return $returnValue;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Rubedo\Collection\AbstractCollection::findById()
     */
    public function findById($contentId, $forceReload = false)
    {
        if ($contentId === null) {
            return null;
        }
        $result = parent::findById($contentId, $forceReload);

        return $result;
    }

    /**
     * Returns the latest revision that where insered in the database
     * @return array
     */
    public function findLatestMigrationRepository()
    {
        return parent::getList(
            null,
            array(
                'property' => "migration.revision",
                "direction" => 'DESC'
            ),
            null,
            1
        );

    }


}