<?php
/**
 *
 */
class EventSendInvitationsButton extends RequestHandler implements GridField_HTMLProvider, GridField_URLHandler {

	public function sendinvitations($grid) {
		return new EventSendInvitationsHandler($grid, $this);
	}

	public function getHTMLFragments($grid) {
		$data = new ArrayData(array(
			'Title' => _t('EventManagement.SEND_INVITATIONS', 'Send Invitations'),
			'Link' => $grid->Link('send-invitations')
		));

		return array(
			'buttons-before-left' => $data->renderWith('EventSendInvitationsButton')
		);
	}

	public function getURLHandlers($grid) {
		return array(
			'send-invitations' => 'sendinvitations'
		);
	}

}
