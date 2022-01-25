<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/sfdbutf8.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Sfdbutf8\Controller;

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Controller to alter collation of database tables and columns
 */
class Utf8Controller extends ActionController
{
    /**
     * The default view object to use if none of the resolved views can render
     * a response for the current request.
     *
     * @var string
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    protected function initializeView(ViewInterface $view): void
    {
        $buttonBar = $this->view->getModuleTemplate()
            ->getDocHeaderComponent()
            ->getButtonBar();

        // Bookmark
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setModuleName('tools_sfdbutf8')
            ->setGetVariables(['route', 'module', 'id'])
            ->setDisplayName('SF DB UTF-8 Module');
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    public function showAction(): void
    {
        $collations = [];
        $connection = $this->getConnectionPool()->getConnectionByName('Default');
        $statement = $connection->query('SHOW COLLATION WHERE Charset like "utf8%"');
        while ($row = $statement->fetch()) {
            $collations[$row['Collation']] = $row['Collation'];
        }
        $this->view->assign('collations', $collations);
    }

    public function dbCheckAction(string $collation): void
    {
        // show all tables with additional settings
        $connection = $this->getConnectionPool()->getConnectionByName('Default');
        $tableStatement = $connection->query('SHOW TABLE STATUS');

        $tables = [];
        while ($table = $tableStatement->fetch()) {
            $columnStatement = $connection->query('SHOW FULL COLUMNS FROM ' . $table['Name'] . ' WHERE Collation <> \'\'');
            while ($column = $columnStatement->fetch()) {
                $table['columns'][] = $column;
            }
            $tables[] = $table;
        }

        $this->view->assign('collation', $collation);
        $this->view->assign('tables', $tables);
    }

    public function convertAction(string $collation): void
    {
        // show all tables with additional settings
        $connection = $this->getConnectionPool()->getConnectionByName('Default');
        $statement = $connection->query('SHOW TABLE STATUS');
        [$charset] = explode('_', $collation);

        while ($table = $statement->fetch()) {
            if ($collation !== $table['Collation']) {
                $connection
                    ->query(
                        'ALTER TABLE ' . $table['Name'] . '
                        ENGINE=' . $table['Engine'] . ', DEFAULT CHARSET=' . $charset . ', COLLATE ' . $collation
                    )
                    ->execute();
            }
            $columnStatement = $connection->query(
                'SHOW FULL COLUMNS
                FROM ' . $table['Name'] . '
                WHERE Collation <> \'\''
            );

            while ($column = $columnStatement->fetch()) {
                $default = '';
                if ($column['Default']) {
                    $default = ' DEFAULT \'' . $column['Default'] . '\'';
                }

                $null = '';
                if ($column['Null'] === 'NO') {
                    $null = ' NOT NULL';
                }

                if ($collation !== $column['Collation']) {
                    $connection->query(
                        'ALTER TABLE ' . $table['Name'] . '
                        CHANGE ' . $column['Field'] . ' ' . $column['Field'] . ' ' . $column['Type'] . '
                        CHARACTER SET ' . $charset . '
                        COLLATE ' . $collation .
                        $default . $null
                    );
                }
            }
        }

        $this->redirect('show');
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
