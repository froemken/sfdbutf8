<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/sfdbutf8.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Sfdbutf8\EventListener;

use Doctrine\DBAL\Event\SchemaAlterTableEventArgs;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use TYPO3\CMS\Core\Database\Schema\TableDiff;

/**
 * Event listener to handle additional processing for table collation.
 */
class SchemaAlterTableListener
{
    /**
     * @return AbstractPlatform
     */
    protected $platform;

    /**
     * Listener for alter table events. This intercepts the building
     * of ALTER TABLE statements and adds the required statements to
     * change the COLLATE on MySQL platforms if necessary.
     */
    public function onSchemaAlterTable(SchemaAlterTableEventArgs $event): bool
    {
        $this->platform = $event->getPlatform();

        /** @var TableDiff $tableDiff */
        $tableDiff = $event->getTableDiff();

        // Original Doctrine TableDiff without table options, continue default processing
        if (!$tableDiff instanceof TableDiff) {
            return false;
        }

        // Table options are only supported on MySQL, continue default processing
        if (!$event->getPlatform() instanceof MySqlPlatform) {
            return false;
        }

        // No changes in table options, continue default processing
        if ($tableDiff->getTableOptions() === []) {
            return false;
        }

        // Add an ALTER TABLE statement to change the table collation to the list of statements.
        if ($tableDiff->hasTableOption('collation')) {
            $tableOption = $tableDiff->getTableOption('collation');
            $event->addSql(sprintf(
                'ALTER TABLE %s CHARACTER SET = %s COLLATE = %s',
                $tableDiff->getName($event->getPlatform())->getQuotedName($event->getPlatform()),
                $this->quote($this->extractCharsetFromCollation($tableOption)),
                $this->quote($tableOption)
            ));
        }

        // continue default processing for all other changes.
        return false;
    }

    protected function extractCharsetFromCollation(string $collation): string
    {
        [$charset] = explode('_', $collation);

        return $charset;
    }

    protected function quote(string $value): string
    {
        return $this->platform->quoteStringLiteral($value);
    }
}
