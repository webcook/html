<?php

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

#include_once(PHPWG_ROOT_PATH.'include/common.inc.php' );
include_once(PHPWG_ROOT_PATH.'admin/include/image.class.php');
include_once(PHPWG_ROOT_PATH.'admin/include/functions_upload.inc.php');

class SynchronizeLocalDirectory
{
    public $cnt_new_dir = 0;
    public $cnt_new_images = 0;
    public $cnt_new_thumbnails = 0;
    public $debug = '';
    
    private $high_res_dir = 'pwg_high';

    
    function rglob($pattern='*', $flags = 0, $path='')
    {
         $paths=glob($path.'*', GLOB_MARK|GLOB_ONLYDIR|GLOB_NOSORT);
         $files=glob($path.$pattern, $flags);
         foreach ($paths as $path) { $files=array_merge($files,$this->rglob($pattern, $flags, $path)); }
         sort($files);
         return $files;
    }

    function directory_contains_files($path)
    {
        $files = glob($path.'*');
        $found_files = false;
        foreach($files as $file)
        {
            if (!is_dir($file))
            {
                $found_files = true;
                break;
            }
        }
        return $found_files;
    }
    
    function create_thumbnail($high_res_file, $dest_file)
    {
	global $conf;
	#copied this code from piwigo core. why is there no function to do that???
	#TODO: overwrite the thumbnail, if the high_res_file is newer than the thumbnail.
	if (! file_exists($dest_file))
	{
	    $img = new pwg_image($high_res_file);
	    $img->pwg_resize(
		$dest_file,
		$conf['upload_form_thumb_maxwidth'],
		$conf['upload_form_thumb_maxheight'],
		$conf['upload_form_thumb_quality'],
		false,
		true,
		$conf['upload_form_thumb_crop'],
		$conf['upload_form_thumb_follow_orientation']
	    );
	    $this->cnt_new_thumbnails++;
            $img->destroy();
	}
    }

    function create_websize_image($high_res_file, $dest_file)
    {
	global $conf;
	#TODO: overwrite the websizedimage, if the high_res_file is newer than the websized image.
	if (! file_exists($dest_file))
	{
	    $img = new pwg_image($high_res_file);
	    $img->pwg_resize(
		$dest_file,
    		$conf['upload_form_websize_maxwidth'],
    		$conf['upload_form_websize_maxheight'],
    		$conf['upload_form_websize_quality'],
    		$conf['upload_form_automatic_rotation'],
	        false
    	    );
            $img->destroy();
    	}
    }
    
    function create_resized_images($base_path)
    {
	global $conf;
	$high_res_path = $base_path.'/'.$this->high_res_dir;
        $files = glob($high_res_path.'/*');
        foreach($files as $file)
        {
            if (!is_dir($file))
            {
        	if (in_array(pathinfo($file, PATHINFO_EXTENSION), $conf['picture_ext']))
        	{
        	    $web_size_file = $base_path.'/'.basename($file);
		    $thumb_file = file_path_for_type($web_size_file, 'thumb');
        	
        	    #TODO: integreate the two functions below in one, or directly add a pwg_image object as parameter 
        	    #(dont know how much time it needs to instanziate this object two times, like its done now)
		    $this->create_thumbnail($file, $thumb_file);
		    $this->create_websize_image($file, $web_size_file);
		} #TODO: also create thumbnails for videos
            }
        }
    }
    
    function synchronize()
    {
	global $conf;
        $sync_source_dir = '/home/gallery2/albums/Reise08';
        $piwigo_gallery_dir = '/var/lib/piwigo/galleries/Reise08';
        #$piwigo_gallery_dir = '/tmp/test';

	#counters:

        $sync_source_dirs = $this->rglob('*',GLOB_MARK|GLOB_ONLYDIR,$sync_source_dir);
        $piwigo_dirs = $this->rglob('*',GLOB_MARK|GLOB_ONLYDIR,$piwigo_gallery_dir);

        #remove dir prefixes
        # TODO: if you have dirs with a "%" in your filename this will fail
        $sync_source_dirs = preg_replace("%$sync_source_dir%", '', $sync_source_dirs);
        $piwigo_dirs = preg_replace("%$piwigo_gallery_dir%", '', $piwigo_dirs);

        reset($piwigo_dirs);
        foreach ($sync_source_dirs as $source_dir)
        {
            $piwigo_dir = current($piwigo_dirs);
            # dont use the thumbnail or highres dirs for comparison
            while (basename($piwigo_dir) == $conf['dir_thumbnail'] || basename($piwigo_dir) == $this->high_res_dir)
            {
                next($piwigo_dirs);
                $piwigo_dir = current($piwigo_dirs);
            }

            while (strcmp($source_dir, $piwigo_dir) > 0)
            {
                #TODO: there is a dir in piwigo, which doesnt exist in the source. Delete it in piwigo.
                next($piwigo_dirs);
                $piwigo_dir = current($piwigo_dirs);
            }

            if (strcmp($source_dir, $piwigo_dir) == 0) #equal
            {
                #TODO: do a comparison if there are deleted files in the source directory
                #check if there are new files in the source directory
                $this->create_resized_images($piwigo_gallery_dir.'/'.$source_dir);

                next($piwigo_dirs);
            } else
            {
                #There is a dir in the source which doesnt exist in piwigo yet. create it!
                mkdir($piwigo_gallery_dir.'/'.$source_dir, 0775);
                # create the thumbnail dir and a symlink to the high resolution dir, if there are files
                # in this dir.
                if ($this->directory_contains_files($sync_source_dir.'/'.$source_dir))
                {
                    mkdir($piwigo_gallery_dir.'/'.$source_dir.'/'.$conf['dir_thumbnail'], 0775);
                    symlink($sync_source_dir.'/'.$source_dir, $piwigo_gallery_dir.'/'.$source_dir.'/'.$this->high_res_dir);
                    $this->cnt_new_dir++;
                    $this->create_resized_images($piwigo_gallery_dir.'/'.$source_dir);
                }
            }
        }
    }
}
?>

