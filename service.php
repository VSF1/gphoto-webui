<?php
require_once("config.php");
require_once("CameraRaw.php");
require_once("gPhoto2.php");
require_once("Camera.php");
require_once("ReturnFile.php");
require_once("TetherStatus.php");

//time gphoto2 --quiet --capture-image-and-download --filename "./images/capture-%Y%m%d-%H%M%S-%03n.%C"
//exec ("gphoto2 --set-config uilock=1",$output);
//echo join("\n",$output);
//exec ("gphoto2  --capture-image",$output);
//echo join("\n",$output);
//exec ("gphoto2 --set-config uilock=1",$output);
//echo join("\n",$output);

$basepath = '/srv/shifucc';
$action = '';
$port = '';

if (isset($_GET['action'])){
	$action = $_GET['action'];
}

if (isset($_GET['port'])){
	//$port = "usb:". $_GET['port'];
	$port = $_GET['port'];
}

// Execute $cmd in the background
function execInBackground($cmd) {
    if (substr(php_uname(), 0, 7) == "Windows") {
        pclose(popen("start /B ". $cmd, "r"));  
    } else {
        exec($cmd . " > /dev/null &");   
    }
}

function processRunning(string $process, bool $isfullcmd=false)
{
	if ($isfullcmd) {
		$cmd="pgrep -f \"$process\"";
	} else { 	
		$cmd="pgrep $process";
	} 
	$result=shell_exec($cmd);
    if (empty($result)) {
        return false;
    } else {
        return trim($result);
    }
}

function readMD5(string $file): string 
{
	$file_to_read = fopen($file, 'r');
	if($file_to_read !== FALSE){
    	while(($data = fgetcsv($file_to_read, 100, ',')) !== FALSE){
        	return explode(" ", $data[0])[0];
    	}    	
    	fclose($file_to_read);
	}
	return ''; 
}

function getDirContents($path) {
	$directory = new RecursiveDirectoryIterator($path, FilesystemIterator::FOLLOW_SYMLINKS);  
	$filter = new \RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
		// Skip hidden files and directories.  
		if ($current->getFilename()[0] === '.') {
			return FALSE;
		} else if ($current->isDir()) {
			// Only recurse into intended subdirectories.
			# return $current->getFilename() === 'wanted_dirname';
			if ($current->getFilename() === 'previews') return FALSE;
			else if ($current->getFilename() === 'fs') return FALSE;
			else return TRUE;
		} else if ($current->isFile()) {
			return TRUE; 
		} else {
			// Only consume files of interest.
			#return strpos($current->getFilename(), 'wanted_filename') === 0;
			return FALSE;
		}
	});

	$iterator = new RecursiveIteratorIterator($filter);
	$files = array();
	foreach ($iterator as $info) 
		$files[] = $info->getPathname();
	return $files;
}

function createPath(string $path): bool {
	$parts = pathinfo($path);
	if (!file_exists($parts['dirname'])) {
		mkdir($parts['dirname'], 0777, true);
	}
	return true;
} 

$returnObj;

try{
	switch($action){

		case "startTether":
			$returnObj = Cameras::tetherStart($gphoto2, $port);
			header('Content-Type: application/json');
			echo json_encode($returnObj);	
			break;

		case "stopTether":			
			$returnObj = Cameras::tetherStop($gphoto2, $port);
			header('Content-Type: application/json');
			echo json_encode($returnObj);
			break;

		case "checkTetherStatus":
			$returnObj = Cameras::checkTetherStatus($gphoto2, $port);
			header('Content-Type: application/json');
			echo json_encode($returnObj);
			break;
		
		case "takePicture":
			$returnObj = Cameras::captureImageAndDownload($gphoto2, $port);
			header('Content-Type: application/json');
			echo json_encode($returnObj);
			break;
	
		case "deleteFile":
			$file = $_GET['file'];
			$path_parts = pathinfo($basepath.'/'.$file);
			unlink($basepath.'/'.$file);
			unlink($basepath.'/.previews/'.$file.'-preview.jpg');
			unlink($basepath.'/.fs/'.$file.'-fs.jpg');
			header('Content-Type: application/json');
			echo json_encode(true);
			break;
			
		case "getFile":
		case "getImage":	
			$file = $_GET['file'];
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="'.$file.'"');
			header('Content-Length: '.filesize($basepath.'/'.$file));
			$fp = fopen($basepath.'/'.$file, 'rb');
			fpassthru($fp);
			exit;
			break;

		case "getCamera":
			$cameras = Cameras::getCameras($gphoto2)->cameras;
			if(!isset($cameras) || !isset($cameras[0])) {
				$cameras[] = new Camera();
				/* Simulate a second camera
				$camera = new Camera();
				$camera->port = 'NA1';
				$cameras[] = $camera;*/
			}
			$returnObj=$cameras;
			header('Content-Type: application/json');
			echo json_encode($returnObj);
			break;

		case "getCameras":
			    $returnObj = Cameras::getCameras($gphoto2);
				header('Content-Type: application/json');
				echo json_encode($returnObj);
				break;
	
		case "getSerialNumber":
			$returnObj = new Camera();
			exec ("gphoto2 --get-config serialnumber", $output);				
			$returnObj->serialNumber = trim(explode("Current:", $output[count($output) - 2])[1]);
			header('Content-Type: application/json');
			echo json_encode($returnObj);
			break;

		case "getBatteryLevel":
			$returnObj = new Camera($port);
			exec ("gphoto2 --get-config batterylevel", $output);				
			$returnObj->batteryLevel = trim(explode("Current:", $output[count($output) - 2])[1]);
			header('Content-Type: application/json');
			echo json_encode($returnObj);
			break;
	
		case "getShutterCounter":
			$returnObj = new Camera();
			exec ("gphoto2 --get-config shuttercounter", $output);
			$returnObj->shutterCounter = trim(explode("Current:", $output[count($output) - 2])[1]);
			header('Content-Type: application/json');
			echo json_encode($returnObj);
			break;
		case "getImagesList":
			$files = getDirContents($basepath);
			foreach ($files as $file) {
				if(!is_dir($basepath.'/'.$file)){
					if (!file_exists($basepath.'/'.$file.'.md5')) {
						exec('md5sum '.$basepath.'/'.$file.' > '.$basepath.'/'.$file.'.md5');
					}			
					$path_parts = pathinfo($basepath.'/'.$file);
					if($path_parts["extension"] != "md5") {
						echo readMD5($basepath.'/'.$file.'.md5').' '.$file."\r\n";
					}
				}
			}
			break;
		case "getFirstImageName":
			$imageDir = opendir($basepath);
			while (($file = readdir($imageDir)) !== false) {			
				if(!is_dir($basepath.'/'.$file)){			
					if (!file_exists($basepath.'/'.$file.'.md5')) {
						exec('md5sum '.$basepath.'/'.$file.' > '.$basepath.'/'.$file.'.md5');
					}			
					$path_parts = pathinfo($basepath.'/'.$file);
					if($path_parts["extension"] != "md5") {
						echo readMD5($basepath.'/'.$file.'.md5').' '.$file."\r\n";
						break;
					}
				}
			}
			closedir($imageDir);
			break;
					
		case "getImages":	
			$ret_files = array();
			$files = getDirContents($basepath);
			foreach($files as $file_path) {				
				$file = substr($file_path, strlen($basepath)+1);				
				$basefile_full_path = $basepath.'/'.$file;
				$fullsizefile_rel_path = 'fs/'.$file.'-fs.jpg';
				$fullsizefile_full_path = $basepath.'/'.$fullsizefile_rel_path;
				$previewfile_rel_path = 'previews/'.$file.'-preview.jpg';
				$previewfile_full_path = $basepath.'/'.$previewfile_rel_path;
				if(!is_dir($basefile_full_path) && CameraRaw::isImageFile($basefile_full_path)){
					$path_parts = pathinfo($file_path);
					#if (!file_exists($basepath.'/'.$file.'.md5')) {
					#	exec('md5sum '.$basepath.'/'.$file.' > images/'.$file.'.md5');
					#}
					if (!file_exists($fullsizefile_full_path)){
						createPath($fullsizefile_full_path);
						// create a full size version
						try { 
							echo $basefile_full_path . "<br/>";
							echo "CameraRaw::generateImageJPG($basefile_full_path, $fullsizefile_full_path);";
							CameraRaw::generateImageJPG($basefile_full_path, $fullsizefile_full_path);
						} catch (Exception $e) {
							echo get_current_user() . '    ';
							echo $e;
							die;
						}
					}				
					if (!file_exists($previewfile_full_path)){
						createPath($previewfile_full_path);
						try { //try to extract the preview image from the RAW
							echo "CameraRaw::extractPreview($basefile_full_path, $previewfile_full_path);";
							CameraRaw::extractPreview($basefile_full_path, $previewfile_full_path);
						} catch (Exception $e) { //else resize the image...
							// sudo apt install graphicsmagick php-gmagick
							$im = new \Gmagick($basefile_full_path);
							$im->setImageFormat('jpg');
							$im->scaleImage(1500,0);					
							$im->writeImage($previewfile_full_path);
							$im->clear();
							$im->destroy();
						}
					}
					$returnFile = new ReturnFile();
					$returnFile->name = $path_parts['basename'];
					$returnFile->sourcePath = 'images/'.$file;
					$returnFile->thumbPath = 'images/'.$previewfile_rel_path;
					$returnFile->largePath = 'images/'.$fullsizefile_rel_path;
					//$returnFile->md5 = readMD5('images/'.$file.'.md5');
					array_push($ret_files, $returnFile);
				
					unset($returnFile);
				}
			}
			$returnObj = $ret_files;
			header('Content-Type: application/json');
			echo json_encode($returnObj);
			break;
		default:
			echo "Unknown command";
			break;
	}
} catch (Exception $e) { //else resize the image...
	
}

?>
