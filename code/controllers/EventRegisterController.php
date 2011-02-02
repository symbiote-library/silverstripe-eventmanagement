<?php
/**
 * Handles collecting the users details and creating a registration to an event
 * for them.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegisterController extends Page_Controller {

	public static $url_handlers = array(
		'' => 'index'
	);

	public static $allowed_actions = array(
		'index',
		'RegisterForm',
		'afterregistration',
		'confirm'
	);

	protected $parent;
	protected $time;

	/**
	 * Constructs a new controller for creating a registration.
	 *
	 * @param Controller $parent
	 * @param RegisterableDateTiem $time
	 */
	public function __construct($parent, $time) {
		$this->parent = $parent;
		$this->time   = $time;

		parent::__construct();
	}

	public function init() {
		parent::init();

		if (!$this->time->canRegister()) {
			return Security::permissionFailure($this, array(
				'default' => 'You do not have permission to register for this event'
			));
		}
	}

	/**
	 * @return string
	 */
	public function index() {
		$event = $this->time->Event();

		if ($event->LimitedPlaces && ($this->time->getRemainingPlaces() < 1)) {
			$message = _t('EventManagement.NOPLACES', 'This event has no more places available.');

			return array(
				'Title'   => _t('EventManagement.EVENTISFULL', 'This Event Is Full'),
				'Content' => "<p>$message</p>"
			);
		}

		$title = sprintf(
			_t('EventManagement.REGISTERFOR', 'Register For %s'), $this->time->EventTitle()
		);

		$controller = $this->customise(array(
			'Title' => $title,
			'Form'  => $this->RegisterForm()
		));
		return $this->getViewer('index')->process($controller);
	}

	/**
	 * @return Form
	 */
	public function RegisterForm() {
		$fields    = $this->time->getRegistrationFields();
		$validator = $this->time->getRegistrationValidator();

		$actions = new FieldSet(
			new FormAction('doRegister', _t('EventMamnager.REGISTER', 'Register'))
		);
		return new Form($this, 'RegisterForm', $fields, $actions, $validator);
	}

	/**
	 * Handles creating the event registration.
	 *
	 * @param array $data
	 * @param Form  $form
	 */
	public function doRegister($data, $form) {
		$registration = new EventRegistration();

		if ($member = Member::currentUser()) {
			$registration->Name  = $member->getName();
			$registration->Email = $member->Email;
		}

		$form->saveInto($registration);

		$registration->EventID  = $this->time->EventID;
		$registration->TimeID   = $this->time->ID;
		$registration->MemberID = Member::currentUserID();

		if (!$this->time->Event()->RegEmailConfirm) {
			$registration->Confirmed = true;
		}

		try {
			$registration->write();
		} catch (ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad');
			return $this->redirectBack();
		}

		if ($this->time->Event()->RegEmailConfirm) {
			$email = new Email();

			$email->setTo($registration->Email);
			$email->setSubject(sprintf(
				_t('EventManagement.CONFIRMREGFOR', 'Confirm Registration For %s (%s)'),
				$this->time->Event()->Title, SiteConfig::current_site_config()->Title));

			$email->setTemplate('EventRegistrationConfirmationEmail');
			$email->populateTemplate(array(
				'Registration' => $registration,
				'Time'         => $this->time,
				'SiteConfig'   => SiteConfig::current_site_config(),
				'ConfirmLink'  => Director::absoluteURL(Controller::join_links(
					$this->Link(), 'confirm', $registration->ID, '?token=' . $registration->Token))
			));

			$email->send();
		}

		return $this->redirect($this->Link('afterregistration'));
	}

	/**
	 * Shows a thankyou message to the user after they register.
	 *
	 * @return array
	 */
	public function afterregistration() {
		return array(
			'Title'   => $this->time->Event()->AfterRegTitle,
			'Content' => $this->time->Event()->obj('AfterRegContent')
		);
	}

	/**
	 * Handles the user clicking on the confirm link in a confirmation email.
	 */
	public function confirm($request) {
		$id    = $request->param('ID');
		$token = $request->getVar('token');

		if (!$rego = DataObject::get_by_id('EventRegistration', $id)) {
			return $this->httpError(404);
		}

		if ($rego->Confirmed || $rego->Token != $token) {
			return $this->httpError(403);
		}

		$rego->Confirmed = true;
		$rego->write();

		return array(
			'Title'   => $this->time->Event()->AfterConfirmTitle,
			'Content' => $this->time->Event()->obj('AfterConfirmContent')
		);
	}

	/**
	 * @param  string $action
	 * @return string
	 */
	public function Link($action = null) {
		return Controller::join_links(
			$this->parent->Link(), 'register', $this->time->ID, $action
		);
	}

}