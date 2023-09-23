<?php

/**
* A class that defines a Tether Status.
* 
*/
class TetherStatus {
	public string $status;
	public string $port;

	function __construct($port="NA", $status="Stopped") { 
		$this->port = $port;
		$this->status = $status;
    }

	function __destruct() {        
    }
}
?>