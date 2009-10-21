<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
 *
 * This program is free software; you can redistribute it and/or 
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/
require_once(dirname(__FILE__).'/../includes/core.inc.php');

Logger::debug('main', 'Starting webservices/server_log.php');

if (! isSessionManagerRequest()) {
	Logger::error('main', 'Request not coming from Session Manager');
	header('HTTP/1.1 400 Bad Request');
	die('ERROR - Request not coming from Session Manager');
}

if (! isset($_GET['type'])) {
	Logger::error('main', 'No log type requested');
	header('HTTP/1.1 400 Bad Request');
	die('ERROR - No log type requested');
}

if (isset($_REQUEST['nb_lines']) && is_numeric($_REQUEST['nb_lines'])) {
	if ($_GET['type'] == 'web')
		$log_content = shell_exec('tail -n '.$_REQUEST['nb_lines'].' '.APS_LOGS.'/main.log');
	elseif ($_GET['type'] == 'daemon')
		$log_content = shell_exec('tail -n '.$_REQUEST['nb_lines'].' '.CHROOT.'/var/log/ulteo-ovd.log');
} else {
	if ($_GET['type'] == 'web')
		$log_content = @file_get_contents(APS_LOGS.'/main.log');
	elseif ($_GET['type'] == 'daemon')
		$log_content = @file_get_contents(CHROOT.'/var/log/ulteo-ovd.log');
}

echo $log_content;

die();
