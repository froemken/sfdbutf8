<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/sfdbutf8.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Sfdbutf8\Command;

use Doctrine\DBAL\DBALException;
use StefanFroemken\Sfdbutf8\Converter\CollationConverter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Convert collation of tables and columns to a specified collation
 */
class ConvertCollationCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription(
            'Set the collation of all tables and columns to a given collation. If needed the character set will be set accordingly'
        );

        $this->addArgument(
            'collation',
            InputArgument::REQUIRED,
            'Set the collation like: utf8_general_ci'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Change collation of tables and columns');
        $io->text([
            'We will now start to convert tables and columns',
            'to collation: ' . $input->getArgument('collation'),
            'If a column is not UTF8 we will also change the CHARACTER SET of table and column',
            ''
        ]);

        $collationConverter = $this->getCollationConverter();
        try {
            $collationConverter->convert($input->getArgument('collation'), false);
        } catch (DBALException $DBALException) {
            // Do nothing. We call convert() with false which does not execute any exception
        }

        ProgressBar::setFormatDefinition(
            'sfdbutf8',
            'Progress %tablename%' . chr(10) . ' %current%/%max% [%bar%] %percent:3s%%'
        );

        $progressBar = $io->createProgressBar();
        $progressBar->setFormat('sfdbutf8');
        foreach ($progressBar->iterate($collationConverter->getAvailableTablenames()) as $tableName) {
            try {
                $progressBar->setMessage($tableName, 'tablename');
                $collationConverter->executeAlterStatementsForTable($tableName);
            } catch (DBALException $DBALException) {
                $output->writeln('Altering table' . $tableName . ' fails. Error: ' . $DBALException->getMessage());
            }
        }

        $io->info('All tables and columns converted');

        return 0;
    }

    protected function getCollationConverter(): CollationConverter
    {
        return GeneralUtility::makeInstance(CollationConverter::class);
    }
}
