<?php
/**
 * Copyright (C) 2008-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2008-2012
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2014
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
require_once(dirname(dirname(__FILE__)).'/includes/core.inc.php');
require_once(dirname(dirname(__FILE__)).'/includes/page_template.php');

if (! checkAuthorization('viewServers'))
		redirect();


if (isset($_REQUEST['action'])) {
	if ($_REQUEST['action']=='manage') {
		if (isset($_REQUEST['id']))
			show_manage($_REQUEST['id']);
	}
}

show_default();

function show_manage($id) {
	$task = $_SESSION['service']->task_info($id);
	if (is_null($task)) {
		popup_error(sprintf(_('Unable to find task %s'), $id));
		redirect();
	}

	$infos = array();
	if ($task->hasAttribute('infos')) {
		$infos = $task->getAttribute('infos');
	}
	
	$packages = array();
	if ($task->hasAttribute('packages')) {
		$packages = $task->getAttribute('packages');
	}
	
	$server =  $_SESSION['service']->server_info($task->server);
	if (is_null($server)) {
		popup_error(sprintf(_('Unable to find server %s', $task->server)));
		redirect();
	}
	
	$can_remove = ($task->succeed() || $task->failed());

	$can_do_action = isAuthorized('manageServers');

	page_header();

	echo '<div id="tasks_div">';
	echo '<h1><a href="?">'._('Tasks managment').'</a> - '.$id.'</h1>';

	echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="5">';
	echo '<tr class="title">';
	echo '<th>'._('Creation time').'</th>';
	echo '<th>'._('Type').'</th>';
	echo '<th>'._('Servers').'</th>';
	echo '<th>'._('Status').'</th>';
	echo '<th>'._('Details').'</th>';
	echo '<th>'._('Job id').'</th>';
	if ($can_remove && $can_do_action)
    		echo '<th></th>';
	echo '</tr>';
	
	if ($task->succeed())
		$status = '<span class="msg_ok">'._('Finished').'</span>';
	elseif ($task->failed())
		$status = '<span class="msg_error">'._('Error').'</span>';
	else
		$status = $task->status;

	echo '<tr class="content1">';
	echo '<td>'.date('Y-m-d H:i:s', $task->t_begin).'</td>';
	echo '<td>'.get_class($task).'</td>';
	echo '<td><a href="servers.php?action=manage&id='.$task->server.'">'.$server->getDisplayName().'</a></td>';
	echo '<td>'.$status.'</td>';
	echo '<td>'.implode(', ', $packages).'</td>';
	echo '<td>'.$task->getAttribute('job_id').'</td>';
	if ($can_remove && $can_do_action) {
		echo '<td>';
		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="Task" />';
		echo '<input type="hidden" name="action" value="del" />';
		echo '<input type="hidden" name="checked_tasks[]" value="'.$task->id.'" />';
		echo '<input type="submit" value="'._('Delete').'" />';
		echo '</form>';
		echo '</td>';
	}
	echo '</tr>';
	echo '</table>';

	if ($task->hasAttribute('infos')) {
		foreach($task->getAttribute('infos') as $k => $v) {
			echo '<h3>'.$k.'</h3>';
			echo '<pre>'.$v.'</pre>';
		}
	}
	
	
	echo '</div>';


	page_footer();
	die();
}

function show_default() {
	$tasks = $_SESSION['service']->tasks_list();
	if (is_null($tasks)) {
		popup_error(_('Internal error requestings tasks'));
		redirect();
	}
	
	$servers_ = $_SESSION['service']->getOnlineServersList();
	if (is_null($servers_)) {
		$servers_ = array();
	}
	
	$servers = array();
	foreach($servers_ as $server) {
		if (isset($server->ulteo_system) && $server->ulteo_system == 1)
			$servers[]= $server;
	}

	$can_do_action = isAuthorized('manageServers');

  page_header();

  echo '<div id="tasks_div">';
  echo '<h1>'._('Tasks').'</h1>';

  if (count($tasks) > 0) {
    echo '<div id="tasks_list_div">';
    echo '<h2>'._('List of tasks').'</h2>';

    echo '<table class="main_sub sortable" id="tasks_list_table" border="0" cellspacing="1" cellpadding="5">';
    echo '<thead>';
    echo '<tr class="title">';
    echo '<th>'._('ID').'</th>';
    echo '<th>'._('Creation time').'</th>';
    echo '<th>'._('Type').'</th>';
    echo '<th>'._('Server').'</th>';
    echo '<th>'._('Status').'</th>';
    echo '<th>'._('Details').'</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    $count = 0;
    foreach($tasks as $task) {
      $content = 'content'.(($count++%2==0)?1:2);
	if (array_key_exists($task->server, $servers_)) {
		$server_name = $servers_[$task->server]->getDisplayName();
	}
	else {
		$server_name = $task->server;
	}
      
      $can_remove = ($task->succeed() || $task->failed());

      if ($task->succeed())
	      $status = '<span class="msg_ok">'._('Finished').'</span>';
      elseif ($task->failed())
	      $status = '<span class="msg_error">'._('Error').'</span>';
      elseif ($task->status == 'in progress')
	      $status = '<span class="msg_warn">'._('In progress').'</span>';
	else
		$status = $task->status;

      echo '<tr class="'.$content.'">';
      echo '<td><a href="?action=manage&id='.$task->id.'">'.$task->id.'</a></td>';
      echo '<td>'.date('Y-m-d H:i:s', $task->t_begin).'</td>';
      echo '<td>'.$task->getAttribute('type').'</td>';
      echo '<td><a href="servers.php?action=manage&id='.$task->server.'">'.$server_name.'</a></td>';
      echo '<td>'.$status.'</td>';
      echo '<td>'.$task->getAttribute('request').'</td>'; // todo !!!
      if ($can_do_action) {
		echo '<td>';
		if ($can_remove) {
			echo '<form action="actions.php" method="post">';
			echo '<input type="hidden" name="name" value="Task" />';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="checked_tasks[]" value="'.$task->id.'" />';
			echo '<input type="submit" value="'._('Delete').'" />';
			echo '</form>';
		}
		echo '</td>';
      }
      echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
  }
  
  $can_do_action = False;
  
    if (count($servers)>0 && $can_do_action) {
    	echo '<h2>'._('Install an application from a package name').'</h2>';

    	echo '<form action="actions.php" method="post">';
	echo '<input type="hidden" name="name" value="Task" />';
	echo '<input type="hidden" name="action" value="add" />';
    	echo '<select name="server">';
    	foreach ($servers as $server)
		echo '<option value="'.$server->id.'">'.$server->getDisplayName().'</option>';
    	echo '</select> &nbsp; ';
    	echo '<input type="text" name="request" value="" /> &nbsp; ';
    	echo '<input type="hidden" name="type" value="install_from_line" />';
    	echo '<input type="submit" name="submit" value="'._('Install').'" />';
    	echo '</form>';

        echo '<h2>'._('Upgrade the internal system and applications').'</h2>';

        echo '<form action="actions.php" method="post">';
        echo '<input type="hidden" name="name" value="Task" />';
        echo '<input type="hidden" name="action" value="add" />';
        echo '<input type="hidden" name="type" value="upgrade" />';
        echo '<input type="hidden" name="request" value="" />'; // hack for the task creation
        echo '<select name="server">';
        foreach ($servers as $server)
            echo '<option value="'.$server->id.'">'.$server->getDisplayName().'</option>';
        echo '</select> &nbsp; ';
        echo '<input type="submit" name="submit" value="'._('Upgrade').'" />';
        echo '</form>';
    }

    echo '</div>';
    page_footer();
    die();
}
