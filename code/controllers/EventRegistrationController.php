<?php
/**
 * Handles collecting the users details and creating a registration to an event
 * for them.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegistrationController extends Page_Controller {

	public static $allowed_actions = array(
		'index',
		'RegisterForm',
		'afterregistration'
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
		if (!$this->time->canRegister()) {
			return Security::permissionFailure($this, array(
				'default' => 'You do not have permission to register for this event'
			));
		}

		parent::init();
	}

	/**
	 * @return string
	 */
	public function index() {
		$title = sprintf(
			_t('EventManagement.REGISTERFOR', 'Register For %s'), $this->time->EventTitle()
		);

		return array(
			'Title' => $title,
			'Form'  => $this->RegisterForm()
		);
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
		$form->saveInto($registration);

		$registration->EventID  = $this->time->EventID;
		$registration->TimeID   = $this->time->ID;
		$registration->MemberID = Member::currentUserID();

		try {
			$registration->write();
		} catch (ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad');
			return $this->redirectBack();
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
			'Title' => _t('EventManagement.THANKSREGISTERING', 'Thanks For Registering!')
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