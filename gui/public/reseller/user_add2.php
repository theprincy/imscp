<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * @noinspection
 * PhpDocMissingThrowsInspection
 * PhpUnhandledExceptionInspection
 * PhpIncludeInspection
 */

use iMSCP\Event\Event;
use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;
use iMSCP\PhpEditor;
use iMSCP\Registry;
use iMSCP\TemplateEngine;

/**
 * Get first step data
 *
 * @return bool TRUE if parameters from first step are found, FALSE otherwise
 */
function getFirstStepData()
{
    global $dmnName, $hpId;

    foreach (
        [
            'dmn_name', 'dmn_expire', 'dmn_url_forward', 'dmn_type_forward',
            'dmn_host_forward', 'dmn_tpl'
        ] as $data
    ) {
        if (!array_key_exists($data, $_SESSION)) {
            return false;
        }
    }

    $dmnName = $_SESSION['dmn_name'];
    $hpId = $_SESSION['dmn_tpl'];
    return true;
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl Template engine
 * @return void
 */
function generatePage(TemplateEngine $tpl)
{
    global $hpName, $php, $cgi, $sub, $als, $mail, $mailQuota, $ftp, $sqld,
           $sqlu, $traffic, $diskspace, $backup, $dns, $extMail,
           $webFolderProtection;

    $cfg = Registry::get('config');

    $tpl->assign([
        'VL_TEMPLATE_NAME_VAL' => tohtml($hpName, 'htmlAttr'),
        'VL_TEMPLATE_NAME'     => tohtml($hpName),
        'MAX_SUBDMN_CNT'       => tohtml($sub, 'htmlAttr'),
        'MAX_DMN_ALIAS_CNT'    => tohtml($als, 'htmlAttr'),
        'MAX_MAIL_CNT'         => tohtml($mail, 'htmlAttr'),
        'MAIL_QUOTA'           => tohtml($mailQuota, 'htmlAttr'),
        'MAX_FTP_CNT'          => tohtml($ftp, 'htmlAttr'),
        'MAX_SQL_CNT'          => tohtml($sqld, 'htmlAttr'),
        'VL_MAX_SQL_USERS'     => tohtml($sqlu, 'htmlAttr'),
        'VL_MAX_TRAFFIC'       => tohtml($traffic, 'htmlAttr'),
        'VL_MAX_DISK_USAGE'    => tohtml($diskspace, 'htmlAttr'),
        'VL_EXTMAILY'          => $extMail == '_yes_' ? ' checked' : '',
        'VL_EXTMAILN'          => $extMail == '_yes_' ? '' : ' checked',
        'VL_PHPY'              => $php == '_yes_' ? ' checked' : '',
        'VL_PHPN'              => $php == '_yes_' ? '' : ' checked',
        'VL_CGIY'              => $cgi == '_yes_' ? ' checked' : '',
        'VL_CGIN'              => $cgi == '_yes_' ? '' : ' checked'
    ]);

    if (!resellerHasFeature('subdomains')) {
        $tpl->assign('SUBDOMAIN_FEATURE', '');
    }

    if (!resellerHasFeature('domain_aliases')) {
        $tpl->assign('ALIAS_FEATURE', '');
    }

    if (!resellerHasFeature('custom_dns_records')) {
        $tpl->assign('CUSTOM_DNS_RECORDS_FEATURE', '');
    } else {
        $tpl->assign([
            'VL_DNSY' => $dns == '_yes_' ? ' checked' : '',
            'VL_DNSN' => $dns == '_yes_' ? '' : ' checked'
        ]);
    }

    if (!resellerHasFeature('mail')) {
        $tpl->assign('MAIL_FEATURE', '');
        $tpl->assign('EXT_MAIL_FEATURE', '');
    }

    if (!resellerHasFeature('ftp')) {
        $tpl->assign('FTP_FEATURE', '');
    }

    if (!resellerHasFeature('sql')) {
        $tpl->assign('SQL_FEATURE', '');
    }

    if (!resellerHasFeature('backup')) {
        $tpl->assign('BACKUP_FEATURE', '');
    } else {
        $tpl->assign([
            'VL_BACKUPD' => in_array('_dmn_', $backup) ? ' checked' : '',
            'VL_BACKUPS' => in_array('_sql_', $backup) ? ' checked' : '',
            'VL_BACKUPM' => in_array('_mail_', $backup) ? ' checked' : ''
        ]);
    }

    $tpl->assign([
        'VL_WEB_FOLDER_PROTECTION_YES' => $webFolderProtection == '_yes_'
            ? ' checked' : '',
        'VL_WEB_FOLDER_PROTECTION_NO'  => $webFolderProtection == '_yes_'
            ? '' : ' checked'
    ]);

    $phpini = PhpEditor::getInstance();

    if (!$phpini->resellerHasPermission('phpiniSystem')) {
        $tpl->assign('PHP_EDITOR_BLOCK', '');
        return;
    }

    $tpl->assign([
        'PHP_EDITOR_YES'         => $phpini->clientHasPermission('phpiniSystem')
            ? ' checked' : '',
        'PHP_EDITOR_NO'          => $phpini->clientHasPermission('phpiniSystem')
            ? '' : ' checked',
        'TR_PHP_EDITOR'          => tohtml(tr('PHP Editor')),
        'TR_PHP_EDITOR_SETTINGS' => tohtml(tr('PHP Settings')),
        'TR_SETTINGS'            => tohtml(tr('PHP Settings')),
        'TR_DIRECTIVES_VALUES'   => tohtml(tr('PHP Configuration options')),
        'TR_FIELDS_OK'           => tohtml(tr('All fields are valid.')),
        'TR_MIB'                 => tohtml(tr('MiB')),
        'TR_SEC'                 => tohtml(tr('Sec.'))
    ]);

    EventAggregator::getInstance()->registerListener(
        Events::onGetJsTranslations,
        function (Event $e) {
            $translations = $e->getParam('translations');
            $translations['core']['close'] = tr('Close');
            $translations['core']['fields_ok'] = tr('All fields are valid.');
            $translations['core']['out_of_range_value_error'] = tr('Value for the PHP %%s directive must be in range %%d to %%d.');
            $translations['core']['lower_value_expected_error'] = tr('%%s cannot be greater than %%s.');
            $translations['core']['error_field_stack'] = Registry::isRegistered('errFieldsStack')
                ? Registry::get('errFieldsStack') : [];
        }
    );

    $permissionsBlock = false;

    if (!$phpini->resellerHasPermission('phpiniAllowUrlFopen')) {
        $tpl->assign('PHP_EDITOR_ALLOW_URL_FOPEN_BLOCK', '');
    } else {
        $tpl->assign([
            'TR_CAN_EDIT_ALLOW_URL_FOPEN' => tohtml(tr(
                'Can edit the PHP %s configuration option',
                'allow_url_fopen'
            )),
            'ALLOW_URL_FOPEN_YES'         => $phpini->clientHasPermission(
                'phpiniAllowUrlFopen'
            ) ? ' checked' : '',
            'ALLOW_URL_FOPEN_NO'          => $phpini->clientHasPermission(
                'phpiniAllowUrlFopen'
            ) ? '' : ' checked'
        ]);
        $permissionsBlock = true;
    }

    if (!$phpini->resellerHasPermission('phpiniDisplayErrors')) {
        $tpl->assign('PHP_EDITOR_DISPLAY_ERRORS_BLOCK', '');
    } else {
        $tpl->assign([
            'TR_CAN_EDIT_DISPLAY_ERRORS' => tohtml(tr(
                'Can edit the PHP %s configuration option',
                'display_errors'
            )),
            'DISPLAY_ERRORS_YES'         => $phpini->clientHasPermission(
                'phpiniDisplayErrors'
            ) ? ' checked' : '',
            'DISPLAY_ERRORS_NO'          => $phpini->clientHasPermission(
                'phpiniDisplayErrors'
            ) ? '' : ' checked'
        ]);
        $permissionsBlock = true;
    }

    if ($cfg['HTTPD_SERVER'] == 'apache_itk') {
        $tpl->assign([
            'PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK' => '',
            'PHP_EDITOR_MAIL_FUNCTION_BLOCK'     => ''
        ]);
    } else {
        if ($phpini->resellerHasPermission('phpiniDisableFunctions')) {
            $tpl->assign([
                'TR_CAN_EDIT_DISABLE_FUNCTIONS' => tohtml(tr(
                    'Can edit the PHP %s configuration option',
                    'disable_functions'
                )),
                'DISABLE_FUNCTIONS_YES'         => $phpini->getClientPermission(
                    'phpiniDisableFunctions'
                ) == 'yes' ? ' checked' : '',
                'DISABLE_FUNCTIONS_NO'          => $phpini->getClientPermission(
                    'phpiniDisableFunctions'
                ) == 'no' ? ' checked' : '',
                'DISABLE_FUNCTIONS_EXEC'        => $phpini->getClientPermission(
                    'phpiniDisableFunctions'
                ) == 'exec' ? ' checked' : '',
                'TR_ONLY_EXEC'                  => tr('Only exec')
            ]);
        } else {
            $tpl->assign('PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK', '');
        }

        if ($phpini->resellerHasPermission('phpiniMailFunction')) {
            $tpl->assign([
                'TR_CAN_USE_MAIL_FUNCTION' => tohtml(
                    tr('Can use the PHP %s function', 'mail')
                ),
                'MAIL_FUNCTION_YES'        => $phpini->clientHasPermission(
                    'phpiniMailFunction'
                ) ? ' checked' : '',
                'MAIL_FUNCTION_NO'         => $phpini->clientHasPermission(
                    'phpiniMailFunction'
                ) ? '' : ' checked'
            ]);
        } else {
            $tpl->assign('PHP_EDITOR_MAIL_FUNCTION_BLOCK', '');
        }

        $permissionsBlock = true;
    }

    if (!$permissionsBlock) {
        $tpl->assign('PHP_EDITOR_PERMISSIONS_BLOCK', '');
    } else {
        $tpl->assign([
            'TR_PERMISSIONS' => tr('Permissions'),
            'TR_ONLY_EXEC'   => tr("Only exec")
        ]);
    }

    $tpl->assign([
        'TR_POST_MAX_SIZE'          => tohtml(
            tr('PHP %s configuration option', 'post_max_size')
        ),
        'POST_MAX_SIZE'             => tohtml(
            $phpini->getDomainIni('phpiniPostMaxSize'), 'htmlAttr'
        ),
        'TR_UPLOAD_MAX_FILEZISE'    => tohtml(
            tr('PHP %s configuration option', 'upload_max_filesize')
        ),
        'UPLOAD_MAX_FILESIZE'       => tohtml(
            $phpini->getDomainIni('phpiniUploadMaxFileSize'), 'htmlAttr'
        ),
        'TR_MAX_EXECUTION_TIME'     => tohtml(
            tr('PHP %s configuration option', 'max_execution_time')),
        'MAX_EXECUTION_TIME'        => tohtml(
            $phpini->getDomainIni('phpiniMaxExecutionTime'), 'htmlAttr'
        ),
        'TR_MAX_INPUT_TIME'         => tohtml(
            tr('PHP %s configuration option', 'max_input_time')
        ),
        'MAX_INPUT_TIME'            => tohtml(
            $phpini->getDomainIni('phpiniMaxInputTime'), 'htmlAttr'
        ),
        'TR_MEMORY_LIMIT'           => tohtml(
            tr('PHP %s configuration option', 'memory_limit')
        ),
        'MEMORY_LIMIT'              => tohtml(
            $phpini->getDomainIni('phpiniMemoryLimit'), 'htmlAttr'
        ),
        'POST_MAX_SIZE_LIMIT'       => tohtml(
            $phpini->getResellerPermission('phpiniPostMaxSize'), 'htmlAttr'
        ),
        'UPLOAD_MAX_FILESIZE_LIMIT' => tohtml(
            $phpini->getResellerPermission('phpiniUploadMaxFileSize'),
            'htmlAttr'
        ),
        'MAX_EXECUTION_TIME_LIMIT'  => tohtml(
            $phpini->getResellerPermission('phpiniMaxExecutionTime'),
            'htmlAttr'
        ),
        'MAX_INPUT_TIME_LIMIT'      => tohtml(
            $phpini->getResellerPermission('phpiniMaxInputTime'), 'htmlAttr'
        ),
        'MEMORY_LIMIT_LIMIT'        => tohtml(
            $phpini->getResellerPermission('phpiniMemoryLimit'), 'htmlAttr'
        )
    ]);
}

/**
 * Get hosting plan data
 *
 * @return void
 */
function getHostingPlanData()
{
    global $hpId, $hpName, $php, $cgi, $sub, $als, $mail, $mailQuota, $ftp,
           $sqld, $sqlu, $traffic, $diskspace, $backup, $dns, $extMail,
           $webFolderProtection;

    if ($hpId == 0) {
        return;
    }

    $stmt = exec_query(
        '
            SELECT name, props
            FROM hosting_plans
            WHERE reseller_id = ?
            AND id = ?
        ',
        [$_SESSION['user_id'], $hpId]
    );

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $data = $stmt->fetchRow();

    list(
        $php, $cgi, $sub, $als, $mail, $ftp, $sqld, $sqlu, $traffic,
        $diskspace, $backup, $dns, $phpEditor, $phpiniAllowUrlFopen,
        $phpiniDisplayErrors, $phpiniDisableFunctions, $phpiniMailFunction,
        $phpiniPostMaxSize, $phpiniUploadMaxFileSize, $phpiniMaxExecutionTime,
        $phpiniMaxInputTime, $phpiniMemoryLimit, $extMail,
        $webFolderProtection, $mailQuota
        ) = explode(';', $data['props']);

    $backup = explode('|', $backup);
    $mailQuota = ($mailQuota != '0') ? $mailQuota / 1048576 : '0';
    $hpName = $data['name'];

    $phpini = PhpEditor::getInstance();
    $phpini->setClientPermission('phpiniSystem', $phpEditor);
    $phpini->setClientPermission('phpiniAllowUrlFopen', $phpiniAllowUrlFopen);
    $phpini->setClientPermission('phpiniDisplayErrors', $phpiniDisplayErrors);
    $phpini->setClientPermission(
        'phpiniDisableFunctions', $phpiniDisableFunctions
    );
    $phpini->setClientPermission('phpiniMailFunction', $phpiniMailFunction);

    // Must be set before phpiniPostMaxSize
    $phpini->setDomainIni('phpiniMemoryLimit', $phpiniMemoryLimit);
    // Must be set before phpiniUploadMaxFileSize
    $phpini->setDomainIni('phpiniPostMaxSize', $phpiniPostMaxSize);
    $phpini->setDomainIni('phpiniUploadMaxFileSize', $phpiniUploadMaxFileSize);
    $phpini->setDomainIni('phpiniMaxExecutionTime', $phpiniMaxExecutionTime);
    $phpini->setDomainIni('phpiniMaxInputTime', $phpiniMaxInputTime);
}

/**
 * Check input data
 *
 * @return bool TRUE if all data are valid, FALSE otherwise
 */
function checkInputData()
{
    global $php, $cgi, $sub, $als, $mail, $mailQuota, $ftp, $sqld, $sqlu,
           $traffic, $diskspace, $backup, $dns, $extMail, $webFolderProtection;

    $sub = isset($_POST['nreseller_max_subdomain_cnt'])
        ? clean_input($_POST['nreseller_max_subdomain_cnt']) : $sub;
    $als = isset($_POST['nreseller_max_alias_cnt'])
        ? clean_input($_POST['nreseller_max_alias_cnt']) : $als;
    $mail = isset($_POST['nreseller_max_mail_cnt'])
        ? clean_input($_POST['nreseller_max_mail_cnt']) : $mail;
    $mailQuota = isset($_POST['nreseller_mail_quota'])
        ? clean_input($_POST['nreseller_mail_quota']) : $mailQuota;
    $ftp = isset($_POST['nreseller_max_ftp_cnt'])
        ? clean_input($_POST['nreseller_max_ftp_cnt']) : $ftp;
    $sqld = isset($_POST['nreseller_max_sql_db_cnt'])
        ? clean_input($_POST['nreseller_max_sql_db_cnt']) : $sqld;
    $sqlu = isset($_POST['nreseller_max_sql_user_cnt'])
        ? clean_input($_POST['nreseller_max_sql_user_cnt']) : $sqlu;
    $traffic = isset($_POST['nreseller_max_traffic'])
        ? clean_input($_POST['nreseller_max_traffic']) : $traffic;
    $diskspace = isset($_POST['nreseller_max_disk'])
        ? clean_input($_POST['nreseller_max_disk']) : $diskspace;
    $php = isset($_POST['php']) ? clean_input($_POST['php']) : $php;
    $cgi = isset($_POST['cgi']) ? clean_input($_POST['cgi']) : $cgi;
    $dns = isset($_POST['dns']) ? clean_input($_POST['dns']) : $dns;
    $backup = isset($_POST['backup']) && is_array($_POST['backup'])
        ? $_POST['backup'] : $backup;
    $extMail = isset($_POST['external_mail'])
        ? clean_input($_POST['external_mail']) : $extMail;
    $webFolderProtection = isset($_POST['web_folder_protection'])
        ? clean_input($_POST['web_folder_protection']) : $webFolderProtection;

    $php = $php === '_yes_' ? '_yes_' : '_no_';
    $cgi = $cgi === '_yes_' ? '_yes_' : '_no_';
    $dns = resellerHasFeature('custom_dns_records') && $dns === '_yes_'
        ? '_yes_' : '_no_';
    $backup = resellerHasFeature('backup')
        ? array_intersect($backup, ['_dmn_', '_sql_', '_mail_']) : [];
    $extMail = $extMail === '_yes_' ? '_yes_' : '_no_';
    $webFolderProtection = $webFolderProtection === '_yes_' ? '_yes_' : '_no_';

    $errFieldsStack = [];

    // Subdomains limit
    if (!resellerHasFeature('subdomains')) {
        $sub = '-1';
    } elseif (!imscp_limit_check($sub, -1)) {
        set_page_message(tohtml(tr('Incorrect subdomain limit.')), 'error');
        $errFieldsStack[] = 'nreseller_max_subdomain_cnt';
    }

    if (!resellerHasFeature('domain_aliases')) {
        $als = '-1';
    } elseif (!imscp_limit_check($als, -1)) {
        set_page_message(tohtml(tr('Incorrect alias limit.')), 'error');
        $errFieldsStack[] = 'nreseller_max_alias_cnt';
    }

    // Mail accounts limit
    if (!resellerHasFeature('mail')) {
        $mail = '-1';
    } elseif (!imscp_limit_check($mail, -1)) {
        set_page_message(
            tohtml(tr('Incorrect mail accounts limit.')), 'error'
        );
        $errFieldsStack[] = 'nreseller_max_mail_cnt';
    }

    // Mail quota limit
    if (!imscp_limit_check($mailQuota, NULL)) {
        set_page_message(tr('Incorrect mail quota.'), 'error');
        $errFieldsStack[] = 'nreseller_mail_quota';
    } elseif ($diskspace != '0' && $mailQuota > $diskspace) {
        set_page_message(
            tohtml(tr('Mail quota cannot be bigger than disk space limit.')),
            'error'
        );
        $errFieldsStack[] = 'nreseller_mail_quota';
    } elseif ($diskspace != '0' && $mailQuota == '0') {
        set_page_message(
            tohtml(tr(
                'Mail quota cannot be unlimited. Max value is %d MiB.',
                $diskspace
            )),
            'error'
        );
        $errFieldsStack[] = 'nreseller_mail_quota';
    }

    // Ftp accounts limit
    if (!resellerHasFeature('ftp')) {
        $ftp = '-1';
    } elseif (!imscp_limit_check($ftp, -1)) {
        set_page_message(tohtml(tr('Incorrect FTP accounts limit.')), 'error');
        $errFieldsStack[] = 'nreseller_max_ftp_cnt';
    }

    // SQL database limit
    if (!resellerHasFeature('sql_db')) {
        $sqld = -1;
    } elseif (!imscp_limit_check($sqld, -1)) {
        set_page_message(
            tohtml(tr('Incorrect SQL databases limit.')), 'error'
        );
        $errFieldsStack[] = 'nreseller_max_sql_db_cnt';
    } elseif ($sqld != -1 && $sqlu == -1) {
        set_page_message(tr('SQL users limit is disabled.'), 'error');
        $errFieldsStack[] = 'nreseller_max_sql_db_cnt';
        $errFieldsStack[] = 'nreseller_max_sql_user_cnt';
    }

    // SQL users limit
    if (!resellerHasFeature('sql_user')) {
        $sqlu = -1;
    } elseif (!imscp_limit_check($sqlu, -1)) {
        set_page_message(tohtml(tr('Incorrect SQL users limit.')), 'error');
        $errFieldsStack[] = 'nreseller_max_sql_user_cnt';
    } elseif ($sqlu != -1 && $sqld == -1) {
        set_page_message(
            tohtml(tr("SQL databases limit is disabled."), 'error')
        );
        $errFieldsStack[] = 'nreseller_max_sql_user_cnt';
        $errFieldsStack[] = 'nreseller_max_sql_db_cnt';
    }

    // Monthly traffic limit
    if (!imscp_limit_check($traffic, NULL)) {
        set_page_message(
            tohtml(tr('Incorrect monthly traffic limit.')), 'error'
        );
        $errFieldsStack[] = 'nreseller_max_traffic';
    }

    // Disk space limit
    if (!imscp_limit_check($diskspace, NULL)) {
        set_page_message(
            tohtml(tr('Incorrect disk space limit.')), 'error'
        );
        $errFieldsStack[] = 'nreseller_max_disk';
    }

    // PHP Editor feature
    $phpini = PhpEditor::getInstance();

    if (isset($_POST['php_ini_system']) && $php != '_no_'
        && $phpini->resellerHasPermission('phpiniSystem')
    ) {
        $phpini->setClientPermission(
            'phpiniSystem', clean_input($_POST['php_ini_system'])
        );

        if ($phpini->clientHasPermission('phpiniSystem')) {
            if (isset($_POST['phpini_perm_allow_url_fopen'])) {
                $phpini->setClientPermission(
                    'phpiniAllowUrlFopen',
                    clean_input($_POST['phpini_perm_allow_url_fopen'])
                );
            }

            if (isset($_POST['phpini_perm_display_errors'])) {
                $phpini->setClientPermission(
                    'phpiniDisplayErrors',
                    clean_input($_POST['phpini_perm_display_errors'])
                );
            }

            if (isset($_POST['phpini_perm_disable_functions'])) {
                $phpini->setClientPermission(
                    'phpiniDisableFunctions',
                    clean_input($_POST['phpini_perm_disable_functions'])
                );
            }

            if (isset($_POST['phpini_perm_mail_function'])) {
                $phpini->setClientPermission(
                    'phpiniMailFunction',
                    clean_input($_POST['phpini_perm_mail_function'])
                );
            }

            if (isset($_POST['memory_limit'])) {
                // Must be set before phpiniPostMaxSize
                $phpini->setDomainIni(
                    'phpiniMemoryLimit', clean_input($_POST['memory_limit'])
                );
            }

            if (isset($_POST['post_max_size'])) {
                // Must be set before phpiniUploadMaxFileSize
                $phpini->setDomainIni('phpiniPostMaxSize',
                    clean_input($_POST['post_max_size'])
                );
            }

            if (isset($_POST['upload_max_filesize'])) {
                $phpini->setDomainIni(
                    'phpiniUploadMaxFileSize',
                    clean_input($_POST['upload_max_filesize'])
                );
            }

            if (isset($_POST['max_execution_time'])) {
                $phpini->setDomainIni(
                    'phpiniMaxExecutionTime',
                    clean_input($_POST['max_execution_time'])
                );
            }

            if (isset($_POST['max_input_time'])) {
                $phpini->setDomainIni(
                    'phpiniMaxInputTime', clean_input($_POST['max_input_time'])
                );
            }
        }
    }

    if (!empty($errFieldsStack)) {
        Registry::set('errFieldsStack', $errFieldsStack);
        return false;
    }

    return true;
}

require 'imscp-lib.php';

check_login('reseller');
EventAggregator::getInstance()->dispatch(Events::onResellerScriptStart);

// Initialize global variables
global $dmnName, $hpId;
$hpName = 'Custom';
$sub = $als = $mail = $mailQuota = $ftp = $sqld = $sqlu = $traffic
    = $diskspace = '0';
$php = $cgi = $dns = $extMail = '_no_';
$webFolderProtection = '_yes_';
$backup = [];

if (!getFirstStepData()) {
    set_page_message(
        tohtml(tr('Domain data were altered. Please try again.')),
        'error'
    );
    unsetMessages();
    redirectTo('user_add1.php');
}

$phpini = PhpEditor::getInstance();
// Load reseller PHP permissions
$phpini->loadResellerPermissions($_SESSION['user_id']);
// Load client default PHP permissions
$phpini->loadClientPermissions();
// Load domain default PHP configuration options
$phpini->loadDomainIni();

if (isset($_POST['uaction'])
    && 'user_add2_nxt' == $_POST['uaction']
    && !isset($_SESSION['step_one'])
) {
    if (checkInputData()) {
        $_SESSION['step_two_data'] = "$dmnName;0";
        $_SESSION['ch_hpprops'] =
            "$php;$cgi;$sub;$als;$mail;$ftp;$sqld;$sqlu;$traffic;$diskspace;"
            . implode('|', $backup) . ";$dns;" .
            $phpini->getClientPermission('phpiniSystem') . ';' .
            $phpini->getClientPermission('phpiniAllowUrlFopen') . ';' .
            $phpini->getClientPermission('phpiniDisplayErrors') . ';' .
            $phpini->getClientPermission('phpiniDisableFunctions') . ';' .
            $phpini->getClientPermission('phpiniMailFunction') . ';' .
            $phpini->getDomainIni('phpiniPostMaxSize') . ';' .
            $phpini->getDomainIni('phpiniUploadMaxFileSize') . ';' .
            $phpini->getDomainIni('phpiniMaxExecutionTime') . ';' .
            $phpini->getDomainIni('phpiniMaxInputTime') . ';' .
            $phpini->getDomainIni('phpiniMemoryLimit') . ';' .
            $extMail . ';' . $webFolderProtection . ';' . $mailQuota * 1048576;

        if (reseller_limits_check(
            $_SESSION['user_id'], $_SESSION['ch_hpprops'])
        ) {
            redirectTo('user_add3.php');
        }
    }
} else {
    unset($_SESSION['step_one']);
    getHostingPlanData();
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'                             => 'shared/layouts/ui.tpl',
    'page'                               => 'reseller/user_add2.tpl',
    'page_message'                       => 'layout',
    'subdomain_feature'                  => 'page',
    'alias_feature'                      => 'page',
    'mail_feature'                       => 'page',
    'custom_dns_records_feature'         => 'page',
    'ext_mail_feature'                   => 'page',
    'ftp_feature'                        => 'page',
    'sql_feature'                        => 'page',
    'backup_feature'                     => 'page',
    'php_editor_block'                   => 'page',
    'php_editor_permissions_block'       => 'php_editor_block',
    'php_editor_allow_url_fopen_block'   => 'php_editor_permissions_block',
    'php_editor_display_errors_block'    => 'php_editor_permissions_block',
    'php_editor_disable_functions_block' => 'php_editor_permissions_block',
    "php_mail_function_block"            => 'php_editor_permissions_block',
    'php_editor_default_values_block'    => 'php_editor_block'
]);
$tpl->assign([
    'TR_PAGE_TITLE'                 => tohtml(tr('Reseller / Customers / Add Customer - Next Step')),
    'TR_HOSTING_PLAN'               => tohtml(tr('Hosting plan')),
    'TR_NAME'                       => tohtml(tr('Name')),
    'TR_MAX_SUBDOMAIN'              => tohtml(tr('Subdomains limit')) . '<br/><i>(-1 ' . tohtml(tr('disabled')) . ', 0 ∞)</i>',
    'TR_MAX_DOMAIN_ALIAS'           => tohtml(tr('Domain aliases limit')) . '<br/><i>(-1 ' . tohtml(tr('disabled')) . ', 0 ∞)</i>',
    'TR_MAX_MAIL_COUNT'             => tohtml(tr('Mail accounts limit')) . '<br/><i>(-1 ' . tohtml(tr('disabled')) . ', 0 ∞)</i>',
    'TR_MAIL_QUOTA'                 => tohtml(tr('Mail quota [MiB]')) . '<br/><i>(0 ∞)</i>',
    'TR_MAX_FTP'                    => tohtml(tr('FTP accounts limit')) . '<br/><i>(-1 ' . tohtml(tr('disabled')) . ', 0 ∞)</i>',
    'TR_MAX_SQL_DB'                 => tohtml(tr('SQL databases limit')) . '<br/><i>(-1 ' . tohtml(tr('disabled')) . ', 0 ∞)</i>',
    'TR_MAX_SQL_USERS'              => tohtml(tr('SQL users limit')) . '<br/><i>(-1 ' . tohtml(tr('disabled')) . ', 0 ∞)</i>',
    'TR_MAX_TRAFFIC'                => tohtml(tr('Monthly traffic limit [MiB]')) . '<br/><i>(0 ∞)</i>',
    'TR_MAX_DISK_USAGE'             => tohtml(tr('Disk space limit [MiB]')) . '<br/><i>(0 ∞)</i>',
    'TR_EXTMAIL'                    => tohtml(tr('External mail server')),
    'TR_PHP'                        => tohtml(tr('PHP')),
    'TR_CGI'                        => tohtml(tr('CGI')),
    'TR_BACKUP'                     => tohtml(tr('Backup')),
    'TR_BACKUP_DOMAIN'              => tohtml(tr('Domain')),
    'TR_BACKUP_SQL'                 => tohtml(tr('SQL')),
    'TR_BACKUP_MAIL'                => tohtml(tr('Mail')),
    'TR_DNS'                        => tohtml(tr('Custom DNS records')),
    'TR_YES'                        => tohtml(tr('yes')),
    'TR_NO'                         => tohtml(tr('no')),
    'TR_NEXT_STEP'                  => tohtml(tr('Next step')),
    'TR_FEATURES'                   => tohtml(tr('Features')),
    'TR_LIMITS'                     => tohtml(tr('Limits')),
    'TR_WEB_FOLDER_PROTECTION'      => tohtml(tr('Web folder protection')),
    'TR_WEB_FOLDER_PROTECTION_HELP' => tohtml(tr('If set to `yes`, Web folders will be protected against deletion.'), 'htmlAttr')
]);

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventAggregator::getInstance()->dispatch(
    Events::onResellerScriptEnd, ['templateEngine' => $tpl]
);
$tpl->prnt();
