#! /usr/bin/php
<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
 * Author Julien LANGLOIS <julien@ulteo.com>
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

require_once('/usr/share/ulteo/sessionmanager/admin/includes/core-minimal.inc.php');

function usage() {
  printf("usage: %s mysql_host mysql_db table_prefix mysql_login mysql_password\n", $_SERVER['argv'][0]);
}

if (count($_SERVER['argv']) != 6) {
  usage();
  exit(1);
}

$host = $_SERVER['argv'][1];
$db_name = $_SERVER['argv'][2];
$prefix = $_SERVER['argv'][3];
$user = $_SERVER['argv'][4];
$password = $_SERVER['argv'][5];

$ret = init($host, $db_name, $prefix, $user, $password);
if ($ret === False)
  exit(2);

exit(0);
