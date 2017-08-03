<?php
// Chech whether we are indeed included by Piwigo.
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

// Fetch the template.
global $template;

// Add our template to the global template
$template->set_filenames(
  array(
    'plugin_admin_content' => dirname(__FILE__).'/admin.tpl'
  )
);


include_once dirname(__FILE__).'/SynchronizeLocalDirectory.php';
$sync = new SynchronizeLocalDirectory();
$sync->synchronize();

// Assign the template contents to ADMIN_CONTENT

$template->assign('synchronizeLocalDirectory_output', $sync->debug);

$template->assign_var_from_handle('ADMIN_CONTENT', 'plugin_admin_content');
?>
