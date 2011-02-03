<?php
/**
 * An invitation sent out via email to invite a user to an event.
 *
 * @package silverstripe-eventmanagement
 */
class EventInvitation extends DataObject {

	public static $db = array(
		'Name'  => 'Varchar(255)',
		'Email' => 'Varchar(255)'
	);

	public static $has_one = array(
		'Event' => 'RegisterableEvent',
		'Time'  => 'RegisterableDateTime'
	);

	public static $summary_fields = array(
		'Name'        => 'Name',
		'Email'       => 'Email',
		'Registered'  => 'Registered',
		'EventTitle'  => 'Event',
		'TimeSummary' => 'Time(s)'
	);

	public function Registered() {
		$rego = DataObject::get_one('EventRegistration', sprintf(
			'"Email" = \'%s\' AND "TimeID" = %d',
			Convert::raw2sql($this->Email), $this->TimeID
		));

		return $rego ? _t('EventRegistration.YES', 'Yes') : _t('EventRegistration.NO', 'No');
	}

	public function EventTitle() {
		return $this->Time()->EventTitle();
	}

	public function TimeSummary() {
		return $this->Time()->Summary();
	}

	/**
	 * @return string
	 */
	public function RegisterLink() {
		return Director::absoluteURL(Controller::join_links(
			$this->Event()->Link(), 'register', $this->TimeID,
			'?name=' . urlencode($this->Name), '?email=' . urlencode($this->Email)
		));
	}

}