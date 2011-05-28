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

	public static $allowed_actions = array(
		'ticketfile'
	);

	protected $parent;
	protected $registration;
	protected $message;

	public function __construct($parent, $registration) {
		$this->parent       = $parent;
		$this->registration = $registration;

		parent::__construct($parent->data()->customise(array(
			'Title' => $this->Title()
		)));
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

		// If the registration has passed the confirmation expiry date, then
		// cancel it.
		if ($time = $this->registration->ConfirmTimeLimit()) {
			if ($time->InPast()) {
				$this->registration->Status = 'Canceled';
				$this->registration->write();
			}
		}

		$message = "EventRegistration.{$rego->ID}.message";
		$this->message = Session::get($message);
		Session::clear($message);
	}

	public function index() {
		return $this->getViewer('index')->process($this);
	}

	public function ticketfile() {
		if (!$this->HasTicketFile() || $this->registration->Status != 'Valid') {
			$this->httpError(404);
		}

		$generator = $this->registration->Time()->Event()->TicketGenerator;
		$generator = new $generator();

		$path = $generator->generateTicketFileFor($this->registration);
		$path = Director::getAbsFile($path);
		$name = $generator->getTicketFilenameFor($this->registration);
		$mime = $generator->getTicketMimeTypeFor($this->registration);

		if (!$path || !file_exists($path)) {
			$this->httpError(404, 'The ticket file could not be generated.');
		}

		return SS_HTTPRequest::send_file(file_get_contents($path), $name, $mime);
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
	 * @return bool
	 */
	public function HasTicketFile() {
		return (bool) $this->registration->Time()->Event()->TicketGenerator;
	}

	/**
	 * @return string
	 */
	public function Link($action = null) {
		return Controller::join_links(
			$this->parent->Link(), 'registration', $this->registration->ID, $action,
			'?token='. $this->registration->Token
		);
	}

}