<?php
namespace StefanFroemken\Sfdbutf8\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use StefanFroemken\KdbXmlImport\Parser\XmlParser;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class Utf8Controller
 *
 * @package StefanFroemken\KdbXmlImport\Controller
 */
class Utf8Controller extends ActionController
{
    /**
     * Show action
     *
     * @return void
     */
    public function showAction()
    {
        $collations = array();
        $res = $this->getDatabaseConnection()->sql_query('SHOW COLLATION WHERE Charset="utf8"');
        while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
            $collations[$row['Collation']] = $row['Collation'];
        }
        $this->view->assign('collations', $collations);
    }
    
    /**
     * DB check action
     *
     * @param string $collation
     *
     * @return void
     */
    public function dbCheckAction($collation)
    {
        //show all tables with additional settings
        $res = $this->getDatabaseConnection()->sql_query('SHOW TABLE STATUS');
        $this->view->assign('numRows', $this->getDatabaseConnection()->sql_num_rows($res));
    
        $tables = array();
        while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
            $tables[] = $row;
        }
        
        foreach ($tables as $key => $table) {
            $res = $this->getDatabaseConnection()->sql_query('SHOW FULL COLUMNS FROM ' . $table['Name'] . ' WHERE Collation <> \'\'');
            while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                $tables[$key]['columns'][] = $row;
            }
        }
    
        $this->view->assign('collation', $collation);
        $this->view->assign('tables', $tables);
    }
    
    /**
     * convert action
     *
     * @param string $collation
     *
     * @return void
     */
    public function convertAction($collation)
    {
        $res = $this->getDatabaseConnection()->sql_query('SHOW TABLE STATUS');
        while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
            if ($collation != $row['Collation']) {
                $this->getDatabaseConnection()->sql_query('
                    ALTER TABLE ' . $row['Name'] . '
                    ENGINE=' . $row['Engine'] . ', DEFAULT CHARSET=utf8, COLLATE ' . $collation
                );
            }
            $res1 = $this->getDatabaseConnection()->sql_query('
                SHOW FULL COLUMNS
                FROM ' . $row['Name'] . '
                WHERE Collation <> \'\'
            ');
            while ($row1 = $this->getDatabaseConnection()->sql_fetch_assoc($res1)) {
                if ($row1['Default']) {
                    $default = ' DEFAULT \'' . $row1['Default'] . '\'';
                } else {
                    $default = '';
                }
                if ($row1['Null']=='NO') {
                    $null = ' NOT NULL';
                } else {
                    $null = '';
                }
                if ($collation != $row1['Collation']) {
                    $this->getDatabaseConnection()->sql_query('
                        ALTER TABLE ' . $row['Name'] . '
                        CHANGE ' . $row1['Field'] . ' ' . $row1['Field'] . ' ' . $row1['Type'] . '
                        CHARACTER SET utf8
                        COLLATE ' . $collation .
                        $default . $null
                    );
                }
            }
        }
        $this->redirect('show');
    }
    
    /**
     * Get TYPO3s Database Connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
