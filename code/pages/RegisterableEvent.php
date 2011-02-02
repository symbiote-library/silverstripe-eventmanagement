<?php
/**
 * A calendar event that can people can register to attend.
 *
 * @package silverstripe-eventmanagement
 */
class RegisterableEvent extends CalendarEvent {

	public static $db = array(
		'MultiplePlaces' => 'Boolean',
		'MaxPlaces'      => 'Int'
	);

	public static $has_many = array(
		'DateTimes'     => 'RegisterableDateTime',
		'Registrations' => 'EventRegistration'
	);

	public function getCMSFields() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-livequery/jquery.livequery.js');
		Requirements::javascript('eventmanagement/javascript/RegisterableEventCms.js');

		$fields = parent::getCMSFields();

		$fields->addFieldsToTab('Root.Content.Registration', array(
			new HeaderField('MultiplePlacesHeader', $this->fieldLabel('MultiplePlacesHeader')),
			new CheckboxField('MultiplePlaces', $this->fieldLabel('MultiplePlaces')),
			new NumericField('MaxPlaces', $this->fieldLabel('MaxPlaces'))
		));

		// Only show the places column if multiple places are enabled.
		$regFields = singleton('EventRegistration')->summaryFields();
		if (!$this->MultiplePlaces) unset($regFields['Places']);

		$registrations = new ComplexTableField(
			$this, 'Registrations', 'EventRegistration', $regFields
		);
		$registrations->setPermissions(array('show', 'print', 'export'));

		$fields->addFieldToTab('Root', new Tab('Registrations'), 'Behaviour');
		$fields->addFieldsToTab('Root.Registrations', array(
			new HeaderField('RegistrationsHeader', $this->fieldLabel('Registrations')),
			$registrations
		));

		return $fields;
	}

	public function fieldLabels() {
		return array_merge(parent::fieldLabels(), array(
			'Registrations' => _t('EventManagement.REGISTATIONS', 'Registrations'),
			'MultiplePlacesHeader' => _t('EventManagement.MULTIPLACES', 'Multiple Places'),
			'MultiplePlaces' => _t('EventManagement.ALLOWMULTIPLACES', 'Allow atendees to register for multiple places?'),
			'MaxPlaces' => _t('EventManagement.MAXPLACES', 'Maximum places selectable (0 for any number)')
		));
	}

}

/**
 * @package silverstripe-eventmanagement
 */
class RegisterableEvent_Controller extends CalendarEvent_Controller {

	public static $allowed_actions = array(
		'register'
	);

	/**
	 * Returns the controller allowing a person to register for an event.
	 *
	 * @param  SS_HTTPRequest $request
	 * @return EventRegistrationController
	 */
	public function register($request) {
		$id = $request->param('ID');

		$filter = sprintf(
			'"CalendarDateTime"."ID" = %d AND "EventID" = %d', $id, $this->ID
		);
		$time = $this->DateTimes($filter, null, null, 1);

		if (!count($time)) {
			$this->httpError(404, 'The requested event time could not be found.');
		}

		$request->shift(2);
		$request->shiftAllParams();
		$request->shiftAllParams();

		return new EventRegistrationController($this, $time->First());
	}

}