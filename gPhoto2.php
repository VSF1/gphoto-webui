<?php

class gPhoto2 {
	public string $filePath="./images";
	public string $filePattern="capture-%Y%m%d-%H%M%S-%03n";

	function __construct() {        
    }

	private function execInBackground($cmd): void {
		if (substr(php_uname(), 0, 7) == "Windows"){
			pclose(popen("start /B ". $cmd, "r"));  
		} else {
			exec($cmd . " > /dev/null &");   
		}
	}

	public function captureImageAndDownload($port): bool {
		exec ("gphoto2 --capture-image-and-download --port $port --filename \"".$this->filePath."/".$this->filePattern.".%C\"",$output);
		if(strpos(implode(" ",$output), "*** Error: ") !== false){
			return false; // return failed command
		} else {	
			return true;
		}	
	}

	public function tetherStart($port): bool {
		$cmdTetherStart="gphoto2 --capture-tethered --keep --port $port --hook-script=\"./bin/tetherHook.sh\" --filename \"./images/capture-%Y%m%d-%H%M%S-%03n.%C\"";
		if(!processRunning($cmdTetherStart, true)) {
			execInBackground ($cmdTetherStart);
			return true;
		} else {
			return false;
		} 
	}	

	public function tetherStop($port): bool {
		$cmdTetherStart="gphoto2 --capture-tethered --keep --port $port --hook-script=\"./bin/tetherHook.sh\" --filename \"./images/capture-%Y%m%d-%H%M%S-%03n.%C\"";
		if(($pid=processRunning($cmdTetherStart, true))) {
			//echo "kill -9 $pid";
			shell_exec ("kill -9 $pid");
			return true;
		} else {
			return false;
		} 
	}	

	public function tetherIsRunning($port): bool {
		$cmdTetherStart="gphoto2 --capture-tethered --keep --port $port --hook-script=\"./bin/tetherHook.sh\" --filename \"./images/capture-%Y%m%d-%H%M%S-%03n.%C\"";
		return processRunning($cmdTetherStart, true);
	}	

	public function getCameras(): array {
		exec ("gphoto2 --auto-detect", $output);
		unset($output[0]);unset($output[1]); // remove first 2 lines
		return $output;
	}

	public function getConfigList($port): array {
		$configList = array();
		exec ("gphoto2 --list-config --port " . $port, $output);
		try {
			foreach ($output as $line) $configList[$line]=1;
		} catch (Exception $e) {
		}
		return $configList;	
	} 

	public function getConfig($config, $port): string {		
		exec ("gphoto2 --get-config ".$config. " --port " . $port, $output);
		try {
			$e=explode("Current:", $output[count($output) - 2]);
			if (isset($e[1])) return trim($e[1]);
		} catch (Exception $e) {
		}		
		return "";
	} 

	public function getSerialNumber($port){		
		$output = $this->getConfig("serialnumber",$port);
		try {			
			if (isset($output)) return ltrim($output,"0");
		} catch (Exception $e) {
		}
		return null;
	} 

	public function getShutterCount($port){		
		return $this->getConfig("shuttercount",$port);
	} 

	public function getBatteryLevel($port){		
		return $this->getConfig("batterylevel",$port);
	} 

	public function getCameraModel($port){		
		return $this->getConfig("cameramodel",$port);
	} 

	public function getManufacturer($port){		
		return $this->getConfig("manufacturer",$port);
	} 

}

global $gphoto2;
$gphoto2 = new gPhoto2();

/* End of file gPhoto2.php */