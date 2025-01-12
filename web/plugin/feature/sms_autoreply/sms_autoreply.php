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

if (!auth_isvalid()) {
	auth_block();
}

$autoreply_id = (int) $_REQUEST['autoreply_id'];

if ($autoreply_id && !sms_autoreply_check_id($autoreply_id)) {
	auth_block();
}

switch (_OP_) {
	case "sms_autoreply_list":
		$content = _dialog() . "
			<h2>" . _('Manage autoreply') . "</h2>
			" . _button('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_add', _('Add SMS autoreply'));
		$content .= "
			<div class=table-responsive>
			<table class=playsms-table-list>
			<thead><tr>";
		if (auth_isadmin()) {
			$content .= "
				<th width=70%>" . _('Keyword') . "</th>
				<th width=20%>" . _('User') . "</th>
				<th width=10%>" . _('Action') . "</th>";
		} else {
			$content .= "
				<th width=90%>" . _('Keyword') . "</th>
				<th width=10%>" . _('Action') . "</th>";
		}
		$content .= "</tr></thead><tbody>";
		if (!auth_isadmin()) {
			$query_user_only = "WHERE uid='" . $user_config['uid'] . "'";
		}
		$db_query = "SELECT * FROM " . _DB_PREF_ . "_featureAutoreply " . $query_user_only . " ORDER BY autoreply_keyword";
		$db_result = dba_query($db_query);
		$i = 0;
		while ($db_row = dba_fetch_array($db_result)) {
			$db_row = _display($db_row);
			if ($owner = user_uid2username($db_row['uid'])) {
				$action = "<a href=\"" . _u('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_manage&autoreply_id=' . $db_row['autoreply_id']) . "\">" . $icon_config['manage'] . "</a>&nbsp;";
				$action .= "<a href=\"" . _u('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_edit&autoreply_id=' . $db_row['autoreply_id']) . "\">" . $icon_config['edit'] . "</a>&nbsp;";
				$action .= "<a href=\"javascript: ConfirmURL('" . _('Are you sure you want to delete SMS autoreply ?') . " (" . _('keyword') . ": " . $db_row['autoreply_keyword'] . ")','" . _u('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_del&autoreply_id=' . $db_row['autoreply_id']) . "')\">" . $icon_config['delete'] . "</a>";
				if (auth_isadmin()) {
					$option_owner = "<td>$owner</td>";
				}
				$i++;
				$content .= "
					<tr>
						<td>" . $db_row['autoreply_keyword'] . "</td>
						" . $option_owner . "
						<td>$action</td>
					</tr>";
			}
		}
		$content .= "
			</tbody>
			</table>
			</div>
			<p>" . _button('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_add', _('Add SMS autoreply'));
		_p($content);
		break;

	case "sms_autoreply_manage":
		$db_query = "SELECT * FROM " . _DB_PREF_ . "_featureAutoreply WHERE autoreply_id=?";
		$db_result = dba_query($db_query, [$autoreply_id]);
		if (!$db_row) {
			$_SESSION['dialog']['danger'][] = _('Unknown error cannot find the data in database');
			header("Location: " . _u('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_list'));
			exit();
		}
		$db_row = _display($db_row);

		$manage_autoreply_keyword = $db_row['autoreply_keyword'];
		$owner_uid = $db_row['uid'];
		$content = _dialog() . "
			<h2>" . _('Manage autoreply') . "</h2>
			<p>" . _('SMS autoreply keyword') . ": " . $manage_autoreply_keyword . "</p>
			<p>" . _button('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_scenario_add&autoreply_id=' . $autoreply_id, _('Add SMS autoreply scenario')) . "</p>
			<div class=table-responsive>
			<table class=playsms-table-list>
			<thead><tr>";
		if (auth_isadmin()) {
			$content .= "
				<th width=20%>" . _('SMS') . " " . _hint(_('SMS is case-insensitive')) . "</th>
				<th width=50%>" . _('Reply') . "</th>
				<th width=20%>" . _('User') . "</th>
				<th width=10%>" . _('Action') . "</th>";
		} else {
			$content .= "
				<th width=20%>" . _('SMS') . " " . _hint(_('SMS is case-insensitive')) . "</th>
				<th width=70%>" . _('Reply') . "</th>
				<th width=10%>" . _('Action') . "</th>";
		}
		$content .= "</tr></thead><tbody>";
		$db_query = "SELECT * FROM " . _DB_PREF_ . "_featureAutoreply_scenario WHERE autoreply_id=? ORDER BY autoreply_scenario_param1";
		$db_result = dba_query($db_query, [$autoreply_id]);
		$j = 0;
		while ($db_row = dba_fetch_array($db_result)) {
			if ($owner = user_uid2username($owner_uid)) {
				$db_row = _display($db_row);
				$list_of_param = "";
				for ($i = 1; $i <= 7; $i++) {
					$list_of_param .= $db_row['autoreply_scenario_param' . $i] . "&nbsp;";
				}
				$action = "<a href=\"" . _u('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_scenario_edit&autoreply_id=' . $autoreply_id . '&autoreply_scenario_id=' . $db_row['autoreply_scenario_id']) . "\">" . $icon_config['edit'] . "</a>";
				$action .= "<a href=\"javascript: ConfirmURL('" . _('Are you sure you want to delete this SMS autoreply scenario ?') . "','" . _u('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_scenario_del&autoreply_id=' . $autoreply_id . '&autoreply_scenario_id=' . $db_row['autoreply_scenario_id']) . "')\">" . $icon_config['delete'] . "</a>";
				if (auth_isadmin()) {
					$option_owner = "<td>" . $owner . "</td>";
				}
				$j++;
				$content .= "
					<tr>
						<td>" . $manage_autoreply_keyword . " " . $list_of_param . "</td>
						<td align=left>" . $db_row['autoreply_scenario_result'] . "</td>
						" . $option_owner . "
						<td>" . $action . "</td>
					</tr>";
			}
		}
		$content .= "
			</tbody>
			</table>
			</div>
			</form>
			<p>" . _button('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_scenario_add&autoreply_id=' . $autoreply_id, _('Add SMS autoreply scenario')) . "
			<p>" . _back('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_list');
		_p($content);
		break;

	case "sms_autoreply_del":
		$db_query = "SELECT autoreply_keyword FROM " . _DB_PREF_ . "_featureAutoreply WHERE autoreply_id=?";
		$db_result = dba_query($db_query, [$autoreply_id]);
		if ($db_row = dba_fetch_array($db_result)) {
			if ($keyword_name = $db_row['autoreply_keyword']) {
				$db_query = "DELETE FROM " . _DB_PREF_ . "_featureAutoreply WHERE autoreply_keyword=?";
				if (dba_affected_rows($db_query, [$keyword_name])) {
					$db_query = "DELETE FROM " . _DB_PREF_ . "_featureAutoreply_scenario WHERE autoreply_id=?";
					if (dba_affected_rows($db_query, [$autoreply_id])) {
						$_SESSION['dialog']['info'][] = _('SMS autoreply has been deleted') . " (" . _('keyword') . ": $keyword_name)";
					} else {
						$_SESSION['dialog']['danger'][] = _('Fail to delete SMS autoreply scenario') . " (" . _('keyword') . ": $keyword_name";
					}
				} else {
					$_SESSION['dialog']['danger'][] = _('Fail to delete SMS autoreply') . " (" . _('keyword') . ": $keyword_name";
				}
			} else {
				$_SESSION['dialog']['danger'][] = _('Unknown error no keyword found');
			}
		} else {
			$_SESSION['dialog']['danger'][] = _('Unknown error no data found on database');
		}
		header("Location: " . _u('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_list'));
		exit();

	case "sms_autoreply_add":
		$select_reply_smsc = '';
		if (auth_isadmin()) {
			$select_reply_smsc = "<tr><td>" . _('SMSC') . "</td><td>" . gateway_select_smsc('smsc') . "</td></tr>";
		}

		$content = _dialog() . "
			<h2>" . _('Manage autoreply') . "</h2>
			<h3>" . _('Add SMS autoreply') . "</h3>
			<form action=index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_add_yes method=post>
			" . _CSRF_FORM_ . "
			<table class=playsms-table>
				<tbody>
				<tr>
					<td class=label-sizer>" . _mandatory(_('SMS autoreply keyword')) . "</td>
					<td><input type=text size=10 maxlength=10 name=add_autoreply_keyword value=\"\"></td>
				</tr>
				" . $select_reply_smsc . "
				</tbody>
			</table>
			<p><input type=submit class=button value='" . _('Save') . "'></p>
			</form>
			<p>" . _back('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_list');
		_p($content);
		break;

	case "sms_autoreply_add_yes":
		// max keyword is 10 chars
		$add_autoreply_keyword = substr(core_sanitize_keyword($_POST['add_autoreply_keyword']), 0, 10);

		if (auth_isadmin()) {
			$smsc = $_POST['smsc'];
		}

		if ($add_autoreply_keyword) {
			if (keyword_isavail($add_autoreply_keyword)) {
				$db_query = "INSERT INTO " . _DB_PREF_ . "_featureAutoreply (uid,autoreply_keyword,smsc) VALUES (?,?,?)";
				if (dba_insert_id($db_query, [$user_config['uid'], $add_autoreply_keyword, $smsc])) {
					$_SESSION['dialog']['info'][] = _('SMS autoreply keyword has been added') . " (" . _('keyword') . ": $add_autoreply_keyword)";
				} else {
					$_SESSION['dialog']['danger'][] = _('Fail to add SMS autoreply') . " (" . _('keyword') . ": $add_autoreply_keyword)";
				}
			} else {
				$_SESSION['dialog']['info'][] = _('SMS keyword already exists, reserved or use by other feature') . " (" . _('keyword') . ": $add_autoreply_keyword)";
			}
		} else {
			$_SESSION['dialog']['info'][] = _('All mandatory fields must be filled');
		}
		header("Location: " . _u('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_add'));
		exit();

	case "sms_autoreply_edit":
		$db_table = _DB_PREF_ . "_featureAutoreply";
		$conditions = [
			'autoreply_id' => $autoreply_id
		];
		$list = dba_search($db_table, '*', $conditions);
		$edit_autoreply_keyword = _display(strtoupper($list[0]['autoreply_keyword']));
		$select_reply_smsc = '';
		if (auth_isadmin()) {
			$select_reply_smsc = "<tr><td>" . _('SMSC') . "</td><td>" . gateway_select_smsc('smsc', $list[0]['smsc']) . "</td></tr>";
		}
		$content = _dialog() . "
			<h2>" . _('Manage autoreply') . "</h2>
			<h3>" . _('Edit SMS autoreply') . "</h3>
			<form action=index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_edit_yes method=post>
			" . _CSRF_FORM_ . "
			<input type=hidden name=autoreply_id value=\"$autoreply_id\"> 
			<table class=playsms-table>
				<tbody>
				<tr>
					<td class=label-sizer>" . _('SMS autoreply keyword') . "</td>
					<td><input type=text value=\"$edit_autoreply_keyword\" readonly></td>					
				</tr>
				" . $select_reply_smsc . "
				</tbody>
			</table>
			<p><input type=submit class=button value='" . _('Save') . "'></p>
			</form>
			<p>" . _back('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_list');
		_p($content);
		break;

	case "sms_autoreply_edit_yes":
		if (auth_isadmin()) {
			$smsc = $_REQUEST['smsc'];
		}
		if ($autoreply_id && $smsc) {
			$db_table = _DB_PREF_ . "_featureAutoreply";
			$items = [
				'smsc' => $smsc
			];
			$conditions = [
				'autoreply_id' => $autoreply_id
			];
			dba_update($db_table, $items, $conditions);
			$_SESSION['dialog']['info'][] = _('SMS autoreply has been updated');
		} else {
			$_SESSION['dialog']['danger'][] = _('Fail to update SMS autoreply');
		}
		header("Location: " . _u('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_edit&autoreply_id=' . $autoreply_id));
		exit();

	// scenario
	case "sms_autoreply_scenario_del":
		$_SESSION['dialog']['info'][] = _('Fail to delete SMS autoreply scenario');
		if ($autoreply_id && $autoreply_scenario_id = $_REQUEST['autoreply_scenario_id']) {
			$db_query = "DELETE FROM " . _DB_PREF_ . "_featureAutoreply_scenario WHERE autoreply_id=? AND autoreply_scenario_id=?";
			if (dba_affected_rows($db_query, [$autoreply_id, $autoreply_scenario_id])) {
				$_SESSION['dialog']['info'][] = _('SMS autoreply scenario has been deleted');
			} else {
				$_SESSION['dialog']['danger'][] = _('Fail to delete SMS autoreply scenario');
			}
		}
		header("Location: " . _u('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_manage&autoreply_id=' . $autoreply_id));
		exit();

	case "sms_autoreply_scenario_add":
		$db_query = "SELECT * FROM " . _DB_PREF_ . "_featureAutoreply WHERE autoreply_id=?";
		$db_result = dba_query($db_query, [$autoreply_id]);
		$db_row = dba_fetch_array($db_result);
		$autoreply_keyword = _display(core_sanitize_keyword($db_row['autoreply_keyword']));
		$content = _dialog() . "
			<h2>" . _('Manage autoreply') . "</h2>
			<h3>" . _('Add SMS autoreply scenario') . "</h3>
			<form action=index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_scenario_add_yes method=post>
			" . _CSRF_FORM_ . "
			<input type=hidden name=autoreply_id value=\"$autoreply_id\">
			<table class=playsms-table>
				<tbody>
				<tr>
					<td class=label-sizer>" . _('SMS autoreply keyword') . "</td><td>" . $autoreply_keyword . "</td>
				</tr>";
		for ($i = 1; $i <= 7; $i++) {
			$content .= "
				<tr>
					<td>" . _('SMS autoreply scenario parameter') . " $i</td><td><input type=text size=10 maxlength=20 name=\"add_autoreply_scenario_param" . $i . "\" value=\"\"> " . _hint(_('This field is not mandatory')) . "</td>
				</tr>";
		}
		$content .= "
			<tr>
				<td>" . _mandatory(_('SMS autoreply scenario reply')) . "</td><td><input type=text name=add_autoreply_scenario_result value=\"\"></td>
			</tr>
			</table>
			<p><input type=submit class=button value='" . _('Save') . "'>
			</form>
			<p>" . _back('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_manage&autoreply_id=' . $autoreply_id);
		_p($content);
		break;

	case "sms_autoreply_scenario_add_yes":
		$add_autoreply_scenario_result = $_POST['add_autoreply_scenario_result'];
		for ($i = 1; $i <= 7; $i++) {
			${"add_autoreply_scenario_param" . $i} = core_sanitize_keyword($_POST['add_autoreply_scenario_param' . $i]);
		}
		if ($add_autoreply_scenario_result) {
			$autoreply_scenario_param_list = '';
			for ($i = 1; $i <= 7; $i++) {
				$autoreply_scenario_param_list .= "autoreply_scenario_param$i,";
				$autoreply_scenario_keyword_param_entry .= "?,";

			}
			if ($autoreply_scenario_param_list) {
				$autoreply_scenario_param_list = preg_replace('/,$/', '', $autoreply_scenario_param_list);
				$autoreply_scenario_keyword_param_entry = preg_replace('/,$/', '', $autoreply_scenario_keyword_param_entry);
			}
			$db_argv = [
				$autoreply_id,
				$add_autoreply_scenario_result,
			];
			for ($i = 1; $i <= 7; $i++) {
				$db_argv[] = ${"add_autoreply_scenario_param" . $i};
			}
			$db_query = "
				INSERT INTO " . _DB_PREF_ . "_featureAutoreply_scenario 
				(autoreply_id,autoreply_scenario_result," . $autoreply_scenario_param_list . ") VALUES (?,?," . $autoreply_scenario_keyword_param_entry . ")";
			if ($new_uid = dba_insert_id($db_query, $db_argv)) {
				$_SESSION['dialog']['info'][] = _('SMS autoreply scenario has been added');
			} else {
				$_SESSION['dialog']['info'][] = _('Fail to add SMS autoreply scenario');
			}
		} else {
			$_SESSION['dialog']['info'][] = _('All mandatory fields must be filled');
		}
		header("Location: " . _u('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_scenario_add&autoreply_id=' . $autoreply_id));
		exit();

	case "sms_autoreply_scenario_edit":
		$autoreply_scenario_id = $_REQUEST['autoreply_scenario_id'];
		$db_query = "SELECT * FROM " . _DB_PREF_ . "_featureAutoreply WHERE autoreply_id=?";
		$db_result = dba_query($db_query, [$autoreply_id]);
		$db_row = dba_fetch_array($db_result);
		$autoreply_keyword = core_sanitize_keyword($db_row['autoreply_keyword']);
		$content = _dialog() . "
			<h2>" . _('Manage autoreply') . "</h2>
			<h3>" . _('Edit SMS autoreply scenario') . "</h3>
			<form action=index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_scenario_edit_yes method=post>
			" . _CSRF_FORM_ . "
			<input type=hidden name=autoreply_id value=\"$autoreply_id\">
			<input type=hidden name=autoreply_scenario_id value=\"$autoreply_scenario_id\">
			<table class=playsms-table>
				<tbody>
				<tr>
					<td class=label-sizer>" . _('SMS autoreply keyword') . "</td><td>" . $autoreply_keyword . "</td>
				</tr>";
		$db_query = "SELECT * FROM " . _DB_PREF_ . "_featureAutoreply_scenario WHERE autoreply_id=? AND autoreply_scenario_id=?";
		$db_result = dba_query($db_query, [$autoreply_id, $autoreply_scenario_id]);
		$db_row = dba_fetch_array($db_result);
		$db_row = _display($db_row);
		for ($i = 1; $i <= 7; $i++) {
			${"edit_autoreply_scenario_param" . $i} = $db_row['autoreply_scenario_param' . $i];
		}
		for ($i = 1; $i <= 7; $i++) {
			$content .= "
				<tr>
					<td>" . _('SMS autoreply scenario parameter') . " $i</td><td><input type=text size=10 maxlength=20 name=edit_autoreply_scenario_param" . $i . " value=\"" . ${"edit_autoreply_scenario_param" . $i} . "\"> " . _hint(_('This field is not mandatory')) . "</td>
				</tr>";
		}
		$edit_autoreply_scenario_result = $db_row['autoreply_scenario_result'];
		$content .= "
			<tr>
				<td>" . _mandatory(_('SMS autoreply scenario reply')) . "</td><td><input type=text name=edit_autoreply_scenario_result value=\"$edit_autoreply_scenario_result\"></td>
			</tr>
			</table>
			<p><input type=submit class=button value=\"" . _('Save') . "\"></p>
			</form>
			<p>" . _back('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_manage&autoreply_id=' . $autoreply_id);
		_p($content);
		break;

	case "sms_autoreply_scenario_edit_yes":
		$autoreply_scenario_id = $_POST['autoreply_scenario_id'];
		$edit_autoreply_scenario_result = $_POST['edit_autoreply_scenario_result'];
		$db_argv = [
			time(),
			$edit_autoreply_scenario_result,
		];
		for ($i = 1; $i <= 7; $i++) {
			${"edit_autoreply_scenario_param" . $i} = core_sanitize_keyword($_POST['edit_autoreply_scenario_param' . $i]);
		}
		if ($edit_autoreply_scenario_result) {
			$autoreply_scenario_param_list = "";
			for ($i = 1; $i <= 7; $i++) {
				$autoreply_scenario_param_list .= "autoreply_scenario_param" . $i . "=?,";
				$db_argv[] = ${"edit_autoreply_scenario_param" . $i};
			}
			$autoreply_scenario_param_list = preg_replace('/,$/', '', $autoreply_scenario_param_list);
			$db_argv[] = $autoreply_id;
			$db_argv[] = $autoreply_scenario_id;
			$db_query = "
				UPDATE " . _DB_PREF_ . "_featureAutoreply_scenario 
				SET c_timestamp=?,autoreply_scenario_result=?," . $autoreply_scenario_param_list . " 
				WHERE autoreply_id=? AND autoreply_scenario_id=?";
			if (dba_affected_rows($db_query, $db_argv)) {
				$_SESSION['dialog']['info'][] = _('SMS autoreply scenario has been edited');
			} else {
				$_SESSION['dialog']['info'][] = _('Fail to edit SMS autoreply scenario');
			}
		} else {
			$_SESSION['dialog']['info'][] = _('All mandatory fields must be filled');
		}
		header("Location: " . _u('index.php?app=main&inc=feature_sms_autoreply&op=sms_autoreply_scenario_edit&autoreply_id=' . $autoreply_id . '&autoreply_scenario_id=' . $autoreply_scenario_id));
		exit();
}
