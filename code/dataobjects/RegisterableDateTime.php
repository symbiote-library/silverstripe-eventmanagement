<?php
/**
 * An instance of an event that a person can register to attend.
 *
 * @package silverstripe-eventmanagement
 */
class RegisterableDateTime extends CalendarDateTime {

	public static $db = array(
		'LimitedPlaces' => 'Boolean',
		'NumPlaces'     => 'Int'
	);

	public static $has_many = array(
		'Registrations' => 'EventRegistration'
	);

	/**
	 * @return bool
	 */
	public function canRegister() {
		if ($this->LimitedPlaces && !$this->getRemainingPlaces()) {
			return false;
		}

		if ($this->Event()->RequireLoggedIn && !Member::currentUserID()) {
			return false;
		}

		return true;
	}

	/**
	 * @return FieldSet
	 */
	public function getRegistrationFields() {
		$fields = new FieldSet(
			new TextField('Name', _t('EventManagement.YOURNAME', 'Your name')),
			new EmailField('Email', _t('EventManagement.EMAILADDR', 'Email address'))
		);

		if ($this->Event()->LimitedPlaces) {
			$entity    = _t('EventManagement.NPLACESREMAINING', 'There are currently %d places remaining.');
			$remaining = '<p id="PlacesRemaining">' . sprintf($entity, $this->getRemainingPlaces()) . '</p>';

			$fields->insertBefore(new LiteralField('PlacesRemaining', $remaining), 'Name');
		}

		if ($this->Event()->MultiplePlaces) {
			$title = _t('EventManagement.NUMPLACES', 'Number of places');

			if ($max = $this->Event()->MaxPlaces) {
				$range = ArrayLib::valuekey(range(1, $max));
				$fields->push(new DropdownField('Places', $title, $range, 1));
			} else {
				$fields->push(new NumericField('Places', $title, 1));
			}
		}

		if ($member = Member::currentUser()) {
			$fields->dataFieldByName('Name')->setValue($member->getName());
			$fields->makeFieldReadonly('Name');

			$fields->dataFieldByName('Email')->setValue($member->Email);
			$fields->makeFieldReadonly('Email');
		}

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

	public function extendTable() {
		$this->addTableTitles(array(
			'RemainingPlacesNice' => _t('EventManager.PLACESREMAINING', 'Places Remaining')
		));

		$this->addPopupFields(array(
			new HeaderField('LimitedPlacesHeader',
				_t('EventManagement.LIMPLACES', 'Limited Places')),
			new CheckboxField('LimitedPlaces',
				_t('EventManagement.HASLIMPLACES', 'Does this event has limited places?')),
			new NumericField('NumPlaces',
				_t('EventManagement.NUMPLACESAVAILABLE', 'Number of places available')),
		));
	}

	public function getRequirementsForPopup() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-livequery/jquery.livequery.js');
		Requirements::javascript('eventmanagement/javascript/RegisterableDateTimeCms.js');
	}

	/**
	 * @return int
	 */
	public function getRemainingPlaces() {
		$avail = $this->NumPlaces;

		if ($this->Event()->MultiplePlaces) {
			$taken = DB::query(sprintf(
				'SELECT SUM("Places") FROM "EventRegistration" WHERE "TimeID" = %d AND "Confirmed" = 1',
				$this->ID
			));
		} else {
			$taken = DB::query(sprintf(
				'SELECT COUNT(*) FROM "EventRegistration" WHERE "TimeID" = %d AND "Confirmed" = 1',
				$this->ID
			));
		}

		return $avail - $taken->value();
	}

	/**
	 * @return string
	 */
	public function RemainingPlacesNice() {
		if ($this->LimitedPlaces) {
			return $this->getRemainingPlaces();
		} else {
			return _t('EventManagement.NOTLIMITED', '(Not limited)');
		}
	}

	/**
	 * @return string
	 */
	public function RegisterLink() {
		return Controller::join_links($this->Event()->Link(), 'register', $this->ID);
	}

	/**
	 * @return string
	 */
	public function UnregisterLink() {
		return Controller::join_links($this->Event()->Link(), 'unregister', $this->ID);
	}

}