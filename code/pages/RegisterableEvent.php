<?php
/**
 * A calendar event that can people can register to attend.
 *
 * @package silverstripe-eventmanagement
 */
class RegisterableEvent extends CalendarEvent {

	public static $db = array(
		'TicketGenerator'       => 'Varchar(255)',
		'OneRegPerEmail'        => 'Boolean',
		'RequireLoggedIn'       => 'Boolean',
		'RegistrationTimeLimit' => 'Int',
		'RegEmailConfirm'       => 'Boolean',
		'EmailConfirmMessage'   => 'Varchar(255)',
		'ConfirmTimeLimit'      => 'Int',
		'AfterConfirmTitle'     => 'Varchar(255)',
		'AfterConfirmContent'   => 'HTMLText',
		'UnRegEmailConfirm'     => 'Boolean',
		'AfterConfUnregTitle'   => 'Varchar(255)',
		'AfterConfUnregContent' => 'HTMLText',
		'AfterConfirmContent'   => 'HTMLText',
		'EmailNotifyChanges'    => 'Boolean',
		'NotifyChangeFields'    => 'Text',
		'AfterRegTitle'         => 'Varchar(255)',
		'AfterRegContent'       => 'HTMLText',
		'AfterUnregTitle'       => 'Varchar(255)',
		'AfterUnregContent'     => 'HTMLText'
	);

	public static $has_many = array(
		'Tickets'   => 'EventTicket',
		'DateTimes' => 'RegisterableDateTime'
	);

	public static $defaults = array(
		'RegistrationTimeLimit' => 900,
		'AfterRegTitle'         => 'Thanks For Registering',
		'AfterRegContent'       => '<p>Thanks for registering! We look forward to seeing you.</p>',
		'EmailConfirmMessage'   => 'Important: You must check your emails and confirm your registration before it is valid.',
		'ConfirmTimeLimit'      => 21600,
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

		$generators = ClassInfo::implementorsOf('EventRegistrationTicketGenerator');
		if ($generators) {
			foreach ($generators as $generator) {
				$instance = new $generator();
				$generators[$generator] = $instance->getGeneratorTitle();
			}

			$fields->addFieldsToTab('Root.Content.Tickets', array(
				new HeaderField('TicketGeneratorHeader', 'Ticket Generator'),
				new LiteralField('TicketGeneratorNone', '<p>The ticket '
					. 'generator is used to generate a ticket file for the '
					. 'user to download and print to bring to the event.</p>'),
				new DropdownField(
					'TicketGenerator', '', $generators, null, null, '(none)')
			));
		}

		$changeFields = singleton('RegisterableDateTime')->fieldLabels(false);
		$fields->addFieldsToTab('Root.Content.Registration', array(
			new HeaderField('RegistrationSettingsHeader', $this->fieldLabel('RegistrationSettingsHeader')),
			new CheckboxField('OneRegPerEmail', $this->fieldLabel('OneRegPerEmail')),
			new CheckboxField('RequireLoggedIn', $this->fieldLabel('RequireLoggedIn')),
			new NumericField('RegistrationTimeLimit', $this->fieldLabel('RegistrationTimeLimit')),
			new HeaderField('EmailSettingsHeader', $this->fieldLabel('EmailSettingsHeader')),
			new CheckboxField('RegEmailConfirm', $this->fieldLabel('RegEmailConfirm')),
			new TextField('EmailConfirmMessage', $this->fieldLabel('EmailConfirmMessage')),
			new NumericField('ConfirmTimeLimit', $this->fieldLabel('ConfirmTimeLimit')),
			new TextField('AfterConfirmTitle', $this->fieldLabel('AfterConfirmTitle')),
			new HtmlEditorField('AfterConfirmContent', $this->fieldLabel('AfterConfirmContent'), 5),
			new CheckboxField('UnRegEmailConfirm', $this->fieldLabel('UnRegEmailConfirm')),
			new TextField('AfterConfUnregTitle', $this->fieldLabel('AfterConfUnregTitle')),
			new HtmlEditorField('AfterConfUnregContent', $this->fieldLabel('AfterConfUnregContent'), 5),
			new CheckboxField('EmailNotifyChanges', $this->fieldLabel('EmailNotifyChanges')),
			new CheckboxSetField('NotifyChangeFields', $this->fieldLabel('NotifyChangeFields'), $changeFields)
		));

		$fields->addFieldsToTab('Root.Content.AfterRegistration', array(
			new TextField('AfterRegTitle', $this->fieldLabel('AfterRegTitle')),
			new HtmlEditorField('AfterRegContent', $this->fieldLabel('AfterRegContent'))
		));

		$fields->addFieldsToTab('Root.Content.AfterUnregistration', array(
			new TextField('AfterUnregTitle', $this->fieldLabel('AfterUnregTitle')),
			new HtmlEditorField('AfterUnregContent', $this->fieldLabel('AfterUnregContent'))
		));

		$registrations = new ComplexTableField(
			$this, 'Registrations', 'EventRegistration',
			null, null,
			'"Status" = \'Valid\' AND "Time"."EventID" = ' . $this->ID,
			null,
			'INNER JOIN "CalendarDateTime" AS "Time" ON "Time"."ID" = "TimeID"'
		);
		$registrations->setTemplate('EventRegistrationComplexTableField');
		$registrations->setPermissions(array('show', 'print', 'export'));

		$canceled = new ComplexTableField(
			$this, 'Registations', 'EventRegistration',
			null, null,
			'"Status" = \'Canceled\' AND "Time"."EventID" = ' . $this->ID,
			null,
			'INNER JOIN "CalendarDateTime" AS "Time" ON "Time"."ID" = "TimeID"'
		);
		$canceled->setTemplate('EventRegistrationComplexTableField');
		$canceled->setPermissions(array('show', 'print', 'export'));

		$fields->addFieldToTab('Root', new Tab('Registrations'), 'Behaviour');
		$fields->addFieldsToTab('Root.Registrations', array(
			new HeaderField('RegistrationsHeader', $this->fieldLabel('Registrations')),
			$registrations,
			new ToggleCompositeField('CanceledRegistrations', 'Canceled Registrations', $canceled)
		));

		if ($this->RegEmailConfirm) {
			$unconfirmed = new ComplexTableField(
				$this, 'UnconfirmedRegistations', 'EventRegistration',
				null, null,
				'"Status" = \'Unconfirmed\' AND "Time"."EventID" = ' . $this->ID,
				null,
				'INNER JOIN "CalendarDateTime" AS "Time" ON "Time"."ID" = "TimeID"'
			);
			$unconfirmed->setPermissions(array('show', 'print', 'export'));
			$unconfirmed->setTemplate('EventRegistrationComplexTableField');

			$fields->addFieldToTab('Root.Registrations', new ToggleCompositeField(
				'UnconfirmedRegistrations', 'Unconfirmed Registrations', $unconfirmed
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
			'RegistrationSettingsHeader' => _t('EventManagement.REGISTATIONSETTINGS', 'Registration Settings'),
			'RegistrationTimeLimit' => _t('EventManagement.REGTIMELIMIT',
				'Time limit to complete registration within (in seconds, 0 to disable place holding during registration)'),
			'EmailSettingsHeader' => _t('EventManagement.EMAILSETTINGS', 'Email Settings'),
			'OneRegPerEmail' => _t('EventManagement.ONEREGPEREMAIL', 'Limit to one registration per email address?'),
			'RegEmailConfirm' => _t('EventManagement.REQEMAILCONFIRM',
				'Require email confirmation to complete free registrations?'),
			'EmailConfirmMessage' => _t('EventManagement.EMAILCONFIRMINFOMSG', 'Email confirmation information message'),
			'ConfirmTimeLimit' => _t('EventManagement.CONFIRMTIMELIMIT',
				'Time limit to confirm registration within (in seconds, 0 for unlimited)'),
			'AfterConfirmTitle' => _t('EventManagement.AFTERCONFIRMTITLE', 'After confirmation title'),
			'AfterConfirmContent' => _t('EventManagement.AFTERCONFIRMCONTENT', 'After confirmation content'),
			'UnRegEmailConfirm' => _t('EventManagement.REQEMAILUNREGCONFIRM', 'Require email confirmation to un-register?'),
			'AfterConfUnregTitle' => _t('EventManagement.AFTERUNREGCONFTITLE', 'After un-registration confirmation title'),
			'AfterConfUnregContent' => _t('EventManagement.AFTERUNREGCONFCONTENT', 'After un-registration confirmation content'),
			'EmailNotifyChanges' => _t('EventManagement.EMAILNOTIFYCHANGES', 'Notify registered users of event changes via email?'),
			'NotifyChangeFields' => _t('EventManagement.NOTIFYWHENTHESECHANGE', 'Notify users when these fields change'),
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
		'details',
		'registration'
	);

	/**
	 * Shows details for an individual event date time, as well as forms for
	 * registering and unregistering.
	 *
	 * @param  SS_HTTPRequest $request
	 * @return array
	 */
	public function details($request) {
		$id   = $request->param('ID');
		$time = DataObject::get_by_id('RegisterableDateTime', (int) $id);

		if (!$time || $time->EventID != $this->ID) {
			$this->httpError(404);
		}

		$request->shift();
		$request->shiftAllParams();

		return new EventTimeDetailsController($this, $time);
	}

	/**
	 * Allows a user to view the details of their registration.
	 *
	 * @param  SS_HTTPRequest $request
	 * @return EventRegistrationDetailsController
	 */
	public function registration($request) {
		$id   = $request->param('ID');
		$rego = DataObject::get_by_id('EventRegistration', (int) $id);

		if (!$rego || $rego->Time()->EventID != $this->ID) {
			$this->httpError(404);
		}

		$request->shift();
		$request->shiftAllParams();

		return new EventRegistrationDetailsController($this, $rego);
	}

}
