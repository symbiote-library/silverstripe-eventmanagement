<?php
/**
 * A calendar event that can people can register to attend.
 *
 * @package silverstripe-eventmanagement
 */
class RegisterableEvent extends CalendarEvent {

	public static $db = array(
		'OneRegPerEmail'        => 'Boolean',
		'RegEmailConfirm'       => 'Boolean',
		'AfterConfirmTitle'     => 'Varchar(255)',
		'AfterConfirmContent'   => 'HTMLText',
		'UnRegEmailConfirm'     => 'Boolean',
		'AfterConfUnregTitle'   => 'Varchar(255)',
		'AfterConfUnregContent' => 'HTMLText',
		'AfterConfirmContent'   => 'HTMLText',
		'EmailNotifyChanges'    => 'Boolean',
		'NotifyChangeFields'    => 'Text',
		'RequireLoggedIn'       => 'Boolean',
		'AfterRegTitle'         => 'Varchar(255)',
		'AfterRegContent'       => 'HTMLText',
		'AfterUnregTitle'       => 'Varchar(255)',
		'AfterUnregContent'     => 'HTMLText'
	);

	public static $has_many = array(
		'Tickets'       => 'EventTicket',
		'DateTimes'     => 'RegisterableDateTime',
		'Registrations' => 'EventRegistration'
	);

	public static $defaults = array(
		'AfterRegTitle'         => 'Thanks For Registering',
		'AfterRegContent'       => '<p>Thanks for registering! We look forward to seeing you.</p>',
		'AfterConfirmTitle'     => 'Registration Confirmed',
		'AfterConfirmContent'   => '<p>Thanks! Your registration has been confirmed</p>',
		'AfterUnregTitle'       => 'Registration Canceled',
		'AfterUnregContent'     => '<p>Your registration has been canceled.</p>',
		'AfterConfUnregTitle'   => 'Un-Registration Confirmed',
		'AfterConfUnregContent' => '<p>Your registration has been canceled.</p>',
		'NotifyChangeFields'    => 'StartDate,EndDate,StartTime,EndTime'
	);

	public function getCMSFields() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-livequery/jquery.livequery.js');
		Requirements::javascript('eventmanagement/javascript/RegisterableEventCms.js');

		$fields = parent::getCMSFields();

		$fields->addFieldsToTab('Root.Content.Tickets', array(
			new HeaderField('TicketTypesHeader', $this->fieldLabel('TicketTypesHeader')),
			new ComplexTableField($this, 'Tickets', 'EventTicket')
		));

		$changeFields = singleton('RegisterableDateTime')->fieldLabels(false);
		$fields->addFieldsToTab('Root.Content.Registration', array(
			new HeaderField('EmailSettingsHeader', $this->fieldLabel('EmailSettingsHeader')),
			new CheckboxField('OneRegPerEmail', $this->fieldLabel('OneRegPerEmail')),
			new CheckboxField('RegEmailConfirm', $this->fieldLabel('RegEmailConfirm')),
			new TextField('AfterConfirmTitle', $this->fieldLabel('AfterConfirmTitle')),
			new HtmlEditorField('AfterConfirmContent', $this->fieldLabel('AfterConfirmContent'), 5),
			new CheckboxField('UnRegEmailConfirm', $this->fieldLabel('UnRegEmailConfirm')),
			new TextField('AfterConfUnregTitle', $this->fieldLabel('AfterConfUnregTitle')),
			new HtmlEditorField('AfterConfUnregContent', $this->fieldLabel('AfterConfUnregContent'), 5),
			new CheckboxField('EmailNotifyChanges', $this->fieldLabel('EmailNotifyChanges')),
			new CheckboxSetField('NotifyChangeFields', $this->fieldLabel('NotifyChangeFields'), $changeFields),
			new HeaderField('MemberSettingsHeader', $this->fieldLabel('MemberSettingsHeader')),
			new CheckboxField('RequireLoggedIn', $this->fieldLabel('RequireLoggedIn'))
		));

		$fields->addFieldsToTab('Root.Content.AfterRegistration', array(
			new TextField('AfterRegTitle', $this->fieldLabel('AfterRegTitle')),
			new HtmlEditorField('AfterRegContent', $this->fieldLabel('AfterRegContent'))
		));

		$fields->addFieldsToTab('Root.Content.AfterUnregistration', array(
			new TextField('AfterUnregTitle', $this->fieldLabel('AfterUnregTitle')),
			new HtmlEditorField('AfterUnregContent', $this->fieldLabel('AfterUnregContent'))
		));

		// Only show the places column if multiple places are enabled.
		$regFields = singleton('EventRegistration')->summaryFields();

		$registrations = new ComplexTableField(
			$this, 'Registrations', 'EventRegistration', $regFields, null, '"Confirmed" = 1'
		);
		$registrations->setPermissions(array('show', 'print', 'export'));

		$fields->addFieldToTab('Root', new Tab('Registrations'), 'Behaviour');
		$fields->addFieldsToTab('Root.Registrations', array(
			new HeaderField('RegistrationsHeader', $this->fieldLabel('Registrations')),
			$registrations
		));

		if ($this->RegEmailConfirm) {
			$count = DB::query(sprintf(
				'SELECT COUNT(*) FROM "EventRegistration" WHERE "EventID" = %d AND "Confirmed" = 0',
				$this->ID
			));

			$unconfirmed = _t(
				'EventManagement.NUMUNCONFIRMEDREG',
				'There are %d unconfirmed registrations.');

			$fields->addFieldToTab('Root.Registrations', new LiteralField(
				'UnconfirmedRegistrations', sprintf("<p>$unconfirmed</p>", $count->value())
			));
		}

		// Add a tab allowing admins to invite people from, as well as view
		// people who have been invited.
		$fields->addFieldToTab('Root', new Tab('Invitations'), 'Behaviour');
		$fields->addFieldsToTab('Root.Invitations', array(
			new HeaderField('InvitationsHeader', $this->fieldLabel('InvitationsHeader')),
			new EventInvitationField($this, 'Invitations')
		));

		return $fields;
	}

	public function fieldLabels() {
		return array_merge(parent::fieldLabels(), array(
			'TicketTypesHeader' => _t('EventManagement.TICKETTYPES', 'Ticket Types'),
			'Registrations' => _t('EventManagement.REGISTATIONS', 'Registrations'),
			'EmailSettingsHeader' => _t('EventManagement.EMAILSETTINGS', 'Email Settings'),
			'OneRegPerEmail' => _t('EventManagement.ONEREGPEREMAIL', 'Limit to one registration per email address?'),
			'RegEmailConfirm' => _t('EventManagement.REQEMAILCONFIRM', 'Require email confirmation
				to complete registration?'),
			'AfterConfirmTitle' => _t('EventManagement.AFTERCONFIRMTITLE', 'After confirmation title'),
			'AfterConfirmContent' => _t('EventManagement.AFTERCONFIRMCONTENT', 'After confirmation content'),
			'UnRegEmailConfirm' => _t('EventManagement.REQEMAILUNREGCONFIRM', 'Require email confirmation to un-register?'),
			'AfterConfUnregTitle' => _t('EventManagement.AFTERUNREGCONFTITLE', 'After un-registration confirmation title'),
			'AfterConfUnregContent' => _t('EventManagement.AFTERUNREGCONFCONTENT', 'After un-registration confirmation content'),
			'EmailNotifyChanges' => _t('EventManagement.EMAILNOTIFYCHANGES', 'Notify registered users of event changes
				via email?'),
			'NotifyChangeFields' => _t('EventManagement.NOTIFYWHENTHESECHANGE', 'Notify users when these fields change'),
			'MemberSettingsHeader' => _t('EventManagement.MEMBERSETTINGS', 'Member Settings'),
			'RequireLoggedIn' => _t('EventManagement.REQUIREDLOGGEDIN', 'Require users to be logged in to register?'),
			'AfterRegTitle' => _t('EventManagement.AFTERREGTITLE', 'After registration title'),
			'AfterRegContent' => _t('EventManagement.AFTERREGCONTENT', 'After registration content'),
			'AfterUnregTitle' => _t('EventManagement.AFTERUNREGTITLE', 'After un-registration title'),
			'AfterUnregContent' => _t('EventManagement.AFTERUNREGCONTENT', 'After un-registration content'),
			'InvitationsHeader' => _t('EventManagement.EVENTINVITES', 'Event Invitations')
		));
	}

}

/**
 * @package silverstripe-eventmanagement
 */
class RegisterableEvent_Controller extends CalendarEvent_Controller {

	public static $allowed_actions = array(
		'datetime',
		'register',
		'unregister'
	);

	/**
	 * Allows a user to view the details about an individual event date/time.
	 */
	public function datetime($request) {
		if (!$time = $this->getTimeById($request->param('ID'))) {
			$this->httpError(404, 'The requested event time could not be found.');
		}

		return array(
			'Title'    => $time->EventTitle(),
			'DateTime' => $time
		);
	}

	/**
	 * Returns the controller allowing a person to register for an event.
	 *
	 * @param  SS_HTTPRequest $request
	 * @return EventRegisterController
	 */
	public function register($request) {
		if (!$time = $this->getTimeById($request->param('ID'))) {
			$this->httpError(404, 'The requested event time could not be found.');
		}

		$request->shift(1);
		$request->shiftAllParams();

		return new EventRegisterController($this, $time);
	}

	/**
	 * Allows a person to remove their registration by entering their email
	 * address.
	 */
	public function unregister($request) {
		if (!$time = $this->getTimeById($request->param('ID'))) {
			$this->httpError(404, 'The requested event time could not be found.');
		}

		$request->shift(1);
		$request->shiftAllParams();

		return new EventUnregisterController($this, $time);
	}

	/**
	 * @param  int $id
	 * @return RegisterableDateTime
	 */
	protected function getTimeById($id) {
		$filter = sprintf(
			'"CalendarDateTime"."ID" = %d AND "EventID" = %d', $id, $this->ID
		);

		if ($time = $this->DateTimes($filter, null, null, 1)) {
			return $time->First();
		}
	}

}