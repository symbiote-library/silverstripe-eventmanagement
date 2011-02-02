<?php
/**
 * @package silverstripe-eventmanagement
 */

if (!class_exists('Calendar')) {
	throw new Exception('The Event Management module requires the Event Calendar module.');
}