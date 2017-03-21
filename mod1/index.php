<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2016 Stefan Froemken <froemken@gmail.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH . 'init.php');
require_once($BACK_PATH . 'template.php');

$LANG->includeLLFile('EXT:sfdbutf8/mod1/locallang.xml');
require_once(PATH_t3lib . 'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF, 1); // This checks permissions and exits if the users has no permission for entry.
// DEFAULT initialization of a module [END]

/**
 * Module 'DB UFT-8' for the 'sfdbutf8' extension.
 *
 * @author Stefan Froemken <froemken@gmail.com>
 * @package TYPO3
 * @subpackage tx_sfdbutf8
 */
class tx_sfdbutf8_module1 extends t3lib_SCbase {

    /**
     * @var array
     */
    public $pageInfo = array();

    /**
     * Main function of the module. Write the content to $this->content
     * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
     *
     * @return void
     */
    public function main() {
        global $BE_USER,$LANG,$BACK_PATH;

        // Access check!
        // The page will show only if there is a valid page and if this page may be viewed by the user
        $this->pageInfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
        $access = is_array($this->pageInfo) ? 1 : 0;

        if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{

                // Draw the header.
            $this->doc = t3lib_div::makeInstance('mediumDoc');
            $this->doc->backPath = $BACK_PATH;
            $this->doc->form = '<form action="" method="POST">';

                // JavaScript
            $this->doc->JScode = '
                <script language="javascript" type="text/javascript">
                    script_ended = 0;
                    function jumpToUrl(URL)	{
                        document.location = URL;
                    }
                </script>
            ';
            $this->doc->postCode = '
                <script language="javascript" type="text/javascript">
                    script_ended = 1;
                    if (top.fsMod) top.fsMod.recentIds["web"] = 0;
                </script>
            ';

            $headerSection = $this->doc->getHeader('pages',$this->pageInfo,$this->pageInfo['_thePath']).'<br />'.$LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.t3lib_div::fixed_lgd_pre($this->pageInfo['_thePath'],50);

            $this->content .= $this->doc->startPage($LANG->getLL('title'));
            $this->content .= $this->doc->header($LANG->getLL('title'));
            $this->content .= $this->doc->spacer(5);
            $this->content .= $this->doc->section('',$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])));
            $this->content .= $this->doc->divider(5);


            // Render content:
            $this->moduleContent();


            // ShortCut
            if ($BE_USER->mayMakeShortcut()) {
                $this->content .= $this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
            }

            $this->content .= $this->doc->spacer(10);
        } else {
            // If no access or if ID == zero
            $this->doc = t3lib_div::makeInstance('mediumDoc');
            $this->doc->backPath = $BACK_PATH;

            $this->content .= $this->doc->startPage($LANG->getLL('title'));
            $this->content .= $this->doc->header($LANG->getLL('title'));
            $this->content .= $this->doc->spacer(5);
            $this->content .= $this->doc->spacer(10);
        }
    }

    /**
     * Prints out the module HTML
     *
     * @return void
     */
    public function printContent() {
        $this->content .= $this->doc->endPage();
        echo $this->content;
    }

    /**
     * Generates the module content
     *
     * @return void
     */
    public function moduleContent() {
        global $LANG;

        $content = '';
        if (t3lib_div::_GP('do') == 'check') {
            //$content .= 'GET:'.t3lib_div::view_array( $_GET ).'<br />';
            //$content .= 'POST:'.t3lib_div::view_array( $_POST ).'<br />';

            //show all tables with additional settings
            $res = $this->getDatabaseConnection()->sql_query('SHOW TABLE STATUS');
            $content .= '<p>' . $this->getDatabaseConnection()->sql_num_rows($res) . $LANG->getLL('txt_records_found') . TYPO3_db . '</p><p>&nbsp;</p>';
            $content .= '<p>' . $LANG->getLL('txt_explain_page_list') . '</p><p>&nbsp;</p>';

            //submit-button
            $content .= '<input type="hidden" name="utf8format" value="'.t3lib_div::_GP('utf8format') . '"><br />';
            $content .= '<input type="hidden" name="do" value="db2utf8"><br />';
            $content .= '<input type="submit" value="DB->utf8"><br />';

            //generate the tablelisting
            $i = 1;
            $content .= '<table border="2" cellpadding="1">';
            $content .= '<tr><th>Nr</th><th>Name</th><th>Engine/Type</th><th>Collation</th></tr>';
            while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                if ($row['Collation'] != t3lib_div::_GP('utf8format')) {
                    $content .= '<tr style="background-color: #FF0000;">';
                } else {
                    $content .= '<tr style="background-color: #00FF00;">';
                }
                $content .= '<td>' . $i . '</td>';
                $content .= '<td>' . $row['Name'] . '</td>';
                $content .= '<td>' . $row['Engine'] . '</td>';
                $content .= '<td>' . $row['Collation'] . '</td>';
                $content .= '</tr>';
                $i++;

                $res1 = $this->getDatabaseConnection()->sql_query('
                    SHOW FULL COLUMNS
                    FROM ' . $row['Name'] . '
                    WHERE Collation <> \'\'
                ');
                while ($row1 = $this->getDatabaseConnection()->sql_fetch_assoc($res1)) {
                    if ($row1['Collation'] != t3lib_div::_GP('utf8format')) {
                        $content .= '<tr style="background-color: #FF0000;">';
                    } else {
                        $content .= '<tr style="background-color: #00FF00;">';
                    }
                    $content .= '<td>&nbsp;</td>';
                    $content .= '<td>' . $row1['Field'] . '</td>';
                    $content .= '<td>' . $row1['Type'] . '</td>';
                    $content .= '<td>' . $row1['Collation'] . '</td>';
                    $content .= '</tr>';
                }
            }
            $content .= '</table>';
        } elseif( t3lib_div::_GP('do') === 'db2utf8' ) {
            //generate table with updated database tables
            $res = $this->getDatabaseConnection()->sql_query(
                'SHOW TABLE STATUS'
            );
            while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                if (t3lib_div::_GP('utf8format') != $row['Collation']) {
                    $this->getDatabaseConnection()->sql_query('
                        ALTER TABLE ' . $row['Name'] . '
                        ENGINE=' . $row['Engine'] . ', DEFAULT CHARSET=utf8, COLLATE ' . t3lib_div::_GP('utf8format')
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
                    if (t3lib_div::_GP('utf8format') != $row1['Collation']) {
                        $this->getDatabaseConnection()->sql_query('
                            ALTER TABLE ' . $row['Name'] . '
                            CHANGE ' . $row1['Field'] . ' ' . $row1['Field'] . ' ' . $row1['Type'] . '
                            CHARACTER SET utf8
                            COLLATE ' . t3lib_div::_GP('utf8format') .
                            $default.$null
                        );
                    }
                }
            }
            //check-button
            $content .= '<input type="hidden" name="utf8format" value="' . t3lib_div::_GP('utf8format') . '"><br />';
            $content .= '<input type="hidden" name="do" value="check"><br />';
            $content .= '<input type="submit" value="DB check"><br />';
        } else {
            $content .= '<p>' . $LANG->getLL('txt_welcome') . '</p><p>&nbsp;</p>';
            $content .= '<p>' . $LANG->getLL('txt_tip') . '</p>';
            $content .= '<p>[SYS][UTF8filesystem] = 1<br />';
            $content .= '[BE][forceCharset] = utf-8</p><p>&nbsp;</p>';
            $content .= '<p>' . $LANG->getLL('txt_choose_charset') . '</p>';

            $content .= '<select name="utf8format" size="1">';
            $res = $this->getDatabaseConnection()->sql_query('SHOW COLLATION WHERE Charset="utf8"');
            while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                if ($row['Collation'] == 'utf8_general_ci') {
                    $content .= '<option value="' . $row['Collation'] . '" selected>' . $row['Collation'] . '</option>';
                } else {
                    $content .= '<option value="' . $row['Collation'] . '">' . $row['Collation'] . '</option>';
                }
            }
            $content .= '</select>';

            $content .= '<p><input type="hidden" name="do" value="check"><br />';
            $content .= '<input type="submit" value="DB check"></p>';
        }
        $this->content .= $this->doc->section('Datenbank auf UTF-8 setzen:', $content, 0, 1);
    }

    /**
     * get TYPO3 database connection
     *
     * @return t3lib_DB
     */
    protected function getDatabaseConnection() {
        return $GLOBALS['TYPO3_DB'];
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/sfdbutf8/mod1/index.php'])	{
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/sfdbutf8/mod1/index.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_sfdbutf8_module1');
$SOBE->init();

// Include files?
foreach ($SOBE->include_once as $INC_FILE) {
    include_once($INC_FILE);
}

$SOBE->main();
$SOBE->printContent();