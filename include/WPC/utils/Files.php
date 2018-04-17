<?php

/**
* Provides File related function.
* Function to handle file uploads and file processing.
* 
* Static Class (Files)
*
* @author  @Warkanum (hein@kinathka.co.za)
* @version 1.0
* @since 1
* @access public
* @updated: 2012-03-12
*/
abstract class Files
{
	private static $AR_MIMEINFO = array(
		'txt' => 'text/plain',
		'diskdir' => 'text/plain',
		'ghostfile' => 'text/plain',
		'lst' => 'text/plain',
		'htm' => 'text/html',
		'html' => 'text/html',
		'php' => 'text/plain',		
		'asc' => 'text/plain',
		'bmp' => 'image/bmp',
		'gif' => 'image/gif',
		'jpeg' => 'image/jpeg',
		'jpg' => 'image/jpeg',
		'jpe' => 'image/jpeg',
		'png' => 'image/png',
		'ico' => 'image/vnd.microsoft.icon',
		'mpeg' => 'video/mpeg',
		'mpg' => 'video/mpeg',
		'mpe' => 'video/mpeg',
		'qt' => 'video/quicktime',
		'mov' => 'video/quicktime',
		'avi' => 'video/x-msvideo',
		'wmv' => 'video/x-ms-wmv',
		'mp2' => 'audio/mpeg',
		'mp3' => 'audio/mpeg',
		'rm' => 'audio/x-pn-realaudio',
		'ram' => 'audio/x-pn-realaudio',
		'rpm' => 'audio/x-pn-realaudio-plugin',
		'ra' => 'audio/x-realaudio',
		'wav' => 'audio/x-wav',
		'css' => 'text/css',
		'zip' => 'application/zip',
		'pdf' => 'application/pdf',
		'doc' => 'application/msword',
		'bin' => 'application/octet-stream',
		'exe' => 'application/octet-stream',
		'class' => 'application/octet-stream',
		'dll' => 'application/octet-stream',
		'xls' => 'application/vnd.ms-excel',
		'ppt' => 'application/vnd.ms-powerpoint',
		'wbxml' => 'application/vnd.wap.wbxml',
		'wmlc' => 'application/vnd.wap.wmlc',
		'wmlsc' => 'application/vnd.wap.wmlscriptc',
		'dvi' => 'application/x-dvi',
		'spl' => 'application/x-futuresplash',
		'gtar' => 'application/x-gtar',
		'gzip' => 'application/x-gzip',
		'js' => 'application/x-javascript',
		'swf' => 'application/x-shockwave-flash',
		'tar' => 'application/x-tar',
		'xhtml' => 'application/xhtml+xml',
		'au' => 'audio/basic',
		'snd' => 'audio/basic',
		'midi' => 'audio/midi',
		'mid' => 'audio/midi',
		'm3u' => 'audio/x-mpegurl',
		'tiff' => 'image/tiff',
		'tif' => 'image/tiff',
		'rtf' => 'text/rtf',
		'wml' => 'text/vnd.wap.wml',
		'wmls' => 'text/vnd.wap.wmlscript',
		'xsl' => 'text/xml',
		'xml' => 'text/xml'
	);
	
	/**
	*	Check if a remote file exists
	*	
	*	@param $url String Remote file url
	*	@return Boolean Return true if the file exists, false if not.
	*	@access public
	*/
	public static function remoteFileExists($url)
	{
		$f = NULL;
		$isThere = false;
		
		if ($f = fopen($url, "r"))
			$isThere = true;
			
		if ($f)
			fclose($f);
			
		return $isThere;
	}
	
	/**
	*	Print header information to force the user to download a specific file instead of displaing inline.
	*	Do not output anything before this function or after.
	*
	*	@param $file String Path to file.
	*	@return Boolean Return true if the file exists, false if not.
	*	@access public
	*/
	public static function headerDownloadFile($file)
	{
		$mime = 'application/force-download';
		header('Pragma: public'); 	// required
		header('Expires: 0');		// no cache
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: private',false);
		header('Content-Type: '.$mime);
		header('Content-Disposition: attachment; filename="'.basename($file).'"');
		header('Content-Transfer-Encoding: binary');
		header('Connection: close');
		readfile($file);		// push it out
		exit();
	}
	
	/**
	*	Get file extention by Mime type
	*	
	*
	*	@param $mimetype String Mimetype
	*	@return String Return true file extention.
	*	@access public
	*/
	public static function getExtByMime($mimetype)
	{	
		$ext = "file";
		
		foreach (self::$AR_MIMEINFO as $e => $m)
		{
			if (strtolower($mimetype) == strtolower($m))
			{
				$ext = $e;
				return $ext;
			}
		}
		
		return $ext;	
	}
	
	/**
	*	Get file mime type by extention 
	*	
	*
	*	@param $ext String The file extention.
	*	@return String Return Mimetype
	*	@access public
	*/
	public static function getMimeByExt($ext)
	{
		$mime = 'text/html';
		
		foreach (self::$AR_MIMEINFO as $e => $m)
		{
			if (strtolower($e) == strtolower($ext))
			{
				$mime = $m;
				return $mime;
			}
		}
		
		return $mime;
	}
	
	/**
	*	Get file extention from its name
	*	
	*
	*	@param $name String Name
	*	@return String Return thefile extention.
	*	@access public
	*/
	public static function getExtFromName($name)
	{
		$ar = explode('.',$name);
		return array_pop($ar);
	}
	
	/**
	*	Process and save posted file
	*	
	*	@param $indexname String Name of the posted file
	*	@param $destPath String Destination directory to save the file.
	*	@param $$newname String Reference String with the new name of the file. Extention is appended.
	*	@return String Return true file extention.
	*	@access public
	*/	
	public static function processUpload($indexname, $destPath, &$newname)
	{
		$allowedExtensions = array("txt","csv","xml",
			"css","doc","xls","rtf","ppt","pdf","swf","flv","avi",
			"wmv","mov","jpg","jpeg","gif","png"); 
			
		$upload_errors = array(
			0 => "No errors.",
			1 => "Larger than upload_max_filesize.",
			2 => "Larger than form MAX_FILE_SIZE.",
			3 => "Partial upload.",
			4 => "No file.",
			5 => "No temporary directory.",
			6 => "Can't write to disk.",
			7 => "File upload stopped by extension.",
			8 => "File is empty."
		  );
  
		//check for a valid file
		if (isset($_FILES[$indexname]) && $_FILES[$indexname]['size'] > 1)
		{
				
			if ($_FILES[$indexname]['size'] < 67108864) //64MB limit!
			{
				$parts = explode('.',$_FILES[$indexname]['name']);
				$fileext = strtolower(end($parts));
				$newname = $newname . '.' . $fileext; //this appends the file ext.
				$uploaddir = sprintf('%s/%s', $destPath, $newname);
				
				
				
				if ($_FILES[$indexname]['error'] > 0)
				{
					$code = $_FILES[$indexname]['error'];
					Msg::add(sprintf('File (%s) was not uploaded. Error code: %s',
						$_FILES[$indexname]['name'], $upload_errors[$code]), 'Upload Failed:', 'error');
						return false;
				}
				
				if (in_array($fileext, $allowedExtensions))
				{
					if (move_uploaded_file($_FILES[$indexname]['tmp_name'], $uploaddir)) {
						return true;
					}
				}
			}
		}
		return false;
	}
	
}



/*
Usefull tips for uploading huge files

.htaccess

php_value session.gc_maxlifetime 10800
php_value max_input_time         10800
php_value max_execution_time     10800
php_value upload_max_filesize    110M
php_value post_max_size          120M

*/

?>