<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/sfdbutf8.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Sfdbutf8\Converter;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use StefanFroemken\Sfdbutf8\EventListener\SchemaAlterTableListener;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\Comparator;
use TYPO3\CMS\Core\Database\Schema\TableDiff;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Converter to change the collation of tables and columns
 */
class CollationConverter
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * @var AbstractSchemaManager
     */
    protected $schemaManager;

    /**
     * @var Schema
     */
    protected $fromSchema;

    /**
     * @var Schema
     */
    protected $toSchema;

    public function __construct()
    {
        try {
            $this->connection = $this->getConnectionPool()->getConnectionByName(
                ConnectionPool::DEFAULT_CONNECTION_NAME
            );
        } catch (DBALException $DBALException) {
            throw new \RuntimeException('Connection "Default" is not configured');
        }

        try {
            $this->platform = $this->connection->getDatabasePlatform();
            $eventManager = $this->platform->getEventManager();
            if ($eventManager instanceof EventManager) {
                $eventManager->addEventListener(
                    Events::onSchemaAlterTable,
                    GeneralUtility::makeInstance(SchemaAlterTableListener::class)
                );
            }
        } catch (\Exception $exception) {
            throw new \RuntimeException('Invalid platform specifies for connection "Default"');
        }

        if (!$this->platform instanceof AbstractPlatform) {
            throw new \RuntimeException('No platform specifies for connection "Default"');
        }

        $this->schemaManager = $this->connection->getSchemaManager();
        if (!$this->schemaManager instanceof AbstractSchemaManager) {
            throw new \RuntimeException('No SchemaManager found for Connection "Default"');
        }

        $this->fromSchema = $this->schemaManager->createSchema();
        $this->toSchema = $this->schemaManager->createSchema();
    }

    /**
     * Main method which will search for different collation in table options
     * and executes the ALTER statements for each table and column
     *
     * @throws DBALException
     */
    public function convert(string $collation, bool $executeStatements = true): void
    {
        foreach ($this->getAvailableTablenames() as $tablename) {
            $this->updateDiffForTableAndColumns($tablename, $collation);
        }

        if ($executeStatements === true) {
            $this->compareAndExecuteAlterStatements();
        }
    }

    protected function updateDiffForTableAndColumns(string $tablename, string $collation): void
    {
        try {
            $fromTableDefinition = $this->fromSchema->getTable($tablename);
            if ($fromTableDefinition->hasOption('collation')) {
                $tableToDefinition = $this->toSchema->getTable($tablename);
                $tableToDefinition->addOption('collation', $collation);

                foreach ($tableToDefinition->getColumns() as $column) {
                    if ($column->hasPlatformOption('collation')) {
                        $column->setPlatformOption('charset', $this->extractCharsetFromCollation($collation));
                        $column->setPlatformOption('collation', $collation);
                    }
                }
            }
        } catch (SchemaException $schemaException) {
            // Do nothing. This method will be called with tables directly with available tables of DB
        }
    }

    /**
     * Get all available tables of Default connection.
     * Useful, if you want to execute ALTER table by table. See ConvertCollationCommand
     *
     * @return string[]
     */
    public function getAvailableTablenames(): array
    {
        return $this->schemaManager->listTableNames();
    }

    /**
     * @throws DBALException
     */
    protected function compareAndExecuteAlterStatements(): void
    {
        foreach ($this->getAvailableTablenames() as $tableName) {
            $this->executeAlterStatementsForTable($tableName);
        }
    }

    protected function getAlterStatements(): array
    {
        $comparator = GeneralUtility::makeInstance(Comparator::class, $this->platform);
        $schemaDiff = $comparator->compare($this->fromSchema, $this->toSchema);

        return array_merge_recursive(
            $this->getChangedTableOptions($schemaDiff),
            $this->getChangedFieldUpdateSuggestions($schemaDiff)
        );
    }

    /**
     * Useful, if you want to execute ALTER table by table. See ConvertCollationCommand
     *
     * @throws DBALException
     */
    public function executeAlterStatementsForTable(string $tableName): int
    {
        static $alterStatements = null;

        if ($alterStatements === null) {
            $alterStatements = $this->getAlterStatements();
        }

        $amountOfTables = 0;
        if (array_key_exists($tableName, $alterStatements)) {
            $amountOfTables = count($alterStatements[$tableName]);
            foreach ($alterStatements[$tableName] as $alterStatementForTable) {
                $this->connection->query($alterStatementForTable);
            }
        }

        return $amountOfTables;
    }

    /**
     * Extract update suggestions (SQL statements) for changed options
     * (like ENGINE) from the complete schema diff.
     */
    protected function getChangedTableOptions(SchemaDiff $schemaDiff): array
    {
        $updateSuggestions = [];

        foreach ($schemaDiff->changedTables as $tableName => $tableDiff) {
            // Skip processing if this is the base TableDiff class or has no table options set.
            if (!$tableDiff instanceof TableDiff || count($tableDiff->getTableOptions()) === 0) {
                continue;
            }

            $tableOptions = $tableDiff->getTableOptions();
            $tableOptionsDiff = new TableDiff($tableDiff->name, [], [], [], [], [], [], $tableDiff->fromTable);
            $tableOptionsDiff->setTableOptions($tableOptions);

            $tableOptionsSchemaDiff = GeneralUtility::makeInstance(
                SchemaDiff::class,
                [],
                [$tableOptionsDiff],
                [],
                $schemaDiff->fromSchema
            );

            $statements = $tableOptionsSchemaDiff->toSaveSql($this->platform);
            foreach ($statements as $statement) {
                $updateSuggestions[$tableName][] = $statement;
            }
        }

        return $updateSuggestions;
    }

    /**
     * Extract update suggestions (SQL statements) for changed fields
     * from the complete schema diff.
     */
    protected function getChangedFieldUpdateSuggestions(SchemaDiff $schemaDiff): array
    {
        $updateSuggestions = [];

        foreach ($schemaDiff->changedTables as $tableName => $changedTable) {
            if (count($changedTable->changedColumns) !== 0) {
                // Treat each changed column with a new diff to get a dedicated suggestion
                // just for this single column.
                try {
                    $fromTable = $this->buildQuotedTable($schemaDiff->fromSchema->getTable($changedTable->name));
                } catch (SchemaException $schemaException) {
                    // Table does not exist
                    continue;
                }

                if ($fromTable === null) {
                    // Tablename is empty
                    continue;
                }

                foreach ($changedTable->changedColumns as $columnName => $changedColumn) {
                    if ($changedColumn->fromColumn !== null) {
                        $changedColumn->fromColumn = $this->buildQuotedColumn($changedColumn->fromColumn);
                    }

                    // Build a dedicated diff just for the current column
                    $tableDiff = GeneralUtility::makeInstance(
                        TableDiff::class,
                        $changedTable->name,
                        [],
                        [$columnName => $changedColumn],
                        [],
                        [],
                        [],
                        [],
                        $fromTable
                    );

                    $temporarySchemaDiff = GeneralUtility::makeInstance(
                        SchemaDiff::class,
                        [],
                        [$tableDiff],
                        [],
                        $schemaDiff->fromSchema
                    );

                    $statements = $temporarySchemaDiff->toSql($this->platform);
                    foreach ($statements as $statement) {
                        $updateSuggestions[$tableName][] = $statement;
                    }
                }
            }
        }

        return $updateSuggestions;
    }

    /**
     * Helper function to build a table object that has the _quoted attribute set so that the SchemaManager
     * will use quoted identifiers when creating the final SQL statements. This is needed as Doctrine doesn't
     * provide a method to set the flag after the object has been instantiated and there's no possibility to
     * hook into the createSchema() method early enough to influence the original table object.
     */
    protected function buildQuotedTable(Table $table): ?Table
    {
        try {
            return new Table(
                $this->platform->quoteIdentifier($table->getName()),
                $table->getColumns(),
                $table->getIndexes(),
                $table->getForeignKeys(),
                0,
                $table->getOptions()
            );
        } catch (DBALException $exception) {
            return null;
        }
    }

    /**
     * Helper function to build a column object that has the _quoted attribute set so that the SchemaManager
     * will use quoted identifiers when creating the final SQL statements. This is needed as Doctrine doesn't
     * provide a method to set the flag after the object has been instantiated and there's no possibility to
     * hook into the createSchema() method early enough to influence the original column object.
     */
    protected function buildQuotedColumn(Column $column): Column
    {
        return GeneralUtility::makeInstance(
            Column::class,
            $this->platform->quoteIdentifier($column->getName()),
            $column->getType(),
            array_diff_key($column->toArray(), ['name', 'type'])
        );
    }

    /**
     * If we change the collation from latin1_swedish_ci to utf8_general_ci we also
     * have to change the CHARACTER SET to utf8.
     * Use this method to extract the charset from given collation
     *
     * Example: utf8_general_ci -> utf8
     */
    protected function extractCharsetFromCollation(string $collation): string
    {
        [$charset] = explode('_', $collation);

        return $charset;
    }

    /**
     * @throws \Exception
     */
    protected function getConnectionForTable(string $table): Connection
    {
        try {
            return $this->getConnectionPool()->getConnectionForTable($table);
        } catch (DBALException $DBALException) {
            throw new \InvalidArgumentException(
                'Could not get connection for table ' . $table . '. Maybe table does not exists'
            );
        }
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
