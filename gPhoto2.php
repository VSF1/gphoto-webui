<?php

define ("GPHOTO2_CAPTURE_AND_DOWNLOAD","capture-image-and-download");
define ("GPHOTO2_CAPTURE_TETHERED","capture-tethered");

class gPhoto2 {
	private string $downloadFilePath="./images";	
	private string $filePattern="capture-%Y%m%d-%H%M%S-%03n";
#	private string $capture_cmd="gphoto2 --@CMD --keep --port @PORT --filename \"@PATH/@PATTERN.%C\" @HOOKSCRIPT";
	private string $capture_cmd="./bin/tetherTranferUsb.sh @PORT \"@PATH\" \"@PATTERN.%C\"";
	private string $hook_script="--hook-script=\"./bin/tetherHook.sh\"";

	function __construct($config=array()) {
		if (isset($config['gphoto2_download_path'])){ 
			$this->downloadFilePath=$config['gphoto2_download_path'];
		}
		if (isset($config['gphoto2_file_pattern'])){ 
			$this->filePattern=$config['gphoto2_file_pattern'];
		}		
    }

	// private
	/*
	private function execInBackground($cmd): void {
		if (substr(php_uname(), 0, 7) == "Windows"){
			pclose(popen("start /B ". $cmd, "r"));  
		} else {
			exec($cmd . " > /dev/null &");   
		}
	}*/

	public function captureImageAndDownload($port): bool {
		$cmd=$this->capture_cmd;
		$cmd=str_replace("@CMD", GPHOTO2_CAPTURE_AND_DOWNLOAD, $cmd);
		$cmd=str_replace("@PORT", $port, $cmd);
		$cmd=str_replace("@PATH", $this->downloadFilePath, $cmd);
		$cmd=str_replace("@PATTERN", $this->filePattern, $cmd);
		$cmd=str_replace("@HOOKSCRIPT", "", $cmd);
		exec ($cmd, $output);
		#exec ("gphoto2 --capture-image-and-download --port $port --filename \"".$this->downloadFilePath."/".$this->filePattern.".%C\"",$output);
		if(strpos(implode(" ",$output), "*** Error: ") !== false){
			return false; // return failed command
		} else {	
			return true;
		}	
	}

	public function tetherStart($port): bool {
		$cmd=$this->capture_cmd;
		$cmd=str_replace("@CMD", GPHOTO2_CAPTURE_TETHERED, $cmd);
		$cmd=str_replace("@PORT", $port, $cmd);
		$cmd=str_replace("@PATH", $this->downloadFilePath, $cmd);
		$cmd=str_replace("@PATTERN", $this->filePattern, $cmd);	
		$cmd=str_replace("@HOOKSCRIPT", $this->hook_script, $cmd);
		#$cmdTetherStart="gphoto2 --capture-tethered --keep --port $port --hook-script=\"./bin/tetherHook.sh\" --filename \"".$this->filePath."/".$this->filePattern.".%C\"";
		if(!processRunning($cmd, true)) {
			execInBackground ($cmd);
			return true;
		} else {
			return false;
		} 
	}	

	public function tetherStop($port): bool {
		$cmd=$this->capture_cmd;
		$cmd=str_replace("@CMD", GPHOTO2_CAPTURE_TETHERED, $cmd);
		$cmd=str_replace("@PORT", $port, $cmd);
		$cmd=str_replace("@PATH", $this->downloadFilePath, $cmd);
		$cmd=str_replace("@PATTERN", $this->filePattern, $cmd);					
		$cmd=str_replace("@HOOKSCRIPT", $this->hook_script, $cmd);
		if(($pid=processRunning($cmd, true))) {			
			shell_exec ("kill -9 $pid");
			return true;
		} else {
			return false;
		} 
	}	

	public function tetherIsRunning($port): bool {
		$cmd=$this->capture_cmd;
		$cmd=str_replace("@CMD", GPHOTO2_CAPTURE_TETHERED, $cmd);
		$cmd=str_replace("@PORT", $port, $cmd);
		$cmd=str_replace("@PATH", $this->downloadFilePath, $cmd);
		$cmd=str_replace("@PATTERN", $this->filePattern, $cmd);	
		$cmd=str_replace("@HOOKSCRIPT", $this->hook_script, $cmd);			
		return processRunning($cmd, true);
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
$gphoto2 = new gPhoto2($config);

/* End of file gPhoto2.php */