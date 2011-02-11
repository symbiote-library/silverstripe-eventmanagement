<?php
/**
 * Tests for the event invitation field and system.
 *
 * @package    silverstripe-eventmanagement
 * @subpackage tests
 */
class EventInvitationFieldTest extends SapphireTest {

	public static $fixture_file = 'eventmanagement/tests/EventInvitationFieldTest.yml';

	/**
	 * @covers EventInvitationField::doInvite()
	 */
	public function testDoInvite() {
		$event = new RegisterableEvent();
		$event->write();

		$datetime = new RegisterableDateTime();
		$datetime->StartDate = date('Y-m-d');
		$datetime->EventID   = $event->ID;
		$datetime->write();

		$field  = new EventInvitationField($datetime, 'Invitations');
		$form = new Form(
			new EventInvitationFieldTest_Controller(),
			'Form',
			new FieldSet($field),
			new FieldSet());
		$form->loadDataFrom($event);

		$invite = $field->InviteForm();
		$invite->dataFieldByName('Emails')->setValue(array('new' => array(
			'Name'  => array('First Test', 'Second Test'),
			'Email' => array('first@example.com', 'second@example.com')
		)));
		$invite->dataFieldByName('TimeID')->setValue($datetime->ID);

		$field->doInvite(array(), $invite);
		$this->assertEmailSent('first@example.com');
		$this->assertEmailSent('second@example.com');

		$this->clearEmails();

		$invite->dataFieldByName('Emails')->setValue(array('new' => array(
			'Name'  => array('First Test', 'Second Test', 'Third Test'),
			'Email' => array('first@example.com', 'second@example.com', 'third@example.com')
		)));

		$field->doInvite(array(), $invite);
		$this->assertNull($this->findEmail('first@example.com'));
		$this->assertNull($this->findEmail('second@example.com'));
		$this->assertEmailSent('third@example.com');
	}

	/**
	 * @covers EventInvitationField::loadfromgroup()
	 */
	public function testLoadFromGroup() {
		$request = new SS_HTTPRequest('GET', null, array(
			'GroupID' => $this->idFromFixture('Group', 'group')
		));

		$field    = new EventInvitationField(new RegisterableEvent(), 'Invitations');
		$response = $field->loadfromgroup($request);
		$data     = Convert::json2array($response->getBody());

		$expect = array(
			(object) array('name' => 'First Member',  'email' => 'first@example.com'),
			(object) array('name' => 'Second Member', 'email' => 'second@example.com')
		);

		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals($expect, $data);
	}

	/**
	 * @covers EventInvitationField::loadfromtime()
	 */
	public function testLoadFromDatetime() {
		$request = new SS_HTTPRequest('GET', null, array(
			'PastTimeID' => $this->idFromFixture('Group', 'group')
		));

		$field    = new EventInvitationField(new RegisterableEvent(), 'Invitations');
		$response = $field->loadfromtime($request);
		$data     = Convert::json2array($response->getBody());

		$expect = array(
			(object) array('name' => 'First Registration',  'email' => 'first@example.com'),
			(object) array('name' => 'Second Registration', 'email' => 'second@example.com')
		);

		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals($expect, $data);
	}

}

/**
 * @ignore
 */
class EventInvitationFieldTest_Controller extends RequestHandler {

	public function Link() { /* nothing */ }

}