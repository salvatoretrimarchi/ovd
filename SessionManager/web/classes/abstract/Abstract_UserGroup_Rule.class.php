<?php
/**
 * Copyright (C) 2009-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2009
 * Author Jocelyn DELALANDE <j.delalande@ulteo.com> 2012
 * Author Julien LANGLOIS <julien@ulteo.com> 2012, 2013
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
require_once(dirname(__FILE__).'/../../includes/core.inc.php');

/**
 * Abstraction layer between the UserGroup_Rule instances and the SQL backend.
 */
class Abstract_UserGroup_Rule {
	const table = 'usergroup_rules';
	
	public static function init($prefs_) {
		Logger::debug('main', 'Starting Abstract_UserGroup_Rule::init');

		$sql_conf = $prefs_->get('general', 'sql');
		$SQL = SQL::newInstance($sql_conf);

		$usergroup_rules_table_structure = array(
			'id'			=>	'int(8) NOT NULL auto_increment',
			'attribute'		=>	'varchar(255)',
			'type'			=>	'varchar(255)',
			'value'			=>	'varchar(255)',
			'usergroup_id'	=>	'varchar(255)'
		);

		$ret = $SQL->buildTable(self::table, $usergroup_rules_table_structure, array('id'));

		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.self::table.'\'');
			return false;
		}

		Logger::debug('main', 'MySQL table \''.self::table.'\' created');
		return true;
	}

	public static function exists($attribute_, $type_, $value_, $usergroup_id_) {
		Logger::debug('main', 'Starting Abstract_UserGroup_Rule::exists with attribute \''.$attribute_.'\' type \''.$type_.'\' value \''.$value_.'\' usergroup_id \''.$usergroup_id_.'\'');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT @1 FROM #2 WHERE @3 = %4 AND @5 = %6 AND @7 = %8 AND @9 = %10 LIMIT 1', 'id', self::table, 'attribute', $attribute_, 'type', $type_, 'value', $value_, 'usergroup_id', $usergroup_id_);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$row = $SQL->FetchResult();
		return $row['id'];
	}

	public static function load($id_) {
		Logger::debug('main', 'Starting Abstract_UserGroup_Rule::load for \''.$id_.'\'');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT * FROM #1 WHERE @2 = %3 LIMIT 1', self::table, 'id', $id_);
		$total = $SQL->NumRows();

		if ($total == 0) {
			Logger::error('main', "Abstract_UserGroup_Rule::load($id_) failed: NumRows == 0");
			return false;
		}

		$row = $SQL->FetchResult();

		$buf = self::generateFromRow($row);

		return $buf;
	}

	public static function save($usergroup_rule_) {
		Logger::debug('main', 'Starting Abstract_UserGroup_Rule::save for (attribute: \''.$usergroup_rule_->attribute.'\', type: \''.$usergroup_rule_->type.'\', value: \''.$usergroup_rule_->value.'\',	usergroup_id: \''.$usergroup_rule_->usergroup_id.'\')');

		$SQL = SQL::getInstance();

		$rule_id = Abstract_UserGroup_Rule::exists($usergroup_rule_->attribute, $usergroup_rule_->type, $usergroup_rule_->value, $usergroup_rule_->usergroup_id);
		if (! $rule_id) {
			$buf = Abstract_UserGroup_Rule::create($usergroup_rule_);

			if ($buf === false) {
				Logger::error('main', 'Abstract_UserGroup_Rule::save failed to create rule');
				return false;
			}

			$usergroup_rule_->id = $buf;
		} else {
			Logger::debug('main', 'Abstract_UserGroup_Rule::save rule('.$usergroup_rule_->attribute.','.$usergroup_rule_->type.','.$usergroup_rule_->value.','.$usergroup_rule_->usergroup_id.') already exists');

			$usergroup_rule_->id = $rule_id;

			return true;
		}

		if (is_null($usergroup_rule_->id)) {
			Logger::error('main', 'Abstract_UserGroup_Rule::save rule\'s id is null');
			return false;
		}

		$SQL->DoQuery('UPDATE #1 SET @2=%3,@4=%5,@6=%7,@8=%9 WHERE @10 = %11 LIMIT 1', self::table, 'attribute', $usergroup_rule_->attribute, 'type', $usergroup_rule_->type, 'value', $usergroup_rule_->value, 'usergroup_id', $usergroup_rule_->usergroup_id, 'id', $usergroup_rule_->id);

		return true;
	}

	private static function create($usergroup_rule_) {
		Logger::debug('main', 'Starting Abstract_UserGroup_Rule::create for (attribute: \''.$usergroup_rule_->attribute.'\', type: \''.$usergroup_rule_->type.'\', value: \''.$usergroup_rule_->value.'\',	usergroup_id: \''.$usergroup_rule_->usergroup_id.'\')');

		$SQL = SQL::getInstance();

		$id = $usergroup_rule_->id;

		$SQL->DoQuery('SELECT 1 FROM #1 WHERE @2 = %3 LIMIT 1', self::table, 'id', $id);
		$total = $SQL->NumRows();

		if ($total != 0) {
			Logger::error('main', 'Abstract_UserGroup_Rule::create rule id \''.$id.'\' already exists');
			return false;
		}

		$SQL->DoQuery('INSERT INTO #1 (@2) VALUES (%3)', self::table, 'id', '');

		return $SQL->InsertId();
	}

	public static function delete($id_) {
		Logger::debug('main', 'Starting Abstract_UserGroup_Rule::delete for \''.$id_.'\'');

		$SQL = SQL::getInstance();

		$id = $id_;

		$SQL->DoQuery('SELECT 1 FROM #1 WHERE @2 = %3 LIMIT 1', self::table, 'id', $id);
		$total = $SQL->NumRows();

		if ($total == 0) {
			Logger::error('main', "Abstract_UserGroup_Rule::delete($id_) rule does not exist (NumRows == 0)");
			return false;
		}

		$SQL->DoQuery('DELETE FROM #1 WHERE @2 = %3 LIMIT 1', self::table, 'id', $id);

		return true;
	}

	private static function generateFromRow($row_) {
		foreach ($row_ as $k => $v)
			$$k = $v;

		$buf = new UserGroup_Rule((int)$id);
		$buf->attribute = (string)$attribute;
		$buf->type = (string)$type;
		$buf->value = (string)$value;
		$buf->usergroup_id = (string)$usergroup_id;

		return $buf;
	}

	public static function load_all() {
		Logger::debug('main', 'Starting Abstract_UserGroup_Rule::load_all');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT * FROM #1', self::table);

		$rows = $SQL->FetchAllResults();

		$usergroup_rules = array();
		foreach ($rows as $row) {
			$usergroup_rule = self::generateFromRow($row);
			if (! is_object($usergroup_rule))
				continue;

			$usergroup_rules[] = $usergroup_rule;
		}

		return $usergroup_rules;
	}

	public static function loadByUserGroupId($usergroup_id_) {
		Logger::debug('main', "Abstract_UserGroup_Rule::loadByUserGroupId($usergroup_id_)");

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT @1,@2,@3,@4,@5 FROM #6 WHERE @5 = %7', 'id', 'attribute', 'type', 'value', 'usergroup_id', self::table, $usergroup_id_);

		$rows = $SQL->FetchAllResults();

		$usergroup_rules = array();
		foreach ($rows as $row) {
			$usergroup_rule = self::generateFromRow($row);
			if (! is_object($usergroup_rule))
				continue;

			$usergroup_rules[] = $usergroup_rule;
		}

		return $usergroup_rules;
	}
	
	public static function deleteByUserGroupIds($group_ids_) {
		if (! is_array($group_ids_) || count($group_ids_) == 0) {
			return false;
		}
		
		$sql = SQL::getInstance();
		
		$groups2 = array();
		foreach($groups2 as $id) {
			array_push($groups2, '"'.$sql->CleanValue($id).'"');
		}
		
		return $sql->DoQuery('DELETE FROM #1 WHERE @2 IN ('.implode(', ',$groups2).')', self::table, 'usergroup_id');
	}

	public static function delete_all() {
		$sql = SQL::getInstance();
		return $sql->DoQuery('DELETE FROM #1', self::table);
	}
	
	
	public static function get_usersgroups() {
		$sql = SQL::getInstance();
		$res = $sql->DoQuery('SELECT COUNT(*) FROM #1 GROUP BY @2', self::table, 'usergroup_id');
		if ($res !== true) {
			Logger::error('main', "Abstract_UserGroup_Rule::get_usersgroups sql request failed");
			return array();
		}
		
		$usersgroups = array();
		$rows = $sql->FetchArrayAll();
		foreach ($rows as $row) {
			array_push($usersgroups, $row['usergroup_id']);
		}
		
		return $usersgroups;
	}
}
