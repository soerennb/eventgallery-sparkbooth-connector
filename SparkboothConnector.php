<?php
/**
 * @package     SEB
 * @subpackage  com_eventgallery
 *
 * @copyright   Copyright (C) 2014 Sören Eberhardt-Biermann. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

// Akeeba Framework on Framework https://github.com/akeeba/fof
if (!defined('F0F_INCLUDED'))
{
    include_once JPATH_LIBRARIES . '/f0f/include.php';
}

class EventGalleryControllerSparkboothConnector extends JControllerLegacy
{
    var $errormsg = "";
    
    public function display($cachable = false, $urlparams = array())
    {
    	$credentials['username'] = JFactory::getApplication()->input->get('username');
    	$credentials['password'] = JFactory::getApplication()->input->get('password');
    	if( empty($credentials['username']) ||empty ($credentials['password'])) {
	    self::return_error('Missing Crendentials');
	    exit;
	}
    	$f0f = new F0FIntegrationJoomlaPlatform();
    	$result = $f0f->loginuser($credentials);
    	if( $result == false ) {
	    self::return_error('Login failed!');
	    exit;
	}
        $user = JFactory::getUser();
	// Authorization - is the user allowed to create new galleries?
	$authorised = $user->authorise('core.create', 'com_eventgallery');
	if( !$authorised ) {
	    self::return_error('Authorization failed!');
	    exit;
	}

	$name = JFactory::getApplication()->input->getString('name');
	$email = JFactory::getApplication()->input->getString('email');
	$messagetxt = JFactory::getApplication()->input->getString('message');

	if( empty($_FILES["media"])) {
		self::return_error('Keine Bilddatei empfangen.');
		exit;
	}

	// Name of the Folder, e.g. "PhotoBooth_2014-10-05", keep the name until 5 a.m. of the next day
	$folder = 'PhotoBooth_'.date('Y-m-d', strtotime("-5 hours"));
	$db = JFactory::getDBO();
	// first check, if there's already a folder with that name
	$query = $db->getQuery(True)
                    ->select('count(1)')
                    ->from($db->quoteName('#__eventgallery_folder'))
                    ->where('folder=' . $db->quote($folder));
        $db->setQuery($query);
        if ($db->loadResult() == 0) {
        	// if not, create a new folder entry in the table
        	$query = $db->getQuery(true)
                        ->insert($db->quoteName('#__eventgallery_folder'))
                        ->columns('folder,foldertags,description,date,published,watermarkid,userid,created,modified,ordering')
                        ->values(
                            $db->Quote($folder).','.
                            $db->Quote('event,photobooth').','.                            
                            $db->Quote($folder).','.
                            $db->Quote(date('Y-m-d', strtotime("-5 hours")).' 00:00:00').','.
                            '0,1,'.
                            $db->Quote($user->id).','.
                            'now(),now(),0');
                $db->setQuery($query);
		$db->query();
        }
	// here are additional parameters, which we receive from the PhotoBooth
	$description = '';
	$description .= empty($name) ? '' : "Name: $name\r\n" ;
	$description .= empty($email) ? '' : "E-Mail: $email\r\n";
	$description .= empty($messagetxt) ? '' : "Nachricht: $messagetxt";

	// handle the upload.
	$result = self::uploadfile( $folder, $description );

	if ( $result == false ) {
		// There was an error uploading the image.
		self::return_error('Sorry, there was a problem uploading your file!'); 
		exit;
	} else {
		// The image was uploaded successfully!
		self::return_success();
	}


        
    }
    	function uploadFile($folder, $description ) {

		$user = JFactory::getUser();

		$path = JPATH_SITE.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'eventgallery';
		@mkdir($path);

		$path=$path.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR ;
		@mkdir($path);


		$fn = $_FILES['media']['name'];
		$fn=JFile::makeSafe($fn);

		$allowedExtensions = Array('jpg', 'gif', 'png', 'jpeg');

		if (!in_array(strtolower( pathinfo ( $fn , PATHINFO_EXTENSION) ), $allowedExtensions) ) {
            		self::return_error("Unsupported file extension in $fn");
            		die();
		}

		$uploadedFiles = Array();	

		// form submit
		$files = $_FILES['media'];

		if($files['error'] == UPLOAD_ERR_OK) {
			$fn = $files['name'];
			$fn = str_replace('..','',$fn);
			$result = move_uploaded_file(
				$files['tmp_name'],
				$path. $fn
			);
			if( !$result) {
				self::return_error('Could not move uploaded File!');die();
			}
			array_push($uploadedFiles, $fn);
		} else {
			self::return_error('Upload Error!');die();
		}		

		$db = JFactory::getDBO();
		foreach($uploadedFiles as $uploadedFile) {
			if (file_exists($path.$uploadedFile)) {
			
				
				@list($width, $height, $type, $attr) = getimagesize($path.$uploadedFile);
                $query = $db->getQuery(True)
                    ->select('count(1)')
                    ->from($db->quoteName('#__eventgallery_file'))
                    ->where('folder=' . $db->quote($folder))
                    ->where('file=' . $db->quote($uploadedFile));
                $db->setQuery($query);
                if ($db->loadResult() == 0) {
                    $query = $db->getQuery(true)
                        ->insert($db->quoteName('#__eventgallery_file'))
                        ->columns('folder,file,caption,userid,created,modified,ordering')
                        ->values(
                            $db->Quote($folder).','.
                            $db->Quote($uploadedFile).','.
                            $db->Quote($description ).','.
                            $db->Quote($user->id).','.
                            'now(),now(),0');
                }else{
                    $query = $db->getQuery(true)
                        ->update($db->quoteName('#__eventgallery_file'))
                        ->set('userid='.$db->Quote($user->id))
                        ->set('created=now()')
                        ->set('modified=now()')

                        ->where('folder='.$db->Quote($folder))
                        ->where('file='.$db->Quote($uploadedFile));
                }


		$db->setQuery($query);
		$db->query();
		EventgalleryLibraryFolderLocal::updateMetadata($path.$uploadedFile, $folder, $uploadedFile);
		} 
	}

		 
	return true;

    }
    static function return_error($errormsg='') {
	echo '<?xml version="1.0" encoding="UTF-8"?><rsp status="fail" ><err msg="'.$errormsg.'" /></rsp>';
	
    }

    static function return_success() {
	echo '<?xml version="1.0" encoding="UTF-8"?><rsp status="ok" />';
    }
}
