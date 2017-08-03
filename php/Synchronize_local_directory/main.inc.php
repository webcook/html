<?php
/*
Version: 1.0beta
Plugin Name: Synchronize local directory
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=595
Author: RMM
Description: Synchronizes the piwigo gallery structure with a directory structure outside from piwigo and creates the necessay thumbnails and websized pictures. No Pictures are copied, the directories are only symlinked.
*/

// Chech whether we are indeed included by Piwigo.
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

// Define the path to our plugin.
define('SKELETON_PATH', PHPWG_PLUGINS_PATH.basename(dirname(__FILE__)).'/');

// Hook on to an event to show the administration page.
add_event_handler('get_admin_plugin_menu_links', 'synchronize_local_directory_admin_menu');

// Add an entry to the 'Plugins' menu.
function synchronize_local_directory_admin_menu($menu) {
  array_push(
    $menu,
    array(
      'NAME'  => 'Synchronize local directory',
      'URL'   => get_admin_plugin_menu_link(dirname(__FILE__)).'/admin.php'
    )
  );
  return $menu;
}

?>
