<?php
class SessionAdmin extends ModelAdmin {
	public static $url_segment = "sessions";
	public static $menu_title = "Sessions";
	
	public static $managed_models = array(
		"SessionStore" => array(
			"title" => "Sessions"
		)
	);
	
	public function canView($member = null) {
		$result = parent::canView($member);
		
		return $result && Config::inst()->forClass("SessionAdmin")->enabled;
	}
}
