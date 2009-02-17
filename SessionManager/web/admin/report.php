<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Gauvain Pocentek <gauvain@ulteo.com>
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

require_once(dirname(__FILE__).'/includes/core.inc.php');
require_once('header.php');

if (! isset($_REQUEST['start']))
	$start = "";
else
	$start = $_REQUEST['start'];

if (! isset($_REQUEST['end']))
	$end = "";
else
	$end = $_REQUEST['end'];

if (! isset($_REQUEST['type']))
	$type = "";
else
	$type = $_REQUEST['type'];

$types_array = array('applications', 'servers');
$types_html = '';
foreach ($types_array as $k) {
	if ($type == $k)
		$selected = ' selected="selected"';
	else
		$selected = '';
	$types_html .= "  <option value=\"$k\"$selected>$k</option>\n";
}
?>

<form action="report.php" method="get">
  Report type:
  <select name="type">
  <?php echo $types_html; ?>
  </select>
  <br />
  From:  <input type="text" name="start" maxlength="8" value="<?php echo $start ?>" />
  To: <input type="text" name="end" maxlength="8" value="<?php echo $end ?>" />
  (YYYYMMDD)

<?php
if (isset($_REQUEST['type']) && is_file('report-'.$_REQUEST['type'].'.php')) {
	/* this is the computing part */
	include_once('report-'.$_REQUEST['type'].'.php');

	/* list available templates */
	print '  <br />';
	print '  Template: ';
	print '  <select name="template">';
	foreach (glob('templates/'.$type.'/*', GLOB_ONLYDIR) as $dir) {
		$item = basename($dir);
		if (isset($_REQUEST['template']) && ($_REQUEST['template'] == $item))
			$s = ' selected="selected"';
		else
			$s = '';
		print "    <option value=\"$item\"$s>$item</option>\n";
	}
	print '  </select>';
	print '  <br />';


	$tpl = 'templates/'.$type.'/default';
	if (isset($_REQUEST['template']) &&
	  is_dir('templates/'.$type.'/'.$_REQUEST['template']))
		$tpl = 'templates/'.$type.'/'.$_REQUEST['template'];
}
?>

  <input type="submit" value="Report" />
</form>
<hr />

<?php
if (isset($tpl)) {
	if (is_file($tpl.'/header.php'))
		include_once($tpl.'/header.php');

	$my_function = $type.'_per_server_data';
	foreach ($data as $fqdn => $server_data) {
		$server_data = $my_function($data, $fqdn);
		include_once($tpl.'/body.php');
	}

	if (is_file($tpl.'/footer.php'))
		include_once($tpl.'/footer.php');
}

require_once('footer.php');
