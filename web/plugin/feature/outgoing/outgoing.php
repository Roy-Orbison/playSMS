<?php

/**
 * This file is part of playSMS.
 *
 * playSMS is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * playSMS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with playSMS. If not, see <http://www.gnu.org/licenses/>.
 */
defined('_SECURE_') or die('Forbidden');

if (!auth_isadmin()) {
	auth_block();
}

switch (_OP_) {
	case "outgoing_list":
		$tpl = [
			'name' => 'outgoing_list',
			'vars' => [
				'DIALOG_DISPLAY' => _dialog(),
				'Route outgoing SMS' => _('Route outgoing SMS'),
				'Add route' => _button('index.php?app=main&inc=feature_outgoing&op=outgoing_add', _('Add route')),
				'User' => _('User'),
				'Prefix' => _('Prefix'),
				'SMSC' => _('SMSC'),
				'Destination name' => _('Destination name'),
				'Action' => _('Action'),
				'option'
			],
		];

		$db_query = "
			SELECT A.*, B.username 
			FROM " . _DB_PREF_ . "_featureOutgoing AS A LEFT JOIN " . _DB_PREF_ . "_tblUser AS B 
			ON B.flag_deleted='0' AND A.uid=B.uid ORDER BY username, smsc, prefix";
		$db_result = dba_query($db_query);
		while ($db_row = dba_fetch_array($db_result)) {
			$db_row = _display($db_row);
			$c_rid = $db_row['id'];
			$c_action = "<a href='" . _u('index.php?app=main&inc=feature_outgoing&op=outgoing_edit&rid=' . $c_rid) . "'>" . $icon_config['edit'] . "</a> ";
			$c_action .= "<a href='javascript: ConfirmURL(\"" . _('Are you sure ?') . "\", \"" . _u('index.php?app=main&inc=feature_outgoing&op=outgoing_del&rid=' . $c_rid) . "\")'>" . $icon_config['delete'] . "</a> ";
			$tpl['loops']['data'][] = [
				'tr_class' => $tr_class,
				'username' => $db_row['username'] ? $db_row['username'] : '*',
				'prefix' => outgoing_prefix_format($db_row['prefix']),
				'smsc' => $db_row['smsc'] ? $db_row['smsc'] : _('blocked'),
				'dst' => $db_row['dst'],
				'action' => $c_action,
			];
		}

		$content = tpl_apply($tpl);
		_p($content);
		break;

	case "outgoing_del":
		$rid = $_REQUEST['rid'];
		$dst = outgoing_getdst($rid);
		$prefix = outgoing_getprefix($rid);
		$db_query = "DELETE FROM " . _DB_PREF_ . "_featureOutgoing WHERE id=?";
		if (dba_affected_rows($db_query, [$rid])) {
			$_SESSION['dialog']['info'][] = _('Route has been deleted') . " (" . _('destination') . ": $dst, " . _('prefix') . ": $prefix)";
		} else {
			$_SESSION['dialog']['danger'][] = _('Fail to delete route') . " (" . _('destination') . ": $dst, " . _('prefix') . ": $prefix)";
		}
		header("Location: " . _u('index.php?app=main&inc=feature_outgoing&op=outgoing_list'));
		exit();

	case "outgoing_edit":
		$rid = $_REQUEST['rid'];
		$uid = outgoing_getuid($rid);
		$select_users = themes_select_users_single('up_uid', $uid);
		$dst = outgoing_getdst($rid);
		$prefix = outgoing_getprefix($rid);
		$smsc = outgoing_getsmsc($rid);
		$select_smsc = "<select name=up_smsc>";
		unset($smsc_list);
		$list = gateway_getall_smsc();
		foreach ( $list as $c_smsc ) {
			$smsc_list[] = $c_smsc['name'];
		}
		foreach ( $smsc_list as $smsc_name ) {
			$selected = $smsc_name == $smsc ? "selected" : "";
			$select_smsc .= "<option " . $selected . ">" . $smsc_name . "</option>";
		}
		$select_smsc .= "</select>";
		$content = _dialog() . "
			<h2>" . _('Route SMS outgoing') . "</h2>
			<h3>" . _('Edit route') . "</h3>
			<form action='index.php?app=main&inc=feature_outgoing&op=outgoing_edit_save' method='post'>
			" . _CSRF_FORM_ . "
			<input type='hidden' name='rid' value=\"$rid\">
			<table class=playsms-table>
			<tr>
				<td class=label-sizer>" . _('User') . "</td><td>" . $select_users . "</td>
			</tr>
			<tr>
				<td>" . _mandatory(_('Destination name')) . "</td><td><input type='text' maxlength='30' name='up_dst' value=\"$dst\" required></td>
			</tr>
			<tr>
				<td>" . _mandatory(_('Prefix')) . "</td><td><input type='text' name='up_prefix' value=\"$prefix\" required> " . _hint(_('Multiple entries separated by comma')) . "</td>
			</tr>
			<tr>
				<td>" . _('SMSC') . "</td><td>" . $select_smsc . "</td>
			</tr>
			</table>
			<p><input type='submit' class='button' value='" . _('Save') . "'></p>
			</form>
			" . _back('index.php?app=main&inc=feature_outgoing&op=outgoing_list');
		_p($content);
		break;

	case "outgoing_edit_save":
		$rid = $_POST['rid'];

		$up_uid = $_REQUEST['up_uid'];
		if ($up_uid) {
			$up_username = user_uid2username($up_uid);
			if (!$up_username) {
				$up_uid = 0;
			}
		}
		$up_dst = $_POST['up_dst'];

		// sanitize prefixes
		$up_prefix = outgoing_prefix_format($_POST['up_prefix']);

		$up_smsc = ($_POST['up_smsc'] ? $_POST['up_smsc'] : 'blocked');
		if ($rid && $up_dst) {
			$db_query = "UPDATE " . _DB_PREF_ . "_featureOutgoing SET c_timestamp='" . time() . "',uid='$up_uid',dst='$up_dst',prefix='$up_prefix',smsc='$up_smsc' WHERE id='$rid'";
			if (@dba_affected_rows($db_query)) {
				$_SESSION['dialog']['info'][] = _('Route has been saved') . " (" . _('destination') . ": $up_dst, " . _('prefix') . ": " . $up_prefix . ")";
			} else {
				$_SESSION['dialog']['danger'][] = _('Fail to save route') . " (" . _('destination') . ": $up_dst, " . _('prefix') . ": " . $up_prefix . ")";
			}
		} else {
			$_SESSION['dialog']['danger'][] = _('You must fill all mandatory fields');
		}
		header("Location: " . _u('index.php?app=main&inc=feature_outgoing&op=outgoing_edit&rid=' . $rid));
		exit();

	case "outgoing_add":
		$select_users = themes_select_users_single('add_uid');
		$select_smsc = "<select name=add_smsc>";
		unset($smsc_list);
		$list = gateway_getall_smsc();
		foreach ( $list as $c_smsc ) {
			$smsc_list[] = $c_smsc['name'];
		}
		foreach ( $smsc_list as $smsc_name ) {
			$select_smsc .= "<option>" . $smsc_name . "</option>";
		}
		$select_smsc .= "</select>";
		$content = _dialog() . "
			<h2>" . _('Route outgoing SMS') . "</h2>
			<h3>" . _('Add route') . "</h3>
			<form action='index.php?app=main&inc=feature_outgoing&op=outgoing_add_yes' method='post'>
			" . _CSRF_FORM_ . "
			<table class=playsms-table>
			<tr>
				<td class=label-sizer>" . _('User') . "</td><td>" . $select_users . "</td>
			</tr>
			<tr>
				<td>" . _mandatory(_('Destination name')) . "</td><td><input type='text' maxlength='30' name='add_dst' value=\"$add_dst\" required></td>
			</tr>
			<tr>
				<td>" . _mandatory(_('Prefix')) . "</td><td><input type='text' name='add_prefix' value=\"$add_prefix\" required> " . _hint(_('Multiple entries separated by comma')) . "</td>
			</tr>
			<tr>
				<td>" . _('SMSC') . "</td><td>" . $select_smsc . "</td>
			</tr>
			</table>
			<input type='submit' class='button' value='" . _('Save') . "'>
			</form>
			" . _back('index.php?app=main&inc=feature_outgoing&op=outgoing_list');
		_p($content);
		break;

	case "outgoing_add_yes":
		$add_uid = $_REQUEST['add_uid'];
		if ($add_uid) {
			$add_username = user_uid2username($add_uid);
			if (!$add_username) {
				$add_uid = 0;
			}
		}

		$add_dst = $_POST['add_dst'];

		// sanitize prefixes
		$add_prefix = outgoing_prefix_format($_POST['add_prefix']);

		$add_smsc = ($_POST['add_smsc'] ? $_POST['add_smsc'] : 'blocked');
		if ($add_dst) {
			$db_query = "
					INSERT INTO " . _DB_PREF_ . "_featureOutgoing (uid,dst,prefix,smsc)
					VALUES ('$add_uid','$add_dst','$add_prefix','$add_smsc')";
			if ($new_uid = @dba_insert_id($db_query)) {
				$_SESSION['dialog']['info'][] = _('Route has been added') . " (" . _('destination') . ": $add_dst, " . _('prefix') . ": " . $add_prefix . ")";
			} else {
				$_SESSION['dialog']['danger'][] = _('Fail to add route') . " (" . _('destination') . ": $add_dst, " . _('prefix') . ": " . $add_prefix . ")";
			}
		} else {
			$_SESSION['dialog']['danger'][] = _('You must fill all fields');
		}
		header("Location: " . _u('index.php?app=main&inc=feature_outgoing&op=outgoing_add'));
		exit();
}
