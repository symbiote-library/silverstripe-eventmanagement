<?php
/**
 * Allows a user to cancel their registration by entering their email address.
 *
 * @package silverstripe-eventmanagement
 */
class EventUnregisterController extends Page_Controller {

	public static $url_handlers = array(
		'' => 'index'
	);

	public static $allowed_actions = array(
		'UnregisterForm'
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

		parent::__construct();
	}

	/**
	 * @return string
	 */
	public function index() {
		$title = sprintf(
			_t('EventManagement.UNREGISTERFROM', 'Unregister From %s'),
			$this->time->EventTitle()
		);

		$controller = $this->customise(array(
			'Title' => $title,
			'Form'  => $this->UnregisterForm()
		));
		return $this->getViewer('index')->process($controller);
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

		foreach ($regos as $rego) $rego->delete();

		return array(
			'Title' => _t('EventManagement.REGCANCELED', 'Registration Canceled')
		);
	}

	/**
	 * @param  string $action
	 * @return string
	 */
	public function Link($action = null) {
		return Controller::join_links(
			$this->parent->Link(), 'unregister', $this->time->ID, $action
		);
	}

}