<?php
class SessionStore_Member extends DataExtension {
	public static $has_one = array(
		"Member" => "Member"
	);
	
	public function updateCMSFields(FieldList $fields) {
		$fields->addFieldsToTab("Root.Member", array(
			new ReadonlyField("MemberID", "Member ID"),
			new ReadonlyField("MemberName", "Member Name")
		));
	}
	
	public function session_write($data = null) {
		$this->owner->MemberID = Member::currentUserID();
	}
	
	public function getMemberName() {
		$member = $this->Member();
		return $member?$member->Name:null;
	}
}
