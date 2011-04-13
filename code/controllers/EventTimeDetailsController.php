<?php
/**
 * Shows event details for an individual event date/time, and allows user to
 * register to the event.
 *
 * @package silverstripe-eventmanagement
 */
class EventTimeDetailsController extends Page_Controller {

	public static $url_handlers = array(
		'' => 'index'
	);

	public static $allowed_actions = array(
		'register',
		'unregister'
	);

	protected $parent;
	protected $time;

	public function __construct($parent, $time) {
		$this->parent     = $parent;
		$this->time       = $time;

		parent::__construct($parent->data()->customise(array(
			'Title' => $this->Title()
		)));
	}

	public function index() {
		return $this->getViewer('index')->process($this);
	}

	/**
	 * @return EventRegisterController
	 */
	public function register() {
		return new EventRegisterController($this, $this->time);
	}

	/**
	 * @return EventUnregisterController
	 */
	public function unregister() {
		return new EventUnregisterController($this, $this->time);
	}

	/**
	 * @return RegisterableDateTime
	 */
	public function DateTime() {
		return $this->time;
	}

	/**
	 * @return string
	 */
	public function Title() {
		return $this->DateTime()->EventTitle();
	}

	/**
	 * @return string
	 */
	public function ExtraSidebarContent() {
		$content = '';
		$this->extend('augmentSidebarContent', $content);
		return $content;
	}

	/**
	 * @return bool
	 */
	public function EventInFuture() {
		return time() < $this->DateTime()->getStartTimestamp();
	}

	/**
	 * @return bool
	 */
	public function EventIsFull() {
		return !$this->DateTime()->getRemainingCapacity();
	}

	/**
	 * @return Form
	 */
	public function UnregisterForm() {
		return $this->unregister()->UnregisterForm();
	}

	/**
	 * @param  string $action
	 * @return string
	 */
	public function Link($action = null) {
		return Controller::join_links(
			$this->parent->Link(), 'details', $this->time->ID, $action
		);
	}

}