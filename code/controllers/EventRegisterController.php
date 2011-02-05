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
		'Form',
		'afterregistration',
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

		parent::__construct();
	}

	public function init() {
		parent::init();

		if ($this->datetime->Event()->RequireLoggedIn && !Member::currentUserID()) {
			return Security::permissionFailure($this, array(
				'default' => 'Please log in to register for this event.'
			));
		}
	}

	public function index() {
		return $this->getViewer('index')->process($this);
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
	public function Form() {
		return new EventRegisterForm($this, 'Form');
	}

	/**
	 * @return string
	 */
	public function Title() {
		return 'Register For ' . $this->datetime->EventTitle();
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