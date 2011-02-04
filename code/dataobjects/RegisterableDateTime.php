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

	public function getDateTimeCMSFields() {
		$fields = parent::getDateTimeCMSFields();

		$fields->removeByName('Registrations');
		$fields->addFieldsToTab('Root.Registration', array(
			new CheckboxField('LimitedPlaces',
				_t('EventManagement.HASLIMPLACES', 'Does this event has limited places?')),
			new NumericField('NumPlaces',
				_t('EventManagement.NUMPLACESAVAILABLE', 'Number of places available')),
		));

		return $fields;
	}

	/**
	 * @return bool
	 */
	public function canRegister() {
		if ($this->LimitedPlaces && !$this->getRemainingPlaces()) {
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

		if ($this->LimitedPlaces) {
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
	}

	public function getRequirementsForPopup() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-livequery/jquery.livequery.js');
		Requirements::javascript('eventmanagement/javascript/RegisterableDateTimeCms.js');
	}

	/**
	 * Notifies the users of any changes made to the event.
	 */
	protected function onAfterWrite() {
		parent::onAfterWrite();

		$email   = $this->Event()->EmailNotifyChanges;
		$changed = $this->getChangedFields();
		$notify  = explode(',', $this->Event()->NotifyChangeFields);
		$notify  = array_intersect_key($changed, array_flip($notify));

		if (!$email || !$changed || !$notify) return;

		$emails = DB::query(sprintf(
			'SELECT "Email", "Name" FROM "EventRegistration" WHERE "TimeID" = %d GROUP BY "Email"',
			$this->ID
		));
		if (!$emails = $emails->map()) return;

		$changed = new DataObjectSet();
		foreach ($notify as $field => $data) {
			$changed->push(new ArrayData(array(
				'Label'  => singleton('EventRegistration')->fieldLabel($field),
				'Before' => $data['before'],
				'After'  => $data['after']
			)));
		}

		$email = new Email();
		$email->setSubject(
			sprintf('Event Details Changed For %s (%s)',
			$this->EventTitle(),
			SiteConfig::current_site_config()->Title));

		$email->setTemplate('EventRegistrationChangeEmail');
		$email->populateTemplate(array(
			'Time'       => $this,
			'SiteConfig' => SiteConfig::current_site_config(),
			'Changed'    => $changed
		));

		// We need to send the email for each registration individually.
		foreach ($emails as $address => $name) {
			$_email = clone $email;
			$_email->setTo($address);
			$_email->populateTemplate(array(
				'Name' => $name
			));

			$_email->send();
		}
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
	public function Summary() {
		$date = implode(' ', CalendarUtil::getDateString($this->StartDate, $this->EndDate));

		if ($this->is_all_day) {
			return sprintf(_t('EventManagement.DATEALLDAY', '%s (all day)'), $date);
		}

		if (!$this->StartTime) return $date;

		$time = $this->obj('StartTime')->Nice();
		if ($this->EndTime) $time .= ' - ' . $this->obj('EndTime')->Nice();

		return "$date $time";
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
	public function DetailsLink() {
		return Controller::join_links($this->Event()->Link(), 'datetime', $this->ID);
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