<?php
require_once("CameraRaw.php");
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

$action = '';

if (isset($_GET['action'])){
	$action = $_GET['action'];
}

// Execute $cmd in the background
function execInBackground($cmd) {
    if (substr(php_uname(), 0, 7) == "Windows"){
        pclose(popen("start /B ". $cmd, "r"));  
    } else {
        exec($cmd . " > /dev/null &");   
    }
}

function processRunning(string $process): bool
{
    if (empty(trim(shell_exec("pgrep $process")))) {
        return false;
    } else {
        return true;
    }
}

$returnObj;

try{
	switch($action){

		case "startTether":
			execInBackground ("gphoto2 --capture-tethered --keep --hook-script=\"./bin/tetherHook.sh\" --filename \"./images/capture-%Y%m%d-%H%M%S-%03n.%C\"");
			echo json_encode(true);					
			break;

		case "stopTether":
			execInBackground ("pkill -f gphoto2");
			echo json_encode(true);					
			break;

		case "checkTetherStatus":
				$returnObj = new TetherStatus();				
				if(processRunning("gphoto2")) {			
					$returnObj->status = "Tether Running";
				} else {
					$returnObj->status = "Tether Stopped";
				}
				header('Content-Type: application/json');
				echo json_encode($returnObj);
				break;
		
		case "takePicture":
			exec ("gphoto2 --capture-image-and-download --filename \"./images/capture-%Y%m%d-%H%M%S-%03n.%C\"",$output);
			echo json_encode(true);					
			break;
	
		case "deleteFile":
			$file = $_GET['file'];
			$path_parts = pathinfo('images/'.$file);
			unlink('images/'.$file);				
			unlink('images/thumbs/'.$path_parts['basename'].'.jpg');				
			header('Content-Type: application/json');
			echo json_encode(true);					
			break;
			
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
			$returnObj = new Camera();
			exec ("gphoto2 --auto-detect", $output);
			$returnObj->camera = trim(explode("usb", $output[count($output) - 1])[0]);
			header('Content-Type: application/json');
			echo json_encode($returnObj);
			break;

		case "getShutterCounter":
			$returnObj = new Camera();
			exec ("gphoto2 --get-config shuttercounter", $output);
			$returnObj->shutterCounter = trim(explode("current", $output[count($output) - 1])[0]);
			header('Content-Type: application/json');
			echo json_encode($returnObj);
			break;
			
		case "getImages":	
			$files = array();
			$imageDir = opendir('images');
			while (($file = readdir($imageDir)) !== false) {			
				if(!is_dir('images/'.$file)){
					$path_parts = pathinfo('images/'.$file);
					if (!file_exists('images/thumbs/'.$path_parts['basename'].'.jpg')){
						try { //try to extract the preview image from the RAW
							CameraRaw::extractPreview('images/'.$file, 'images/thumbs/'.$path_parts['basename'].'.jpg');
						} catch (Exception $e) { //else resize the image...
							$im = new Imagick('images/'.$file);
							$im->setImageFormat('jpg');
							$im->scaleImage(1024,0);					
							$im->writeImage('images/thumbs/'.$path_parts['basename'].'jpg');
							$im->clear();
							$im->destroy();
						}
					}				
					$returnFile = new ReturnFile();
					$returnFile->name = $path_parts['basename'];
					$returnFile->sourcePath = 'images/'.$file;
					$returnFile->thumbPath = 'images/thumbs/'.$path_parts['basename'].'.jpg';
				
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