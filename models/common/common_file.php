<?php
/**
 * class common_file
 *
 * Copyright (c) 2009-2011 Laposa Ltd (http://laposa.co.uk)
 * Licensed under the New BSD License. See the file LICENSE.txt for details.
 *
 */
 
class common_file extends Onxshop_Model {

	/**
	 * PRIMARY KEY
	 * @access private
	 */
	var $id;
	/**
	 * @access private
	 */
	var $src;
	/**
	 * @access private
	 */
	var $role;
	
	/**
	 * NOT NULL REFERENCES common_node(id) ON UPDATE CASCADE ON DELETE CASCADE
	 * @access private
	 */
	var $node_id;
	/**
	 * @access private
	 */
	var $title;
	/**
	 * @access private
	 */
	var $description;
	/**
	 * @access private
	 */
	var $priority;
	/**
	 * @access private
	 */
	var $modified;
	/**
	 * @access private
	 */
	var $author;

	var $_hashMap = array(
		'id'=>array('label' => '', 'validation'=>'int', 'required'=>true), 
		'src'=>array('label' => '', 'validation'=>'string', 'required'=>true),
		'role'=>array('label' => '', 'validation'=>'string', 'required'=>false),
		'node_id'=>array('label' => '', 'validation'=>'int', 'required'=>true),
		'title'=>array('label' => '', 'validation'=>'string', 'required'=>true),
		'description'=>array('label' => '', 'validation'=>'string', 'required'=>false),
		'priority'=>array('label' => '', 'validation'=>'int', 'required'=>false),
		'modified'=>array('label' => '', 'validation'=>'datetime', 'required'=>true),
		'author'=>array('label' => '', 'validation'=>'int', 'required'=>true)
	);

	/**
	 * get file detail
	 */
	 
	public function getFileDetail($id) {
		
		if (!is_numeric($id)) return false;
 		
 		$file_detail = $this->detail($id);
 		
 		$file_detail = $this->pupulateAdditionalInfo($file_detail);
		
		return $file_detail;
	}

	/**
	 * get additional info
	 */
	 
	public function pupulateAdditionalInfo($file_detail) {
		
		$full_path = ONXSHOP_PROJECT_DIR . $file_detail['src'];
		
		$file_detail['file_path_encoded'] = $this->encode_file_path($full_path);
			
		if (file_exists($full_path)) {
			$file_detail['info'] = $this->getFileInfo($full_path);
		} else {
			msg("File $full_path", 'error', 1);
		}
		
		if (preg_match('/^image/', $file_detail['info']['mime-type'])) {
			$file_detail['imagesize'] = $this->getImageSize($full_path);
		}
			
		return $file_detail;
	}
	
	/**
	 * list files
	 */
	 
	function listFiles($node_id , $priority = "priority DESC, id ASC", $role = false) {
	
		$result = array();
		
		if (is_numeric($node_id)) {
			if ($role) {
				$files = $this->listing("node_id = $node_id AND role = '$role'", $priority);
			} else {
				$files = $this->listing("node_id = $node_id", $priority);
			}
		} else {
			msg("File->listFiles: node_id is not numeric", 'error');
		}

		foreach ($files as $file_detail) {
			
			$file_detail = $this->pupulateAdditionalInfo($file_detail);
			
			$result[] = $file_detail;
		}
		return $result;
	}
	
	/**
	 * get file link
	 */
	 
	function getFileLink($src) {
	
		$file_list = $this->listing("src='$src'");
		return $file_list;
	}
	
	/**
	 * insert file
	 */
	 
	function insertFile($file = array()) {
	
		$src = ONXSHOP_PROJECT_DIR . $file['src'];
		
		if (is_readable($src)) {
			
			if (!is_numeric($file['priority'])) $file['priority'] = 0;
			$file['modified'] = date('c');
			$file['author'] = $_SESSION['authentication']['logon'];
			
			if ($id = $this->insert($file)) {
				msg('File Inserted', 'ok', 2);
				return $id;
			} else {
				msg("Can't insert file $src", 'error');
				return false;
			}
			
		} else {
			msg("$src does not exists!", 'error');
			return false;
		}
	}
	
	/**
	 * Copy single uploaded file
	 * return string filename on success, array when file exists, otherwise false 
	 *
	 * @param unknown_type $file
	 * @param unknown_type $save_dir
	 * @param unknown_type $type
	 * @return mixed
	 */
	 
	function getSingleUpload($file = array(), $save_dir, $overwrite = 0) {
	
		$upload_file = $file['tmp_name'];
		$safe_filename = $this->nameToSafe($file['name']);
		$save_file = $save_dir . $safe_filename;
		$save_file_full = ONXSHOP_PROJECT_DIR . $save_file;
		$save_dir_full = ONXSHOP_PROJECT_DIR . $save_dir;
	
		if (!file_exists($save_dir_full)) {
			if (!mkdir($save_dir_full)) {
				msg("common_file.getSingleUpload(): Cannot create folder $save_dir_full", 'error');
			}
		}
	
		if (is_uploaded_file($upload_file)) {
		
			if (file_exists($save_file_full)) {
			
				msg("File '$save_file_full' already exists!", 'error');
				$temp_file = "var/tmp/$safe_filename";
				msg("Saving as $temp_file", 'ok', 2);
				
				if (copy($upload_file, ONXSHOP_PROJECT_DIR . $temp_file)) {
				
					//array type for result (indicates overwrite)
					$result = array( 'filename'=> $safe_filename, 'save_dir'=> $save_dir, "temp_file"=>$temp_file);
					return $result;
				
				} else {
				
					msg("common_file.getSingleUpload(): Cannot copy $upload_file to temp location " . ONXSHOP_PROJECT_DIR . $temp_file, 'error');
					return false;
					
				}
				
			} else {
			
				if (copy($upload_file, $save_file_full)) {
			
					msg("File '$save_file_full' saved.", 'ok', 2);
					//chmod($save_file_full, 0666);
					//string type for result
					return $save_file;
			
				} else {
			
					msg("common_file.getSingleUpload(): Cannot copy $upload_file to $save_file_full", 'error');
					return false;
			
				}
				
			}
	
		} else {
			msg(" File '$upload_file' not saved.", "error");
			return false;
		}
	}
	
	/**
	 * safe filename
	 */
	 
	function nameToSafe($name, $maxlen=250) {
	
	    $name = $this->recodeUTF8ToAscii($name);
	    return preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
	}

	/**
	 * duplicated function from common_uri_mapping
	 */
	
	function recodeUTF8ToAscii($string) {
	
	    //recode to ASCII
	    if (function_exists("recode_string")) {
	        $string = recode_string("utf-8..flat", trim($string));
	    } else {
	        //msg($fullpath, 'ok', 2);
	
	        //$fullpath = iconv("UTF-8", "ASCII//TRANSLIT", trim($fullpath));
	
	        //$fullpath = utf2ascii(trim($fullpath));
	        $string = mb_convert_encoding($string,"HTML-ENTITIES","UTF-8");
	
	        $string = preg_replace('/\&(.)[^;]*;/', "\\1", $string);
	
	        //msg($fullpath, 'ok', 2);
	    }
	
	    return $string;
	}
	
	
	/**
	 * Ovewrite file
	 * 
	 */
	
	function overwriteFile($filename, $save_dir, $temp_file) {
	
		$result = $this->_overwriteFile($filename, $save_dir, $temp_file);
		
		if ($result) {
			$thumbnails_dir = ONXSHOP_PROJECT_DIR . "var/thumbnails/";
			$sizes = scandir($thumbnails_dir);
			foreach ($sizes as $size) {
				if (preg_match("/^[0-9]*x?([0-9]*)?$/", $size)) {
					
					$file_full_path = $thumbnails_dir . "$size/" . md5($save_dir . $filename);
					
					if (file_exists($file_full_path)) {
						if (unlink($file_full_path)) msg("Deleted $file_full_path", 'ok', 2);
						else msg("common_file.overwriteFile(): Cannot delete $file_full_path");
					}
				}
			}
			return $result;
		} else {
			return false;
		}
	}
	
	/**
	 * Overwrite existing file on the filesystem
	 *
	 * @param unknown_type $filename
	 * @param unknown_type $save_dir
	 * @return bool
	 */
	 
	function _overwriteFile($filename, $save_dir, $temp_file) {
	
		//$src_file_full = ONXSHOP_PROJECT_DIR . "var/tmp/" . $filename;
		$src_file_full = ONXSHOP_PROJECT_DIR . $temp_file;
		$save_file_full = ONXSHOP_PROJECT_DIR . $save_dir . $filename;
		
		copy($src_file_full, $save_file_full);

		if (is_readable($save_file_full)) return true;
		else return false;
	}
	
	/**
	 * Unlink File from database
	 *
	 * @param unknown_type $id
	 * @return bool
	 */
	 
	function unlinkFile( $id ) {
	
		$file = $this->detail($id);
		
		if ($this->delete($id)) {
			msg("File ID $id has been unlinked from the database", 'ok', 2);
			return true;
		} else {
			msg("Deletion of file ID $id from the database has failed", 'error');
			return false;
		}
	}
	
	/**
	 * remove file from the filesystem
	 *
	 * @param unknown_type $file
	 * @return bool
	 */
	 
	function deleteFile( $file ) {
	
		$file_full_path = ONXSHOP_PROJECT_DIR . $file;
		if (file_exists($file_full_path)) {
			if (is_dir($file_full_path)) {
				if (rmdir($file_full_path)) msg("Directory has been removed");
				else msg("Can't remove directory", "error");
			} else {
				//check if it's not used from other records
				$relations_list = $this->getRelations($file);
				if ($relations_list['count'] === 0) {
					msg("File $file has been deleted from database", 'ok', 2);
					if (unlink($file_full_path)) msg("File $file_full_path has been deleted from filesystem", 'ok', 2);
					else return false;
		
					msg("File $file has been deleted successfully", 'ok', 2);
					
					return true;
				} else {
					msg("Can't delete. File $file is in use.", 'error');
					return false;
				}
			}
		} else {
			msg("Can't delete. File doesn't exists.", 'error');
			return false;
		}
	}
	
	
	/**
	 * rm() -- Vigorously erase files and directories.
	 *
	 * @param $fileglob mixed If string, must be a file name (foo.txt), glob pattern (*.txt), or directory name.
	 *                        If array, must be an array of file names, glob patterns, or directories.
	 */
	 
	function rm($fileglob) {
	
	   if (is_string($fileglob)) {
	       if (is_file($fileglob)) {
	           return unlink($fileglob);
	       } else if (is_dir($fileglob)) {
	           $ok = $this->rm("$fileglob/*");
	           if (! $ok) {
	               return false;
	           }
	           return $this->unlinkRecursive($fileglob);
	       } else {
	           $matching = glob($fileglob);
	           if ($matching === false) {
	               trigger_error(sprintf('No files match supplied glob %s', $fileglob), E_USER_WARNING);
	               return false;
	           }     
	           $rcs = array_map(array($this, 'rm'), $matching);
	           if (in_array(false, $rcs)) {
	               return false;
	           }
	       }     
	   } else if (is_array($fileglob)) {
	       $rcs = array_map(array($this, 'rm'), $fileglob);
	       if (in_array(false, $rcs)) {
	           return false;
	       }
	   } else {
	       trigger_error('Param #1 must be filename or glob pattern, or array of filenames or glob patterns', E_USER_ERROR);
	       return false;
	   }
	
	   return true;
	}
	
	/**
	 * Recursively delete a directory
	 *
	 * @param string $dir Directory name
	 * @param boolean $deleteRootToo Delete specified top-level directory as well
	 */
	 
	function unlinkRecursive($dir, $deleteRootToo = true) {
	
	    if(!$dh = opendir($dir))
	    {
	        return;
	    }
	    while (false !== ($obj = readdir($dh)))
	    {
	        if($obj == '.' || $obj == '..')
	        {
	            continue;
	        }
	
	        if (!unlink($dir . '/' . $obj))
	        {
	            $this->unlinkRecursive($dir.'/'.$obj, true);
	        }
	    }
	
	    closedir($dh);
	   
	    if ($deleteRootToo)
	    {
	        rmdir($dir);
	    }
	   
	    return true;
	} 

	/**
	 * Find where the file is used
	 *
	 * @param unknown_type $file
	 * @return array
	 */
	 
	function getRelations($file) {

		require_once('models/common/common_file.php');
		$CommonFile = new common_file();
		$file_list['file'] = $CommonFile->getFileLink($file);
		
		require_once('models/common/common_image.php');
		$CommonImage = new common_image();
		$file_list['node'] = $CommonImage->getFileLink($file);
		
		require_once('models/ecommerce/ecommerce_product_image.php');
		$ProductImage = new ecommerce_product_image();
		$file_list['product'] = $ProductImage->getFileLink($file);
		
		require_once('models/ecommerce/ecommerce_product_variety_image.php');
		$ProductVarietyImage = new ecommerce_product_variety_image();
		$file_list['product_variety'] = $ProductVarietyImage->getFileLink($file);
		
		require_once('models/common/common_taxonomy_label_image.php');
		$TaxonomyImage = new common_taxonomy_label_image();
		$file_list['taxonomy'] = $TaxonomyImage->getFileLink($file);

		$file_list['count'] = count($file_list['file']) + count($file_list['node']) + count($file_list['product']) + count($file_list['product_variety']) + count($file_list['taxonomy']);

		return $file_list;
	}

	/**
	 * Get detailed file info
	 *
	 * @param unknown_type $fp
	 * @return array
	 */
	 
	function getFileInfo($fp) {
	
		$file_info['modified'] = strftime("%c", filemtime($fp));
		$file_info['mime-type'] = local_exec("file -bi " . escapeshellarg($fp));
		$file_info['type-detail'] = local_exec("file -b " . escapeshellarg($fp));
		$file_info['file_path'] = str_replace(ONXSHOP_PROJECT_DIR . 'var/files/', '', $fp);
		$file_info['size'] = $this->resize_bytes(filesize($fp));
		
		if (trim($file_info['mime-type']) == 'application/pdf') {
			$file_info['extra-detail'] = local_exec("pdfinfo " . escapeshellarg($fp));
		} else if (preg_match("/^image/", $file_info['mime-type'])) {
			$file_info['extra-detail'] = local_exec("identify " . escapeshellarg($fp));
		}
		
		//find filename
		$file_path_segments = explode('/', $fp);
		$file_info['filename'] = $file_path_segments[count($file_path_segments)-1];

		return $file_info;
	}
	
	/**
	 * encode file path
	 */
	 
	function encode_file_path($string) {
	
		return str_replace('=', '_XXX_', base64_encode($string));
	}

	/**
	 * decode file path
	 */
	 
	function decode_file_path($string) {
	
		return base64_decode(str_replace('_XXX_', '=', $string));
	}

	
	/**
	 * get file list using unix file command
	 * TODO: use PHP glob() instead
	 *
	 * @param unknown_type $directory
	 * @param unknown_type $attrs
	 * @return unknown
	 */
	 
	function getFlatArrayFromFs($directory, $attrs = '', $display_hidden = 0) {
	
		msg("calling getFlatArrayFromFs($directory)", 'ok', 3);
		if (!file_exists($directory)) {
			msg("Directory $directory does not exists!", 'error'); 
			return false;
		}
		
		$csv_list = local_exec("csv_from_fs " . escapeshellarg($directory) . " " . escapeshellarg($attrs));
	
		$csv_list = str_replace(rtrim($directory, '/'), '', $csv_list);
		$csv_array = explode("\n", $csv_list);
	
		$basename = '/' . basename($directory) . '/';
		foreach ($csv_array as $c) {
			$x = explode(';', $c);
			//dont populate base directory
			if ($x[0] != $basename) $csv[] = $x; 
		}
		
		array_pop($csv);
	
		foreach ($csv as $c) {
			$l['id'] = ltrim($c[0], '/');
			$l['parent'] = ltrim($c[1], '/');
			$l['name'] = $c[2];
			$l['title'] = $c[2];
			if (is_dir($directory . $l['id'])) $l['node_group'] = 'folder';
			else $l['node_group'] = 'file';
			$l['publish'] = 1;
			$l['size'] = $this->resize_bytes($c[3]);
			$l['modified'] = str_replace('.0000000000', '', $c[4]); //remove seconds fraction
			
			if ($display_hidden) {
				$csvf[] = $l;
			} else {
				// don't display hidden files files beginning with "."
				if (!preg_match("/^\./", $l['name'])) $csvf[] = $l;
			}
		}
		
		if (!is_array($csvf)) $csvf = array();
		
		return $csvf;
	}
	
	/**
	 * get joined list of files from ONXSHOP_DIR and ONXSHOP_PROJECT_DIR
	 *
	 * @param unknown_type $directory
	 * @return unknown
	 */
	 
	function getFlatArrayFromFsJoin ($directory) {
		
		$global_templates_dir = ONXSHOP_DIR . $directory;
		$global_templates = $this->getFlatArrayFromFs($global_templates_dir);
	
		$application_templates_dir = ONXSHOP_PROJECT_DIR . $directory;
		if (is_dir($application_templates_dir)) $application_templates = $this->getFlatArrayFromFs($application_templates_dir);
		else $application_templates = array();
	
		//merge
		$templates1 = array_merge($application_templates, $global_templates);
	
		//remove duplicates
		$ids = array();
		foreach ($templates1 as $t) {
			if (!in_array($t['id'], $ids)) {
				$ids[] = $t['id'];
				$templates[] = $t;
			}
		}
		
		return $templates;
	
	}
	
		
	/**
	 * Get File List from file system
	 * 
	 */
	 
	function getTree($directory, $type = '') {

		$list = $this->getFlatArrayFromFs($directory, $type);
		
		return $list;
	}
	
	
	/**
	 * byte format
	 *
	 * @param unknown_type $size
	 * @return unknown
	 */
	 
	function resize_bytes($size) {
	
	   $count = 0;
	   $format = array("B","KB","MB","GB","TB","PB","EB","ZB","YB");
	   while(($size/1024)>1 && $count<8)
	   {
	       $size=$size/1024;
	       $count++;
	   }
	   $return = number_format($size,0,'','.')." ".$format[$count];
	   return $return;
	}

	/**
	 * get image size
	 */
	 
	static function getImageSize($file) {
		
		if (is_readable($file)) {
		
			$size = getimagesize($file);
			
			if ($size) {
			
				$result['width'] = $size[0];
				$result['height'] = $size[1];
				$result['proportion'] = $result['width']/$result['height'];
				
				msg("Image has size {$result['width']}x{$result['height']}. Ratio of image sides is {$result['proportion']}", 'ok', 3);
				
				return $result;
			} else {
				msg("common_image.getImageSize(): $files is not an image", 'error');
				return false;
			}
		} else {
			msg("common_image.getImageSize(): $file isn't readable", 'error');
			return false;
		}
	}
}
