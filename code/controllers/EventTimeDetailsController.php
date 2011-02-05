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

	protected $parent;
	protected $time;

	public function __construct($parent, $time) {
		$this->parent = $parent;
		$this->time   = $time;

		parent::__construct();
	}

	public function index() {
		return $this->getViewer('index')->process($this);
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

}