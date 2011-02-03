<?php
/**
 * Allows the admin to view existing invitations and send out new ones.
 *
 * @package silverstripe-eventmanagement
 */
class EventInvitationField extends FormField {

	public static $allowed_actions = array(
		'invite',
		'InviteForm'
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

		return "<p>$link</p>" . $this->table->FieldHolder();
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
		if ($times = $this->form->getRecord()->DateTimes()) {
			$times = $times->map('ID', 'Summary');
		} else {
			$times = array();
		}

		$fields = new Tab('Main',
			new HeaderField('Select A Date/Time To Invite To'),
			new DropdownField('TimeID', '', $times, null, null, true),
			new HeaderField('EmailsToSendHeader', 'People To Send Invite To'),
			$emails = new TableField('Emails', 'EventInvitation', array(
				'Name'  => 'Name',
				'Email' => 'Email Address'
			), array(
				'Name'  => 'TextField',
				'Email' => 'TextField'
			))
		);
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
			'SiteConfig' => SiteConfig::current_site_config()
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
				'Name'         => $name,
				'RegisterLink' => $invitation->RegisterLink()
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

}