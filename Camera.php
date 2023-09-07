<?php

/**
* A class that defines a camera.
* 
*/
class Camera {
	public string $camera;
	public string $cameraModel;
	public string $manufacturer;	
	public string $port;
	public string $shutterCounter;
	public string $serialNumber;
	public string $batteryLevel;

	function __construct() {        
    }
	
	public function updateInfo() {
		$port = $this->port;
		exec ("gphoto2 --get-config serialnumber --port " . $port, $output);
		try {
			$e=explode("Current:", $output[count($output) - 2]);
			if (isset($e[1])) $this->serialNumber = ltrim(trim($e[1]),"0");
		} catch (Exception $e) {
		}
	
		exec ("gphoto2 --get-config shuttercounter --port " . $port, $output);
		try {
			$e=explode("Current:", $output[count($output) - 2]);
			if (isset($e[1])) $this->shutterCounter = trim($e[1]);
		} catch (Exception $e) {
		}
		
		exec ("gphoto2 --get-config batterylevel --port " . $port, $output);				
		try {
			$e=explode("Current:", $output[count($output) - 2]);
			if (isset($e[1])) $this->batteryLevel = trim($e[1]);
		} catch (Exception $e) {
		}

		exec ("gphoto2 --get-config cameramodel --port " . $port, $output);
		try {
			$e=explode("Current:", $output[count($output) - 2]);
			if (isset($e[1])) $this->cameraModel = trim($e[1]);
		} catch (Exception $e) {
		}

		exec ("gphoto2 --get-config manufacturer --port " . $port, $output);
		try {
			$e=explode("Current:", $output[count($output) - 2]);
			if (isset($e[1])) $this->manufacturer = trim($e[1]);
		} catch (Exception $e) {
		}

	}	
}

class Cameras {
	public array $cameras;
	function __construct() {
        
    }

	public static function getCameras() {
		$returnObj = new Cameras();
		$returnObj->cameras=[];
		exec ("gphoto2 --auto-detect", $output);
		unset($output[0]);unset($output[1]);
		foreach ($output as $line) {
			$camera = new Camera();
			$camera->camera = trim(explode("usb", $line)[0]);
			$camera->port = "usb:".trim(explode("usb:", $line)[1]);
			$camera->updateInfo();
			$returnObj->cameras[] = $camera;
		}
		return $returnObj;	
	}	
		
}
?>