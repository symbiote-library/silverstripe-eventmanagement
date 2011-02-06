<?php
/**
 * Allows the admin to view existing invitations and send out new ones.
 *
 * @package silverstripe-eventmanagement
 */
class EventInvitationField extends FormField {

	public static $allowed_actions = array(
		'FieldHolder',
		'invite',
		'InviteForm',
		'loadfromgroup',
		'loadfromtime'
	);

	protected $table;

	/**
	 * @param Controller $parent
	 * @param string $name
	 */
	public function __construct($parent, $name) {
		parent::__construct($name);

		$this->table = new TableListField('Invites', 'EventInvitation', null, sprintf(
			'"EventID" = %d', $parent->ID
		));
		$this->table->setPermissions(array('show', 'export', 'print'));
	}

	public function FieldHolder() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript('eventmanagement/javascript/EventInvitationField.js');

		$link = $this->createTag('a', array(
			'id'    => 'event-do-invite',
			'href'  => $this->Link('invite')),
			_t('EventManagement.INVITEPEOPLETOEVENT', 'Invite People To Event'));

		return $this->createTag(
			'div',
			array('id' => $this->id(), 'href' => $this->Link('FieldHolder')),
			"<p>$link</p>" . $this->table->FieldHolder());
	}

	/**
	 * @return string
	 */
	public function invite() {
		Requirements::clear();

		$controller = $this->customise(array(
			'Form' => $this->InviteForm()
		));
		return $controller->renderWith('EventInvitationField_invite');
	}

	/**
	 * @return Form
	 */
	public function InviteForm() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-metadata/jquery.metadata.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/jquery_improvements.js');
		Requirements::javascript('eventmanagement/javascript/EventInvitationField_invite.js');

		if ($times = $this->form->getRecord()->DateTimes()) {
			$times = $times->map('ID', 'Summary');
		} else {
			$times = array();
		}

		// Get past date times attached to the parent calendar, so we can get
		// all registered members from them.
		$past = DataObject::get('RegisterableDateTime', sprintf(
			'"CalendarID" = %d AND "StartDate" < \'%s\'',
			$this->form->getRecord()->CalendarID, date('Y-m-d')
		));

		if ($past) {
			$pastTimes = array();

			foreach ($past->groupBy('EventID') as $value) {
				$pastTimes[$value->First()->EventTitle()] = $value->map('ID', 'Summary');
			}
		} else {
			$pastTimes = array();
		}

		$fields = new Tab('Main',
			new HeaderField('Select A Date/Time To Invite To'),
			new DropdownField('TimeID', '', $times, null, null, true),
			new HeaderField('AddPeopleHeader', 'Add People To Invite From'),
			new SelectionGroup('AddPeople', array(
				'From a member group' => $group = new DropdownField(
					'GroupID', '', DataObject::get('Group')->map(), null, null, true),
				'From a past event' => $time = new GroupedDropdownField(
					'PastTimeID', '', $pastTimes, null, null, true)
			)),
			new HeaderField('EmailsToSendHeader', 'People To Send Invite To'),
			$emails = new TableField('Emails', 'EventInvitation', array(
				'Name'  => 'Name',
				'Email' => 'Email Address'
			), array(
				'Name'  => 'TextField',
				'Email' => 'TextField'
			))
		);

		$group->addExtraClass(sprintf("{ link: '%s' }", $this->Link('loadfromgroup')));
		$time->addExtraClass(sprintf("{ link: '%s' }", $this->Link('loadfromtime')));

		$emails->setCustomSourceItems(new DataObjectSet());

		$fields    = new FieldSet(new TabSet('Root', $fields));
		$validator = new RequiredFields('TimeID');

		return new Form($this, 'InviteForm', $fields, new FieldSet(
			new FormAction('doInvite', 'Invite')
		), $validator);
	}

	public function doInvite($data, Form $form) {
		$data   = $form->getData();
		$emails = $data['Emails']['new'];
		$sent   = new DataObjectSet();

		if (!$emails) {
			$form->addErrorMessage('Emails', 'Please enter at least one person to invite.');
		}

		$time = DataObject::get_by_id('RegisterableDateTime', $data['TimeID']);

		$invite = new Email();
		$invite->setSubject(sprintf(
			'Event Invitation For %s (%s)',
			$time->EventTitle(), SiteConfig::current_site_config()->Title));
		$invite->setTemplate('EventInvitationEmail');
		$invite->populateTemplate(array(
			'Time'       => $time,
			'SiteConfig' => SiteConfig::current_site_config(),
			'Link'       => Director::absoluteURL($time->Link())
		));

		$count = count($emails['Name']);
		for ($i = 0; $i < $count; $i++) {
			$name  = trim($emails['Name'][$i]);
			$email = trim($emails['Email'][$i]);

			if (!$name || !$email) continue;

			$regod = DataObject::get_one('EventRegistration', sprintf(
				'"Email" = \'%s\' AND "TimeID" = \'%d\'', Convert::raw2sql($email), $time->ID
			));

			if ($regod) {
				$sent->push(new ArrayData(array(
					'Name' => $name, 'Email' => $email, 'Sent' => false, 'Reason' => 'Already registered'
				)));
				continue;
			}

			$invited = DataObject::get_one('EventInvitation', sprintf(
				'"Email" = \'%s\' AND "TimeID" = \'%d\'', Convert::raw2sql($email), $time->ID
			));

			if ($invited) {
				$sent->push(new ArrayData(array(
					'Name' => $name, 'Email' => $email, 'Sent' => false, 'Reason' => 'Already invited'
				)));
				continue;
			}

			$invitation = new EventInvitation();
			$invitation->Name    = $name;
			$invitation->Email   = $email;
			$invitation->TimeID  = $time->ID;
			$invitation->EventID = $time->EventID;
			$invitation->write();

			$_invite = clone $invite;
			$_invite->setTo($email);
			$_invite->populateTemplate(array(
				'Name' => $name
			));
			$_invite->send();

			$sent->push(new ArrayData(array(
				'Name' => $name, 'Email' => $email, 'Sent' => true
			)));
		}

		Requirements::clear();
		$controller = $this->customise(array(
			'Result' => $sent
		));
		return $controller->renderWith('EventInvitationField_invite');
	}

	/**
	 * Loads a list of names and emails from a group.
	 */
	public function loadfromgroup($request) {
		$group = DataObject::get_by_id('Group', $request->getVar('GroupID'));

		if (!$group) {
			$this->httpError(404);
		}

		$result = array();
		foreach ($group->Members() as $member) {
			$result[] = array(
				'name'  => $member->getName(),
				'email' => $member->Email
			);
		}

		$response = new SS_HTTPResponse(Convert::array2json($result));
		$response->addHeader('Content-Type', 'application/json');
		return $response;
	}

	/**
	 * Loads a list of names and emails from a past event date time.
	 */
	public function loadfromtime($request) {
		$time = DataObject::get_by_id('RegisterableDateTime', $request->getVar('PastTimeID'));

		if (!$time) {
			$this->httpError(404);
		}

		$result = array();
		foreach ($time->Registrations() as $registration) {
			$result[] = array(
				'name'  => $registration->Name,
				'email' => $registration->Email
			);
		}

		$response = new SS_HTTPResponse(Convert::array2json($result));
		$response->addHeader('Content-Type', 'application/json');
		return $response;
	}

}