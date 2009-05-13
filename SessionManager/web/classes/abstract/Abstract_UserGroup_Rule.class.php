<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
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
require_once(dirname(__FILE__).'/../../includes/core.inc.php');

class Abstract_UserGroup_Rule {
	public static function init($prefs_) {
		Logger::debug('main', 'Starting Abstract_UserGroup_Rule::init');

		$mysql_conf = $prefs_->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$invites_table_structure = array(
			'id'			=>	'int(8) NOT NULL auto_increment',
			'attribute'		=>	'varchar(255) NOT NULL',
			'type'			=>	'varchar(255) NOT NULL',
			'value'			=>	'varchar(255) NOT NULL'
		);

		$ret = $SQL->buildTable($mysql_conf['prefix'].'usergroup_rules', $invites_table_structure, array('id'));

		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.$mysql_conf['prefix'].'usergroup_rules\'');
			return false;
		}

		Logger::debug('main', 'MySQL table \''.$mysql_conf['prefix'].'usergroup_rules\' created');
		return true;
	}

	public static function load($id_) {
		Logger::debug('main', 'Starting Abstract_UserGroup_Rule::load for \''.$id_.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$id = $id_;

		$SQL->DoQuery('SELECT @1,@2,@3 FROM @4 WHERE @5 = %6 LIMIT 1', 'attribute', 'type', 'value', $mysql_conf['prefix'].'usergroup_rules', 'id', $id);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$row = $SQL->FetchResult();

		foreach ($row as $k => $v)
			$$k = $v;

		$buf = new UserGroup_Rule($id);
		$buf->attribute = (string)$attribute;
		$buf->type = (string)$type;
		$buf->value = (string)$value;

		return $buf;
	}

	public static function save($usergroup_rule_) {
		Logger::debug('main', 'Starting Abstract_UserGroup_Rule::save for \''.$usergroup_rule_->id.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		if (! Abstract_UserGroup_Rule::exists($usergroup_rule_->attribute, $usergroup_rule_->type, $usergroup_rule_->value)) {
			$buf = Abstract_UserGroup_Rule::create($usergroup_rule_);

			if (! $buf)
				return false;

			$usergroup_rule_->id = $buf;
		}

		if (is_null($usergroup_rule_->id))
			return false;

		$SQL->DoQuery('UPDATE @1 SET @2=%3,@4=%5,@6=%7 WHERE @8 = %9 LIMIT 1', $mysql_conf['prefix'].'usergroup_rules', 'attribute', $usergroup_rule_->attribute, 'type', $usergroup_rule_->type, 'value', $usergroup_rule_->value, 'id', $usergroup_rule_->id);

		return true;
	}

	private static function create($usergroup_rule_) {
		Logger::debug('main', 'Starting Abstract_UserGroup_Rule::create for \''.$usergroup_rule_->id.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$id = $usergroup_rule_->id;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $mysql_conf['prefix'].'usergroup_rules', 'id', $id);
		$total = $SQL->NumRows();

		if ($total != 0)
			return false;

		$SQL->DoQuery('INSERT INTO @1 (@2) VALUES (%3)', $mysql_conf['prefix'].'usergroup_rules', 'id', '');

		return $SQL->InsertId();
	}

	public static function delete($id_) {
		Logger::debug('main', 'Starting Abstract_UserGroup_Rule::delete for \''.$id_.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$id = $id_;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $mysql_conf['prefix'].'usergroup_rules', 'id', $id);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3 LIMIT 1', $mysql_conf['prefix'].'usergroup_rules', 'id', $id);

		return true;
	}

	public static function exists($attribute_, $type_, $value_) {
		Logger::debug('main', 'Starting Abstract_UserGroup_Rule::exists with \''.$attribute_.'\' \''.$type_.'\' \''.$value_.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$id = $id_;

		$SQL->DoQuery('SELECT @1 FROM @2 WHERE @3 = %4 AND @5 = %6 AND @7 = %8 LIMIT 1', 'id', $mysql_conf['prefix'].'usergroup_rules', 'attribute', $attribute_, 'type', $type_, 'value', $value_);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$row = $SQL->FetchResult();
		return $row['id'];
	}
}
