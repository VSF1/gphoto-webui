<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

require_once("CameraRaw.php");
require_once("gPhoto2.php");
require_once("Camera.php");
require_once("ReturnFile.php");
require_once("TetherStatus.php");
require_once("IniFile.php");

// read setup
$config = IniFile::read("cfg/config.ini");

//time gphoto2 --quiet --capture-image-and-download --filename "./images/capture-%Y%m%d-%H%M%S-%03n.%C"
//exec ("gphoto2 --set-config uilock=1",$output);
//echo join("\n",$output);
//exec ("gphoto2  --capture-image",$output);
//echo join("\n",$output);
//exec ("gphoto2 --set-config uilock=1",$output);
//echo join("\n",$output);

$action = '';
$port = '';

if (isset($_GET['action'])){
	$action = $_GET['action'];
}

if (isset($_GET['port'])){
	//$port = "usb:". $_GET['port'];
	$port = $_GET['port'];
}

$cmdTetherStart="gphoto2 --capture-tethered --keep --port $port --hook-script=\"./bin/tetherHook.sh\" --filename \"./images/capture-%Y%m%d-%H%M%S-%03n.%C\"";
$cmdTakePicture="gphoto2 --capture-image-and-download --port $port --filename \"./images/capture-%Y%m%d-%H%M%S-%03n.%C\"";
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
	$result=trim(shell_exec($cmd));
    if (empty($result)) {
        return false;
    } else {
        return $result;
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
			$path_parts = pathinfo('images/'.$file);
			unlink('images/'.$file);				
			unlink('images/thumbs/'.$path_parts['basename'].'.jpg');				
			header('Content-Type: application/json');
			echo json_encode(true);					
			break;
			
		case "getFile":
		case "getImage":	
			$file = $_GET['file'];
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="'.$file.'"');
			header('Content-Length: '.filesize('images/'.$file));
			$fp = fopen('images/'.$file, 'rb');
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
			$imageDir = opendir('images');
			while (($file = readdir($imageDir)) !== false) {			
				if(!is_dir('images/'.$file)){			
					if (!file_exists('images/'.$file.'.md5')) {
						exec('md5sum images/'.$file.' > images/'.$file.'.md5');
					}			
					$path_parts = pathinfo('images/'.$file);
					if($path_parts["extension"] != "md5") {
						echo readMD5('images/'.$file.'.md5').' '.$file."\r\n";
					}
				}
			}
			closedir($imageDir);
			break;
		case "getFirstImageName":
			$imageDir = opendir('images');
			while (($file = readdir($imageDir)) !== false) {			
				if(!is_dir('images/'.$file)){			
					if (!file_exists('images/'.$file.'.md5')) {
						exec('md5sum images/'.$file.' > images/'.$file.'.md5');
					}			
					$path_parts = pathinfo('images/'.$file);
					if($path_parts["extension"] != "md5") {
						echo readMD5('images/'.$file.'.md5').' '.$file."\r\n";
						break;
					}
				}
			}
			closedir($imageDir);
			break;
					
		case "getImages":	
			$files = array();
			$imageDir = opendir('images');
			while (($file = readdir($imageDir)) !== false) {			
				if(!is_dir('images/'.$file) && CameraRaw::isImageFile('images/'.$file)){
					$path_parts = pathinfo('images/'.$file);
					/*										
					if (!file_exists('images/'.$file.'.md5')) {
						exec('md5sum images/'.$file.' > images/'.$file.'.md5');
					}
					if (!file_exists('images/fs/'.$path_parts['basename'].'.jpg')){
						// create a full size version
						try { 
							CameraRaw::generateImageJPG('images/'.$file, 'images/fs/'.$path_parts['basename'].'.jpg');
						} catch (Exception $e) {
							echo get_current_user() . '    ';
							echo $e;
							die;
						}
					}				
					if (!file_exists('images/thumbs/'.$path_parts['basename'].'.jpg')){
						try { //try to extract the preview image from the RAW
							CameraRaw::extractPreview('images/'.$file, 'images/thumbs/'.$path_parts['basename'].'.jpg');
						} catch (Exception $e) { //else resize the image...
							$im = new \Gmagick('images/'.$file);
							$im->setImageFormat('jpg');
							$im->scaleImage(400,0);					
							$im->writeImage('images/thumbs/'.$path_parts['basename'].'.jpg');
							$im->clear();
							$im->destroy();
						}
					}*/				
					$returnFile = new ReturnFile();
					$returnFile->name = $path_parts['basename'];
					$returnFile->sourcePath = 'images/'.$file;
					$returnFile->thumbPath = 'images/thumbs/'.$path_parts['filename'].'.jpg';
					$returnFile->largePath = 'images/fs/'.$path_parts['filename'].'.jpg';
					//$returnFile->md5 = readMD5('images/'.$file.'.md5');
					array_push($files,$returnFile);
				
					unset($returnFile);
				}
			}
			closedir($imageDir);
			$returnObj = $files;
			header('Content-Type: application/json');
			echo json_encode($returnObj);
			break;
		default:
			break;
	}
} catch (Exception $e) { //else resize the image...
	
}

?>
