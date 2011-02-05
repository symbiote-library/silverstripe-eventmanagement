<?php
/**
 * A form for registering for events which collects the desired tickets and
 * basic user details, then requires the user to pay if needed, then finally
 * displays a registration summary.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegisterForm extends MultiForm {

	public static $start_step = 'EventRegisterTicketsStep';

}