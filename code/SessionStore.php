<?php
class SessionStore extends DataObject {
	public static $db = array(
		"SessionID" => "Varchar",
		"Data" => "Text"
	);
	
	public static $indexes = array(
		"SessionID" => true,
		"LastEdited" => true
	);
	
	public static $summary_fields = array(
		"SessionID" => "SessionID"
	);
	
	public static function bySessionID($sessionID) {
		$lifetime = (int) ini_get("session.gc_maxlifetime");
		$deathTime = new DateTime();
		$deathTime->sub(new DateInterval("PT{$lifetime}S"));
		
		return self::get()->filter(array(
			"SessionID:ExactMatch" => $sessionID,
			"LastEdited:GreaterThan" => $deathTime->format("c")
		))->sort(array(
			"Created" => "DESC"
		))->First();
	}
	
	public static function register() {
		session_set_save_handler(
			array(__CLASS__, "session_open"),
			array(__CLASS__, "session_close"),
			array(__CLASS__, "session_read"),
			array(__CLASS__, "session_write"),
			array(__CLASS__, "session_destroy"),
			array(__CLASS__, "session_gc")
		);
		register_shutdown_function('session_write_close');
	}
	
	public static function session_open($savePath, $sessionID) {
		return true;
	}
	
	public static function session_close() {
		return true;
	}
	
	public static function session_read($sessionID) {
		$session = self::bySessionID($sessionID);
		$session->extend("session_read");
		return $session?$session->Data:"";
	}
	
	public static function session_write($sessionID, $data) {
		$session = self::bySessionID($sessionID);
		if(!$session)
			$session = new self(array(
				"SessionID" => $sessionID
			));
		$session->Data = $data;
		$session->extend("session_write", $data);
		return $session->write();
	}
	
	public static function session_destroy($sessionID) {
		$session = self::bySessionID($sessionID);
		if($session)
			return $session->delete();
		
		return true;
	}
	
	public static function session_gc($lifetime) {
		$deathTime = new DateTime();
		$deathTime->sub(new DateInterval("PT{$lifetime}S"));
	
		$garbageSessions = self::get()->filter(array(
			"LastEdited:LessThan" => $deathTime->format("c")
		));
		
		foreach($garbageSessions as $session) {
			$session->delete();
		}
		
		return true;
	}
	
	public function getCMSFields() {
		$fields = new FieldList(new TabSet("Root"));
		
		$fields->addFieldsToTab("Root.Main", array(
			new ReadonlyField("SessionID"),
			$dataView = new TextareaField("DataView", "Data")
		));
		
		$dataView->setDisabled(true);
		$dataView->setColumns(100);
		$dataView->setRows(40);
		
		return $fields;
	}
	
	public function getTitle() {
		return $this->SessionID?:"Invalid session";
	}
	
	public function getDataView() {
		$data = self::unserialize($this->Data);
		return print_r($data, true);
	}
	
	public static function unserialize($session_data) {
		$method = ini_get("session.serialize_handler");
		switch ($method) {
			case "php":
				return self::unserialize_php($session_data);
				break;
			case "php_binary":
				return self::unserialize_phpbinary($session_data);
				break;
			default:
				throw new Exception("Unsupported session.serialize_handler: " . $method . ". Supported: php, php_binary");
		}
	}

	private static function unserialize_php($session_data) {
		$return_data = array();
		$offset = 0;
		while ($offset < strlen($session_data)) {
			if (!strstr(substr($session_data, $offset), "|")) {
				throw new Exception("invalid data, remaining: " . substr($session_data, $offset));
			}
			$pos = strpos($session_data, "|", $offset);
			$num = $pos - $offset;
			$varname = substr($session_data, $offset, $num);
			$offset += $num + 1;
			$data = unserialize(substr($session_data, $offset));
			$return_data[$varname] = $data;
			$offset += strlen(serialize($data));
		}
		return $return_data;
	}

	private static function unserialize_phpbinary($session_data) {
		$return_data = array();
		$offset = 0;
		while ($offset < strlen($session_data)) {
			$num = ord($session_data[$offset]);
			$offset += 1;
			$varname = substr($session_data, $offset, $num);
			$offset += $num;
			$data = unserialize(substr($session_data, $offset));
			$return_data[$varname] = $data;
			$offset += strlen(serialize($data));
		}
		return $return_data;
	}
}
