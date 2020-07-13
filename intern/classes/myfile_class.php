<?php


class myfile {

	private $fileHandle;
	private $mode;
	private $fullText;
	private $checkedPathName;
	private $checkedName;

	public function __construct($filename, $mode = "read") {

		$realpath = realpath( dirname($filename) );
		$subpath = str_replace(getcwd() . DIRECTORY_SEPARATOR , '', $realpath);
		$realname = preg_replace("[^a-zA-Z0-9_\-\.". DIRECTORY_SEPARATOR ."]","_",basename($filename)) ;
		$this->checkedPathName =  getcwd() . DIRECTORY_SEPARATOR . $subpath.  DIRECTORY_SEPARATOR . $realname;
		$this->checkedName = $realname;
		if ($mode == "append") {
			$this->fileHandle = fopen($this->checkedPathName , "a+");
			$this->mode = "append";
		} elseif ($mode == "readfull") {
			$this->fileHandle = NULL;
			$this->fullText = file_get_contents($this->checkedPathName);
			$this->mode = "readfull";
		} elseif ($mode == "read") {
			$this->fileHandle = fopen($this->checkedPathName , "r");
			$this->mode = "read";
		} elseif ($mode == "writefull") {
			$this->mode = "writefull";
		} else {
			return false;
		}
		
	}
	
	public function getCheckedName() {

		return $this->checkedName;
	}
	
	public function write($line) {
		if ($this->mode == "append") {
			fwrite($this->fileHandle, $line);
		} else {
			return false;
		}
	}
	
	public function writeLn($line) {
		if ($this->mode == "append") {
			fwrite($this->fileHandle, $line. "\n");
		} else {
			return false;
		}
	}
	
	public function writeCSV($data, $seperator = ";", $textsep = '"') {
		if ($this->mode == "append") {
			fputcsv($this->fileHandle, $data, $seperator, $textsep);
		} else {
			return false;
		}
	}
	
	public function readLn() {
		if ($this->mode == "read") {
			return fgets($this->fileHandle, 4048);
		} else {
			return false;
		}
	}
	
	public function getContent() {
		if ($this->mode == "readfull") {
			return $this->fullText;
		} else {
			return false;
		}
	}
	
	public function putContent( $data ) {
		if ($this->mode == "writefull") {
			file_put_contents($data);
		} else {
			return false;
		}
	}
	
	public function fileSize() {
		return filesize($this->checkedName);
	}

	public function type() {
		return mime_content_type($this->checkedName);
	}

	public function close() {
		fclose($this->fileHandle);
		$this->fileHandle = NULL;
		$this->mode = NULL;
		$this->fullText = NULL;
		$this->checkedName = NULL;
	}
	
}
