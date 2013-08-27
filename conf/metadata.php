<?php
/**
 * Options for the authdrupal plugin
 *
 * @author Ben Fox-Moore <ben.foxmoore@gmail.com>
 */

$meta['drupalRoot'] = array('string');
$meta['TablesToLock'] = array('array');
$meta['findPasswordHash'] = array('');
$meta['getUserInfo'] = array('');
$meta['getGroups'] = array('');
$meta['getUsers'] = array('');
$meta['FilterLogin'] = array('string');
$meta['FilterName'] = array('string');
$meta['FilterEmail'] = array('string');
$meta['FilterGroup'] = array('string');
$meta['SortOrder'] = array('string');
$meta['debug'] = array('multichoice','_choices' => array(0,1,2));

