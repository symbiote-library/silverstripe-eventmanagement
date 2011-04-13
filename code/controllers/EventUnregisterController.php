<?php
/**
 * Allows a user to cancel their registration by entering their email address.
 *
 * @package silverstripe-eventmanagement
 */
class EventUnregisterController extends Page_Controller {

	public static $allowed_actions = array(
		'UnregisterForm',
		'afterunregistration',
		'confirm'
	);

	protected $parent;
	protected $time;

	/**
	 * Constructs a new controller for deleting a registration.
	 *
	 * @param Controller $parent
	 * @param RegisterableDateTiem $time
	 */
	public function __construct($parent, $time) {
		$this->parent = $parent;
		$this->time   = $time;

		parent::__construct($parent->data());
	}

	/**
	 * @return Form
	 */
	public function UnregisterForm() {
		return new Form(
			$this,
			'UnregisterForm',
			new FieldSet(new EmailField(
				'Email', _t('EventManagement.EMAILADDR', 'Email address'))),
			new FieldSet(new FormAction(
				'doUnregister', _t('EventManagement.UNREGISTER', 'Un-register'))),
			new RequiredFields('Email'));
	}

	/**
	 * @param array $data
	 * @param Form  $form
	 */
	public function doUnregister($data, $form) {
		$regos = $this->time->Registrations(sprintf(
			'"Email" = \'%s\'', Convert::raw2sql($data['Email'])
		));

		if (!$regos || !count($regos)) {
			$form->sessionMessage(_t(
				'EventManager.NOREGFOREMAIL',
				'No registrations for the email you entered could be found.'),
				'bad');
			return $this->redirectBack();
		}

		if ($this->time->Event()->UnRegEmailConfirm) {
			$addr         = $data['Email'];
			$email        = new Email();
			$registration = $regos->First();

			$email->setTo($addr);
			$email->setSubject(sprintf(
				_t('EventManagement.CONFIRMUNREGFOR', 'Confirm Un-Registration For %s (%s)'),
				$this->time->Event()->Title, SiteConfig::current_site_config()->Title));

			$email->setTemplate('EventUnregistrationConfirmationEmail');
			$email->populateTemplate(array(
				'Registration' => $registration,
				'Time'         => $this->time,
				'SiteConfig'   => SiteConfig::current_site_config(),
				'ConfirmLink'  => Director::absoluteURL(Controller::join_links(
					$this->Link(), 'confirm',
					'?email=' . urlencode($addr), '?token=' . $registration->Token))
			));

			$email->send();
		} else {
			foreach ($regos as $rego) {
				$rego->Status = 'Canceled';
				$rego->write();
			}
		}

		return $this->redirect($this->Link('afterunregistration'));
	}

	/**
	 * @return array
	 */
	public function afterunregistration() {
		return array(
			'Title'   => $this->time->Event()->AfterUnregTitle,
			'Content' => $this->time->Event()->obj('AfterUnregContent')
		);
	}

	/**
	 * @return array
	 */
	public function confirm($request) {
		$email = $request->getVar('email');
		$token = $request->getVar('token');

		// Attempt to get at least one registration with the email and token,
		// and if we do then delete all the other ones as well.
		$first = DataObject::get_one('EventRegistration', sprintf(
			'"Email" = \'%s\' AND "Token" = \'%s\'',
			Convert::raw2sql($email), Convert::raw2sql($token)
		));

		if (!$first) {
			return $this->httpError(404);
		}

		// Now delete all registrations with the same email.
		$regos = DataObject::get('EventRegistration', sprintf(
			'"Email" = \'%s\'', Convert::raw2sql($email)
		));

		foreach ($regos as $rego) {
			$rego->Status = 'Canceled';
			$rego->write();
		}

		return array(
			'Title'   => $this->time->Event()->AfterConfUnregTitle,
			'Content' => $this->time->Event()->obj('AfterConfUnregContent')
		);
	}

	/**
	 * @param  string $action
	 * @return string
	 */
	public function Link($action = null) {
		return Controller::join_links(
			$this->parent->Link(), 'unregister', $action
		);
	}

}