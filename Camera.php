<?php
require_once("gPhoto2.php");

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
	public array $availableCfg;	
	private gPhoto2 $gphoto2;

	function __construct($port='NA') {   
		$this->gphoto2=new gPhoto2();
		$this->port = ($port=='-1' ? 'NA': $port);
		$this->camera = 'No Camera Detected';		
		$this->serialNumber = 'NA';
		$this->batteryLevel = 'NA';     				
    }
	
	function __destruct() {
		unset($availableCfg);
		unset($gphoto2);
    }
	
	public function updateInfo(): bool {	
		$this->availableCfg=$this->gphoto2->getConfigList($this->port);
		$this->serialNumber=$this->gphoto2->getSerialNumber($this->port);	
		$this->updateShutterCounter();
		$this->updateBatteryLevel();
		$this->cameraModel=$this->gphoto2->getCameraModel($this->port);

		$this->manufacturer=$this->gphoto2->getManufacturer($this->port);
		return true;
	}	

	public function captureImageAndDownload(): bool {
		return $this->gphoto2->captureImageAndDownload($this->port);
	} 

	public function updateBatteryLevel(): bool { 
		if ($this->configAvailable("batterylevel")){ 
			$this->batteryLevel=$this->gphoto2->getBatteryLevel($this->port);
			return true;
		}
		return false;	
	}
	
	public function updateShutterCounter(): bool { 
		if ($this->configAvailable("batterylevel")){ 
			$this->shutterCounter=$this->gphoto2->getShutterCount($this->port);
			return true;
		}
		return false;	
	}

	public function configAvailable($config): bool { 
		foreach($this->availableCfg as $key=>$value){
			if("$config" == substr($key,-strlen($config))){
			  // $number = substr($key,strrpos($key,'_'));
			  return true;
			}
		  }
		return false;
	}	
}

class Cameras {	
	public array $cameras;

	function __construct() {        
    }

	function __destruct() {        
		unset($cameras);
    }

	public static function getCameras($gphoto2): Cameras {
		$returnObj = new Cameras();
		$returnObj->cameras=[];
		$output=$gphoto2->getCameras();
		foreach ($output as $line) {
			$camera = new Camera();
			$camera->camera = trim(explode("usb", $line)[0]);
			$camera->port = "usb:".trim(explode("usb:", $line)[1]);
			$camera->updateInfo();
			$returnObj->cameras[] = $camera;
		}
		unset($output);		
		return $returnObj;	
	}

	public static function checkTetherStatus($gphoto2, $port='-1') {
		if ($port == "-1") { 
			// check for all cameras
			$cameras = Cameras::getCameras($gphoto2)->cameras;
			foreach ($cameras as $camera) {
				$tetherStatus = new TetherStatus($camera->port);
				if($gphoto2->tetherIsRunning($camera->port)) {			
					$tetherStatus->status = "Running";
				} 
				$returnObj[]=$tetherStatus;
			}
		} else {
			// check for single camera
			$tetherStatus = new TetherStatus($port);
			if($gphoto2->tetherIsRunning($port)) {			
				$tetherStatus->status = "Running";
			} 
			$returnObj[]=$tetherStatus;
		} 

		if(!isset($returnObj)){
			$returnObj[]= new TetherStatus("NA");
		}	
		return $returnObj;
	}
	
	public static function captureImageAndDownload($gphoto2, $port='-1'): bool {
		if ($port == "-1") { 
			// capture from all cameras
			$cameras = Cameras::getCameras($gphoto2)->cameras;
			foreach ($cameras as $camera) {
				$gphoto2->captureImageAndDownload($camera->port);
			}
		} else {
			// capture from single camera
			$gphoto2->captureImageAndDownload($port);
		} 
		return true;			
	}	

	public static function tetherStart($gphoto2, $port): bool {
		if ($port == "-1") { 
			// capture from all cameras
			$cameras = Cameras::getCameras($gphoto2)->cameras;
			foreach ($cameras as $camera) {
				$gphoto2->tetherStart($camera->port);
			}
		} else {
			// capture from single camera
			$gphoto2->tetherStart($port);
		} 
		return true;
	}	

	public static function tetherStop($gphoto2, $port): bool {
		if ($port == "-1") { 
			// capture from all cameras
			$cameras = Cameras::getCameras($gphoto2)->cameras;		
			foreach ($cameras as $camera) {
				echo "$camera->port";
				$gphoto2->tetherStop($camera->port);
			}			
		} else {
			// capture from single camera
			$gphoto2->tetherStop($port);
		} 
		return true;
	}	
}
