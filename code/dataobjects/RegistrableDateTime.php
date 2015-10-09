<?php
/**
 * An instance of an event that a person can register to attend.
 */
class RegistrableDateTime extends CalendarDateTime {

	private static $db = array(
		'Capacity'      => 'Int',
		'EmailReminder' => 'Boolean',
		'RemindDays'    => 'Int'
	);

	private static $has_many = array(
		'Registrations' => 'EventRegistration'
	);

	private static $many_many = array(
		'Tickets' => 'EventTicket'
	);

	private static $many_many_extraFields = array(
		'Tickets' => array('Available' => 'Int', 'Sort' => 'Int')
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->removeByName('Capacity');
		$fields->removeByName('Registrations');
		$fields->removeByName('Tickets');
		$fields->removeByName('EmailReminder');
		$fields->removeByName('RemindDays');
		$fields->removeByName('PaymentID');
		$fields->removeByName('ReminderJobID');

		if (!$this->isInDB()) {
			$fields->push(new LiteralField(
				'RegistrationNote', '<p>You can configure registration once ' .
				'you save for the first time.</p>'
			));

			return $fields;
		}


		$fields->push(
			new GridField(
				'Tickets',
				_t('EventManagement.AVAILABLE_TICKETS', 'Available Tickets'),
				$this->Tickets(),
				GridFieldConfig::create()
					->addComponent(new GridFieldButtonRow('before'))
					->addComponent(new GridFieldToolbarHeader())
					->addComponent($add = new GridFieldAddExistingSearchButton())
					->addComponent(new GridFieldTitleHeader())
					->addComponent(new GridFieldOrderableRows('Sort'))
					->addComponent($editable = new GridFieldEditableColumns())
					->addComponent(new GridFieldDeleteAction(true))
			)
		);

		$fields->push(
			$capacity = new NumericField('Capacity', _t('EventManagement.CAPACITY', 'Capacity'))
		);

		$editable->setDisplayFields(array(
			'Title'        => array('title' => 'Title', 'field' => 'ReadonlyField'),
			'StartSummary' => 'Sales Start',
			'PriceSummary' => 'Price',
			'Available'    => array('field' => 'NumericField')
		));

		$add->setTitle(_t('EventManagement.ADD_TICKET_TYPE', 'Add Ticket Type'));
		$capacity->setDescription('Set to 0 for unlimited capacity.');

		if (class_exists('AbstractQueuedJob')) {
			if ($this->ReminderJobID && $this->ReminderJob()->StepsProcessed) {
				$fields->push(new LiteralField(
					'RemindersAlreadySent',
					'<p>Reminder emails have already been sent out.</p>'
				));
			} else {
				$fields->push(new CheckboxField(
						'EmailReminder',
						_t('EventManagement.SEND_REMINDER_EMAIL', 'Send the registered attendees a reminder email?')
					)
				);

				$fields->push(
					$remindDays = new NumericField(
						'RemindDays',
						_t('EventManagement.SEND_REMINDER', 'Send reminder')
					)
				);

				$remindDays->setDescription(_t('EventManagement.DAYS_BEFORE_EVENT', 'Days before the event starts.'));
			}
		}

		return $fields;
	}

	public function validate() {
		$result   = parent::validate();
		$currency = null;

		// Ensure that if we are sending a reminder email it has an interval
		// to send at.
		if ($this->EmailReminder && !$this->RemindDays) {
			$result->error('You must enter a time to send the reminder at.');
		}

		// Ensure that we only have tickets in one currency, since you can't
		// make a payment across currencies.
		foreach ($this->Tickets() as $ticket) {
			if ($ticket->Type == 'Price') {
				$ticketCurr = $ticket->Price->getCurrency();

				if ($ticketCurr && $currency && $ticketCurr != $currency) {
					$result->error(sprintf(
						'You cannot attach tickets with different currencies '
						. 'to one event. You have tickets in both "%s" and "%s".',
						$currency, $ticketCurr));
					return $result;
				}

				$currency = $ticketCurr;
			}
		}

		return $result;
	}

	/**
	 * If an email reminder is set, then this registers it in the queue.
	 */
	protected function onBeforeWrite() {
		parent::onBeforeWrite();

		// If an email reminder has been set then register it with the queued
		// jobs module.
		if (class_exists('AbstractQueuedJob') && $this->EmailReminder) {
			$hasJob = $this->ReminderJobID;
			$changedStart = $this->isChanged('RemindDays');

			if ($hasJob) {
				if (!$changedStart) {
					return;
				} else {
					$this->ReminderJob()->delete();
				}
			}

			$start = $this->getStartDateTime()->getTimestamp();
			$start = sfTime::subtract($start, $this->RemindDays, sfTime::DAY);

			$job = new EventReminderEmailJob($this);
			$srv = singleton('QueuedJobService');
			$this->ReminderJobID = $srv->queueJob($job, date('Y-m-d H:i:s', $start));
		}
	}

	/**
	 * Notifies the users of any changes made to the event.
	 */
	protected function onAfterWrite() {
		parent::onAfterWrite();

		// If any details have changed and the system is configured to send out
		// a notification email then do so.
		$email   = $this->Event()->EmailNotifyChanges;
		$changed = $this->getChangedFields(false, 2);
		$notify  = explode(',', $this->Event()->NotifyChangeFields);
		$notify  = array_intersect_key($changed, array_flip($notify));

		if (!$email || !$changed || !$notify) return;

		$emails = DB::query(sprintf(
			'SELECT "Email", "Name" FROM "EventRegistration" WHERE "TimeID" = %d '
			. 'AND "Status" = \'Valid\' GROUP BY "Email"',
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
			'Changed'    => $changed,
			'Link'       => $this->Link()
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
	 * Returns the overall number of places remaining at this event, TRUE if
	 * there are unlimited places or FALSE if they are all taken.
	 *
	 * @param  int $excludeId A registration ID to exclude from calculations.
	 * @return int|bool
	 */
	public function getRemainingCapacity($excludeId = null) {
		if (!$this->Capacity) return true;

		$taken = new SQLQuery();
		$taken->setSelect('SUM("Quantity")');
		$taken->setFrom('EventRegistration_Tickets');
		$taken->addLeftJoin('EventRegistration', '"EventRegistration"."ID" = "EventRegistrationID"');

		if ($excludeId) {
			$taken->addWhere('"EventRegistration"."ID" <>'. (int)$excludeId);
		}

		$taken->addWhere('"Status" <> \'Canceled\'');
		$taken->addWhere('"EventRegistration"."TimeID" ='. (int)$this->ID);
		$taken = $taken->execute()->value();

		return ($this->Capacity >= $taken) ? $this->Capacity - $taken : false;
	}

	/**
	 * @return boolean
	 */
	public function isSoldOut() {
		return (!$this->getRemainingCapacity());
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		$parts = CalendarUtil::get_date_string($this->StartDate, $this->EndDate);
		if ($parts) {
			$date = implode(' ', $parts);	
		}else {
			$date = 'No date';
		}
		
		if ($this->AllDay) {
			return sprintf(_t('EventManagement.DATEALLDAY', '%s (all day)'), $date);
		}

		if (!$this->StartTime) return $date;

		$time = $this->obj('StartTime')->Nice();
		if ($this->EndTime) $time .= ' - ' . $this->obj('EndTime')->Nice();

		return "$date $time";
	}

	/**
	 * @return DateTime
	 */
	public function getStartDateTime() {
		$dt = new DateTime($this->StartDate);

		if(!$this->AllDay) {
			if($this->StartTime) {
				$dt->modify($this->StartTime);
			}
		}

		return $dt;
	}

	/**
	 * @return string
	 */
	public function Link() {
		return Controller::join_links($this->Event()->Link(), 'details', $this->ID);
	}

}
