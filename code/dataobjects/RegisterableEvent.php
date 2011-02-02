<?php
/**
 * A calendar event that can people can register to attend.
 *
 * @package silverstripe-eventmanagement
 */
class RegisterableEvent extends CalendarEvent {

	public static $has_many = array(
		'DateTimes'     => 'RegisterableDateTime',
		'Registrations' => 'EventRegistration'
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$registrations = new ComplexTableField(
			$this, 'Registrations', 'EventRegistration'
		);
		$registrations = $registrations->performReadonlyTransformation();

		$fields->addFieldToTab('Root', new Tab('Registrations'), 'Behaviour');
		$fields->addFieldToTab('Root.Registrations', $registrations);

		return $fields;
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