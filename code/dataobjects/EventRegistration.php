<?php
/**
 * Represents a registration to an event.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegistration extends DataObject {

	private static $db = array(
		'Name'   => 'Varchar(255)',
		'Email'  => 'Varchar(255)',
		'Status' => 'Enum("Unsubmitted, Unconfirmed, Valid, Canceled")',
		'Total'  => 'Money',
		'Token'  => 'Varchar(40)'
	);

	private static $has_one = array(
		'Time'   => 'RegistrableDateTime',
		'Member' => 'Member'
	);

	private static $many_many = array(
		'Tickets' => 'EventTicket'
	);

	private static $many_many_extraFields = array(
		'Tickets' => array('Quantity' => 'Int')
	);

	private static $summary_fields = array(
		'Name'          => 'Name',
		'Email'         => 'Email',
		'Time.Title'    => 'Event',
		'TotalQuantity' => 'Places'
	);

	protected function onBeforeWrite() {
		if (!$this->isInDB()) {
			$generator = new RandomGenerator();
			$this->Token = $generator->randomToken();
		}

		parent::onBeforeWrite();
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->removeByName('Tickets');
		$fields->removeByName('Total');
		$fields->removeByName('Token');
		$fields->removeByName('TimeID');

		$config = GridFieldConfig_RelationEditor::create();
		$config->getComponentByType('GridFieldDataColumns')->setDisplayFields(array(
			'Title'        => 'Ticket Title',
			'PriceSummary' => 'Price',
			'Quantity'     => 'Quantity'
		));
		$ticketsGridField = GridField::create(
			'Tickets',
			'EventTicket',
			$this->Tickets(),
			$config
		);
		$fields->addFieldToTab('Root.Tickets', $ticketsGridField);

		if (class_exists('Payment')) {
			$fields->addFieldToTab('Root.Tickets', new ReadonlyField(
				'TotalNice', 'Total', $this->Total->Nice()
			));
		}

		return $fields;
	}

	/**
	 * @see EventRegistration::EventTitle()
	 */
	public function getTitle() {
		return $this->Time()->Title;
	}

	/**
	 * @return int
	 */
	public function TotalQuantity() {
		return $this->Tickets()->sum('Quantity');
	}

	/**
	 * @return SS_Datetime
	 */
	public function ConfirmTimeLimit() {
		$unconfirmed = $this->Status == 'Unconfirmed';
		$limit       = $this->Time()->Event()->ConfirmTimeLimit;

		if ($unconfirmed && $limit) {
			return DBField::create_field('SS_Datetime', strtotime($this->Created) + $limit);
		}
	}

	/**
	 * @return string
	 */
	public function Link() {
		return Controller::join_links(
			$this->Time()->Event()->Link(), 'registration', $this->ID, '?token=' . $this->Token
		);
	}

	public function canView($member = null) {
		return $this->Time()->canView($member)
			 && Permission::check("CMS_ACCESS_CMSMain", 'any', $member);
	}

	public function canEdit($member = null) {
		return $this->Time()->canEdit($member)
			 && Permission::check("CMS_ACCESS_CMSMain", 'any', $member);
	}

	public function canDelete($member = null) {
		return $this->Time()->canDelete($member)
			 && Permission::check("CMS_ACCESS_CMSMain", 'any', $member);
	}

	public function canCreate($member = null) {
		return $this->Time()->canCreate($member)
			 && Permission::check("CMS_ACCESS_CMSMain", 'any', $member);
	}

}
