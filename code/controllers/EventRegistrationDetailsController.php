<?php
/**
 * Allows a user to view details for an event registration, provided they have
 * the correct token value, or are the member attached to the registration.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegistrationDetailsController extends Page_Controller {

	public static $url_handlers = array(
		'' => 'index'
	);

	protected $parent;
	protected $registration;
	protected $message;

	public function __construct($parent, $registration) {
		$this->parent       = $parent;
		$this->registration = $registration;

		parent::__construct();
	}

	public function init() {
		parent::init();

		$request = $this->request;
		$rego    = $this->registration;

		$hasToken = $request->getVar('token') == $rego->Token;
		$hasMemb  = $rego->MemberID && Member::currentUserID() == $rego->MemberID;

		if (!$hasToken && !$hasMemb) {
			return Security::permissionFailure($this);
		}

		$message = "EventRegistration.{$rego->ID}.message";
		$this->message = Session::get($message);
		Session::clear($message);
	}

	public function index() {
		return $this->getViewer('index')->process($this);
	}

	/**
	 * @return EventRegistration
	 */
	public function Registration() {
		return $this->registration;
	}

	/**
	 * @return string
	 */
	public function Title() {
		return 'Registration Details for ' . $this->registration->Time()->EventTitle();
	}

	/**
	 * @return string
	 */
	public function Message() {
		return $this->message;
	}

	/**
	 * @return EventRegistrationTicketsTableField
	 */
	public function TicketsTable() {
		$rego  = $this->registration;
		$table = new EventRegistrationTicketsTableField('Tickets', $rego->Time());

		$table->setReadonly(true);
		$table->setShowUnavailableTickets(false);
		$table->setShowUnselectedTickets(false);
		$table->setForceTotalRow(true);
		$table->setValue($rego->Tickets());
		$table->setTotal($rego->Total);

		return $table;
	}

	/**
	 * @return string
	 */
	public function Link() {
		return Controller::join_links(
			$this->parent->Link(), 'registration', $this->registration->ID
		);
	}

}