<?php

class gPhoto2 {
	public static function CaptureImageAndDownload($filePath) {
		exec ("gphoto2 --capture-image-and-download --filename \"./images/capture-%Y%m%d-%H%M%S-%03n.%C\"",$output);
		return true;
	}

}