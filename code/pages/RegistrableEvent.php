<?php
/**
 * A calendar event that can people can register to attend.
 */
class RegistrableEvent extends CalendarEvent {

	private static $db = array(
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
		'EmailNotifyChanges'    => 'Boolean',
		'NotifyChangeFields'    => 'Text',
		'AfterRegTitle'         => 'Varchar(255)',
		'AfterRegContent'       => 'HTMLText',
		'AfterUnregTitle'       => 'Varchar(255)',
		'AfterUnregContent'     => 'HTMLText'
	);

	private static $has_many = array(
		'Tickets'     => 'EventTicket',
		'DateTimes'   => 'RegistrableDateTime',
		'Invitations' => 'EventInvitation'
	);

	private static $defaults = array(
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

	private static $icon = "eventmanagement/images/date_edit.png";

	private static $description = "An event that can be registered for.";

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->insertAfter(
			new ToggleCompositeField(
				'AfterRegistrationContent',
				_t('EventRegistration.AFTER_REG_CONTENT', 'After Registration Content'),
				array(
					new TextField('AfterRegTitle', _t('EventRegistration.TITLE', 'Title')),
					new HtmlEditorField('AfterRegContent', _t('EventRegistration.CONTENT', 'Content'))
				)
			),
			'Content'
		);

		$fields->insertAfter(
			new ToggleCompositeField(
				'AfterUnRegistrationContent',
				_t('EventRegistration.AFTER_UNREG_CONTENT', 'After Un-Registration Content'),
				array(
					new TextField('AfterUnregTitle', _t('EventRegistration.TITLE', 'Title')),
					new HtmlEditorField('AfterUnregContent', _t('EventRegistration.CONTENT', 'Content'))
				)
			),
			'AfterRegistrationContent'
		);

		if ($this->RegEmailConfirm) {
			$fields->addFieldToTab('Root.Main', new ToggleCompositeField(
				'AfterRegistrationConfirmation',
				_t('EventRegistration.AFTER_REG_CONFIRM_CONTENT', 'After Registration Confirmation Content'),
				array(
					new TextField('AfterConfirmTitle', _t('EventRegistration.TITLE', 'Title')),
					new HtmlEditorField('AfterConfirmContent', _t('EventRegistration.CONTENT', 'Content'))
				)
			));
		}

		if ($this->UnRegEmailConfirm) {
			$fields->addFieldToTab('Root.Main', new ToggleCompositeField(
				'AfterUnRegistrationConfirmation',
				_t('EventRegistration.AFTER_UNREG_CONFIRM_CONTENT', 'After Un-Registration Confirmation Content'),
				array(
					new TextField('AfterConfUnregTitle', _t('EventRegistration.TITLE', 'Title')),
					new HtmlEditorField('AfterConfUnregContent', _t('EventRegistration.CONTENT', 'Content'))
				)
			));
		}

		$fields->addFieldToTab('Root.Tickets', new GridField(
			'Tickets',
			'Ticket Types',
			$this->Tickets(),
			GridFieldConfig_RecordEditor::create()
		));

		$generators = ClassInfo::implementorsOf('EventRegistrationTicketGenerator');

		if ($generators) {
			foreach ($generators as $generator) {
				$instance = new $generator();
				$generators[$generator] = $instance->getGeneratorTitle();
			}

			$generator = new DropdownField(
				'TicketGenerator',
				_t('EventRegistration.TICKET_GENERATOR', 'Ticket generator'),
				$generators
			);

			$generator->setEmptyString(_t('EventManagement.NONE', '(None)'));
			$generator->setDescription(_t(
				'EventManagement.TICKET_GENERATOR_NOTE',
				'The ticket generator is used to generate a ticket file for the user to download.'
			));

			$fields->addFieldToTab('Root.Tickets', $generator);
		}

		$regGridFieldConfig = GridFieldConfig_Base::create()
		->removeComponentsByType('GridFieldAddNewButton')
		->removeComponentsByType('GridFieldDeleteAction')
		->addComponents(
			new GridFieldButtonRow('after'),
			new GridFieldPrintButton('buttons-after-left'),
			new GridFieldExportButton('buttons-after-left')
		);

		$fields->addFieldsToTab('Root.Registrations', array(
			new GridField(
				'Registrations',
				_t('EventManagement.REGISTRATIONS', 'Registrations'),
				$this->DateTimes()->relation('Registrations')->filter('Status', 'Valid'),
				$regGridFieldConfig
			),
			new GridField(
				'CanceledRegistrations',
				_t('EventManagement.CANCELLATIONS', 'Cancellations'),
				$this->DateTimes()->relation('Registrations')->filter('Status', 'Canceled'),
				$regGridFieldConfig
			)
		));

		if ($this->RegEmailConfirm) {
			$fields->addFieldToTab('Root.Registrations', new ToggleCompositeField(
				'UnconfirmedRegistrations',
				_t('EventManagement.UNCONFIRMED_REGISTRATIONS', 'Unconfirmed Registrations'),
				array(
					new GridField(
						'UnconfirmedRegistrations',
						'',
						$this->DateTimes()->relation('Registrations')->filter('Status', 'Unconfirmed')
					)
				)
			));
		}

		$fields->addFieldToTab('Root.Invitations', new GridField(
			'Invitations',
			_t('EventManagement.INVITATIONS', 'Invitations'),
			$this->Invitations(),
			GridFieldConfig_RecordViewer::create()
				->addComponent(new GridFieldButtonRow('before'))
				->addComponent(new EventSendInvitationsButton($this))
		));

		return $fields;
	}

	public function getSettingsFields() {
		$fields = parent::getSettingsFields();

		Requirements::javascript('eventmanagement/javascript/cms.js');

		$fields->addFieldsToTab('Root.Registration', array(
			new CheckboxField(
				'OneRegPerEmail',
				_t('EventManagement.ONE_REG_PER_EMAIL', 'Limit to one registration per email address?')
			),
			new CheckboxField(
				'RequireLoggedIn',
				_t('EventManagement.REQUIRE_LOGGED_IN', 'Require users to be logged in to register?')
			),
			$limit = new NumericField(
				'RegistrationTimeLimit',
				_t('EventManagement.REG_TIME_LIMIT', 'Registration time limit')
			),
		));

		$limit->setDescription(_t(
			'EventManagement.REG_TIME_LIMIT_NOTE',
			'The time limit to complete registration, in seconds. Set to 0 to disable place holding.'
		));

		$fields->addFieldsToTab('Root.Email', array(
			new CheckboxField(
				'RegEmailConfirm',
				_t('EventManagement.REQ_EMAIL_CONFIRM', 'Require email confirmation to complete free registrations?')
			),
			$info = new TextField(
				'EmailConfirmMessage',
				_t('EventManagement.EMAIL_CONFIRM_INFO', 'Email confirmation information')
			),
			$limit = new NumericField(
				'ConfirmTimeLimit',
				_t('EventManagement.EMAIL_CONFIRM_TIME_LIMIT', 'Email confirmation time limit')
			),
			new CheckboxField(
				'UnRegEmailConfirm',
				_t('EventManagement.REQ_UN_REG_EMAIL_CONFIRM', 'Require email confirmation to un-register?')
			),
			new CheckboxField(
				'EmailNotifyChanges',
				_t('EventManagement.EMAIL_NOTIFY_CHANGES', 'Notify registered users of event changes via email?')
			),
			new CheckboxSetField(
				'NotifyChangeFields',
				_t('EventManagement.NOTIFY_CHANGE_IN', 'Notify of changes in'),
				singleton('RegistrableDateTime')->fieldLabels(false)
			)
		));

		$info->setDescription(_t(
			'EventManagement.EMAIL_CONFIRM_INFO_NOTE',
			'This message is displayed to users to let them know they need to confirm their registration.'
		));

		$limit->setDescription(_t(
			'EventManagement.CONFIRM_TIME_LIMIT_NOTE',
			'The time limit to conform registration, in seconds. Set to 0 for no limit.'
		));

		return $fields;
	}

}

class RegistrableEvent_Controller extends CalendarEvent_Controller {

	public static $allowed_actions = array(
		'details',
		'registration'
	);

	/**
	 * Shows details for an individual event date time, as well as forms for
	 * registering and un-registering.
	 *
	 * @param SS_HTTPRequest $request
	 * @return array
	 */
	public function details($request) {
		$id = $request->param('ID');

		if (!ctype_digit($id)) {
			$this->httpError(404);
		}

		$time = RegistrableDateTime::get()->byID($id);

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
	 * @param SS_HTTPRequest $request
	 * @return EventRegistrationDetailsController
	 */
	public function registration($request) {
		$id = $request->param('ID');

		if (!ctype_digit($id)) {
			$this->httpError(404);
		}

		$rego = EventRegistration::get()->byID($id);

		if (!$rego || $rego->Time()->EventID != $this->ID) {
			$this->httpError(404);
		}

		$request->shift();
		$request->shiftAllParams();

		return new EventRegistrationDetailsController($this, $rego);
	}

}
