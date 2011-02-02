<?php
/**
 * Represents a registration to an event.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegistration extends DataObject {

	public static $db = array(
		'Name'  => 'Varchar(255)',
		'Email' => 'Varchar(255)'
	);

	public static $has_one = array(
		'Time'   => 'RegisterableDateTime',
		'Event'  => 'RegisterableEvent',
		'Member' => 'Member'
	);

	/**
	 * @return FieldSet
	 */
	public function getRegistrationFields() {
		$fields = new FieldSet(
			new TextField('Name', _t('EventManager.YOURNAME', 'Your Name')),
			new EmailField('Email', _t('EventManager.EMAILADDR', 'Email Address'))
		);

		$this->extend('updateRegistrationFields', $fields);
		return $fields;
	}

	/**
	 * @return Validator
	 */
	public function getRegistrationValidator() {
		$validator = new RequiredFields('Name', 'Email');
		$this->extend('updateRegistrationValidator', $validator);

		return $validator;
	}

}