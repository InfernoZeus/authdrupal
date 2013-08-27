<?php
/**
 * Default settings for the authdrupal plugin
 *
 * @author Ben Fox-Moore <ben.foxmoore@gmail.com>
 */

$conf['drupalRoot'] 		= "../";
$conf['TablesToLock']		= array("users", "users AS u", "users_roles AS ur", "role AS r");
$conf['findPasswordHash'] 	= "SELECT pass FROM users WHERE name='%{user}'";
$conf['getUserInfo'] 		= "SELECT pass, name, mail FROM users WHERE name='%{user}'";
$conf['getGroups'] 			= "SELECT DISTINCT r.name AS 'group' FROM users AS u, users_roles AS ur, role AS r
							   WHERE (u.uid = ur.uid AND r.rid = ur.rid AND u.name = '%{user}')";
$conf['getUsers']    		= "SELECT DISTINCT name AS user FROM users AS u
							   LEFT JOIN users_roles AS ur ON u.uid=ur.uid
							   LEFT JOIN role AS r ON ur.rid=r.rid";
$conf['FilterLogin'] 		= "login LIKE '%{user}'";
$conf['FilterName']  		= "fullname LIKE '%{name}'";
$conf['FilterEmail'] 		= "email LIKE '%{email}'";
$conf['FilterGroup'] 		= "name LIKE '%{group}'";
$conf['SortOrder']   		= "ORDER BY login";
$conf['debug'] 				= 0;
