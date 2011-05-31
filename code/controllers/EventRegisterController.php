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
		'RegisterForm',
		'confirm'
	);

	protected $parent;
	protected $datetime;

	/**
	 * Constructs a new controller for creating a registration.
	 *
	 * @param Controller $parent
	 * @param RegisterableDateTime $datetime
	 */
	public function __construct($parent, $datetime) {
		$this->parent   = $parent;
		$this->datetime = $datetime;

		parent::__construct($parent->data());
	}

	public function init() {
		parent::init();

		if ($this->datetime->Event()->RequireLoggedIn && !Member::currentUserID()) {
			return Security::permissionFailure($this, array(
				'default' => 'Please log in to register for this event.'
			));
		}

		$form   = $this->RegisterForm();
		$expiry = $form->getExpiryDateTime();

		if ($expiry && $expiry->InPast()) {
			$form->getSession()->Registration()->delete();
			$form->getSession()->delete();

			$message = _t('EventManagement.REGSESSIONEXPIRED', 'Your'
				. ' registration expired before it was completed. Please'
				. ' try ordering your tickets again.');
			$form->sessionMessage($message, 'bad');

			$this->redirect($this->Link());
		}
	}

	public function index() {
		$datetime = $this->datetime;
		$exclude  = null;

		// If we have a current multiform ID, then exclude the linked
		// registration from the capacity calculation.
		if (isset($_GET['MultiFormSessionID'])) {
			$exclude = $this->RegisterForm()->getSession()->RegistrationID;
		}

		if ($datetime->getStartTimestamp() < time()) {
			$data = array(
				'Title'   => $datetime->EventTitle() . ' Has Already Happened',
				'Content' => '<p>You can no longer register for this event.</p>'
			);
		} elseif ($datetime->getRemainingCapacity($exclude)) {
			$data = array(
				'Title' => 'Register For ' . $datetime->EventTitle(),
				'Form'  => $this->RegisterForm()
			);
		} else {
			$data = array(
				'Title'   => $datetime->EventTitle() . ' Is Full',
				'Content' => '<p>There are no more places available at this event.</p>'
			);
		}

		return $this->getViewer('index')->process($this->customise($data));
	}

	/**
	 * Handles a user clicking on a registration confirmation link in an email.
	 */
	public function confirm($request) {
		$id    = $request->param('ID');
		$token = $request->getVar('token');

		if (!$rego = DataObject::get_by_id('EventRegistration', $id)) {
			return $this->httpError(404);
		}

		if ($rego->Token != $token) {
			return $this->httpError(403);
		}

		if ($rego->Status != 'Unconfirmed') {
			return $this->redirect($rego->Link());
		}

		try {
			$rego->Status = 'Valid';
			$rego->write();

			EventRegistrationDetailsEmail::factory($rego)->send();
		} catch (ValidationException $e) {
			return array(
				'Title'   => 'Could Not Confirm Registration',
				'Content' => '<p>' . $e->getResult()->message() . '</p>'
			);
		}

		return array(
			'Title'   => $this->datetime->Event()->AfterConfirmTitle,
			'Content' => $this->datetime->Event()->obj('AfterConfirmContent')
		);
	}

	/**
	 * @return RegisterableDateTime
	 */
	public function getDateTime() {
		return $this->datetime;
	}

	/**
	 * @return Form
	 */
	public function RegisterForm() {
		return new EventRegisterForm($this, 'RegisterForm');
	}

	/**
	 * @param  string $action
	 * @return string
	 */
	public function Link($action = null) {
		return Controller::join_links(
			$this->parent->Link(), 'register', $action
		);
	}

}
