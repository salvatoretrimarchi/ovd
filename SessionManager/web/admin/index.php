<?php
/**
 * Copyright (C) 2008 Ulteo SAS
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
require_once(dirname(__FILE__).'/includes/core.inc.php');
require_once(dirname(__FILE__).'/includes/page_template.php');

if (!file_exists(SESSIONMANAGER_CONFFILE_SERIALIZED)) {
	// TODO installation
	redirect('configuration.php?action=init');
}
$show_servers = isAuthorized('viewServers') || isAuthorized('manageServers');
$show_users = isAuthorized('viewUsers') || isAuthorized('manageUsers');
$show_usersgroups = isAuthorized('viewUsersGroups') || isAuthorized('manageUsersGroups');
$show_applications = isAuthorized('viewApplications') || isAuthorized('manageApplications');
$show_applicationsgroups = isAuthorized('viewApplicationsGroups') || isAuthorized('manageApplicationsGroups');
$show_publications = isAuthorized('viewPublications') || isAuthorized('managePublications');
$show_configuration = isAuthorized('viewConfiguration') || isAuthorized('manageConfiguration');
$show_status = isAuthorized('viewStatus');

page_header();
?>
<table style="width: 100%;" border="0" cellspacing="3" cellpadding="5">
	<tr>
		<td style="width: 30%; text-align: left; vertical-align: top;">
<div class="container rounded" style="background: #fff; width: 98%; margin-left: auto; margin-right: auto;">
<div>
	<h2>Users and Users groups</h2>

	<ul>
		<?php
			if ($show_users)
				echo '<li><a href="users.php">'._('User list').'</a></li>';
			if ($show_usersgroups)
				echo '<li><a href="usersgroup.php">'._('Users groups list').'</a></li>';
		?>
	</ul>
</div>
</div>
		</td>
		<td style="width: 20px;">
		</td>
		<td style="width: 30%; text-align: left; vertical-align: top;">
<div class="container rounded" style="background: #fff; width: 98%; margin-left: auto; margin-right: auto;">
<div>
	<h2>Servers</h2>
<?php
if ($show_servers) {
?>
	<ul>
		<li><a href="servers.php"><?php echo _('Servers list'); ?></a></li>
		<li><a href="servers.php?view=unregistered"><?php echo ('Unregistered servers list'); ?></a></li>
	</ul>
<?php
}
?>
</div>
</div>
		</td>
		<td style="width: 20px;">
		</td>
		<td style="padding-right: 20px; text-align: left; vertical-align: top;">
<div class="container rounded" style="background: #fff; width: 98%; margin-left: auto; margin-right: auto;">
<div>
	<h2>Configuration</h2>
<?php
if ($show_configuration) {
?>
	<ul>
		<li><a href="configuration-sumup.php"><?php echo _('General configuration'); ?></a></li>
	</ul>
<?php
}
?>
</div>
</div>
		</td>
	</tr>
	<tr>
		<td style="height: 10px;" colspan="5">
		</td>
	</tr>
	<tr>
		<td style="text-align: left; vertical-align: top;">
<div class="container rounded" style="background: #fff; width: 98%; margin-left: auto; margin-right: auto;">
<div>
	<h2>Applications and Appgroups</h2>

	<ul>
		<?php
		if ($show_applications)
			echo '<li><a href="applications.php">'._('Application list').'</a></li>';
		if ($show_applicationsgroups)
			echo '<li><a href="appsgroup.php">'._('Application groups list').'</a><br /><br /></li>';
		if ($show_publications) {
			echo '<li><a href="publications.php">'._('Publication list').'</a></li>';
			echo '<li><a href="wizard.php">'._('Publication wizard').'</a></li>';
		}
		?>
	</ul>
</div>
</div>
		</td>
		<td style="width: 20px;">
		</td>
		<td style="padding-right: 20px; text-align: left; vertical-align: top;" colspan="3">
<div class="container rounded" style="background: #fff; width: 99%; margin-left: auto; margin-right: auto;">
<div>
	<h2>Status</h2>
<?php
if ($show_status) {
	echo '<ul>';
	$active_sessions = Sessions::getAll();
	$count_active_sessions = count($active_sessions);

	$online_servers = Servers::getOnline();
	$count_online_servers = count($online_servers);
	$offline_servers = Servers::getOffline();
	$count_offline_servers = count($offline_servers);
	$broken_servers = Servers::getBroken();
	$count_broken_servers = count($broken_servers);

	echo '<li>';
	echo $count_active_sessions.' ';
	echo '<a href="sessions.php">';
	if ($count_active_sessions > 1)
		echo _('active sessions');
	else
		echo _('active session');
	echo '</a>';
	echo '</li>';

	echo '<li>';
	echo $count_online_servers.' ';
	echo '<a href="servers.php">';
	if ($count_online_servers > 1)
		echo _('online servers');
	else
		echo _('online server');
	echo '</a>';
	echo '</li>';

	echo '<li>';
	echo $count_offline_servers.' ';
	echo '<a href="servers.php">';
	if ($count_offline_servers > 1)
		echo _('offline servers');
	else
		echo _('offline server');
	echo '</a>';
	echo '</li>';

	echo '<li>';
	echo $count_broken_servers.' ';
	echo '<a href="servers.php">';
	if ($count_broken_servers > 1)
		echo _('broken servers');
	else
		echo _('broken server');
	echo '</a>';
	echo '</li>';
	echo '</ul>';
}
?>
</div>
</div>
		</td>
	</tr>
</table>
<?php
page_footer();
