<?php

// start a new sticky form session in case of failure
elgg_make_sticky_form('event');

$guid = get_input("guid");
$container_guid = get_input("container_guid");
$title = get_input("title");
$shortdescription = get_input("shortdescription");
$tags = get_input("tags");
$organizer = get_input("organizer");
$description = get_input("description");
$comments_on = get_input("comments_on");
$location = get_input("location");
$region = get_input("region");
$event_type = get_input("event_type");
$website = get_input("website");
$contact_details = get_input("contact_details");
$latitude = get_input("latitude");
$longitude = get_input("longitude");
$venue = get_input("venue");
$fee = get_input("fee");
$start_day = get_input("start_day");
$end_day = get_input("end_day");
$end_time_hours = get_input("end_time_hours");
$end_time_minutes = get_input("end_time_minutes");
$registration_ended = get_input("registration_ended");
$show_attendees = get_input("show_attendees");
$notify_onsignup = get_input("notify_onsignup");
$endregistration_day = get_input("endregistration_day");
$max_attendees = get_input("max_attendees");
$waiting_list = get_input("waiting_list");
$access_id = get_input("access_id");
$with_program = get_input("with_program");
$delete_current_icon = get_input("delete_current_icon");
$registration_needed = get_input("registration_needed");
$register_nologin = get_input("register_nologin");

$event_interested = get_input("event_interested");
$event_presenting = get_input("event_presenting");
$event_exhibiting = get_input("event_exhibiting");
$event_organizing = get_input("event_organizing");

$registration_completed = get_input("registration_completed");

$waiting_list_enabled = get_input("waiting_list_enabled");

$start_time_hours = get_input("start_time_hours");
$start_time_minutes = get_input("start_time_minutes");
$start_time = mktime($start_time_hours, $start_time_minutes, 1, 0, 0, 0);

if (!empty($end_day)) {
	$end_date = explode('-', $end_day);
	$end_ts = mktime($end_time_hours, $end_time_minutes, 1, $end_date[1], $end_date[2], $end_date[0]);
}

if (!empty($start_day)) {
	$date = explode('-',$start_day);
	$start_day = mktime(0,0,1,$date[1],$date[2],$date[0]);

	$start_ts = mktime($start_time_hours, $start_time_minutes, 1, $date[1], $date[2], $date[0]);

	if (!empty($end_ts) && ($end_ts < $start_ts)) {
		register_error(elgg_echo('event_manager:action:event:edit:end_before_start'));
		forward(REFERER);
	}
}

if (!empty($endregistration_day)) {
	$date_endregistration_day = explode('-',$endregistration_day);
	$endregistration_day = mktime(0,0,1,$date_endregistration_day[1],$date_endregistration_day[2],$date_endregistration_day[0]);
}

if (!empty($guid) && $entity = get_entity($guid)) {
	if ($entity->getSubtype() == Event::SUBTYPE) {
		$event = $entity;
	}
}

if ($event_type == '-') {
	$event_type = '';
}

if ($region == '-') {
	$region = '';
}

if (!empty($max_attendees) && !is_numeric($max_attendees)) {
	$max_attendees = "";
}

if (empty($title) || empty($start_day) || empty($end_ts)) {
	register_error(elgg_echo("event_manager:action:event:edit:error_fields"));
	forward(REFERER);
}

$newEvent = false;
if (!isset($event)) {
	$newEvent = true;
	$event = new Event();
}

$event->title = $title;
$event->description = $description;
$event->container_guid = $container_guid;
$event->access_id = $access_id;
$event->save();

$event->setLocation($location);
$event->setLatLong($latitude, $longitude);
$event->tags = string_to_tag_array($tags);

if ($newEvent) {
	// add event create river event
	elgg_create_river_item([
		'view' => 'river/object/event/create',
		'action_type' => 'create',
		'subject_guid' => elgg_get_logged_in_user_guid(),
		'object_guid' => $event->getGUID(),
	]);
}

$event->shortdescription = $shortdescription;
$event->comments_on = $comments_on;
$event->registration_ended = $registration_ended;
$event->registration_needed = $registration_needed;
$event->show_attendees = $show_attendees;
$event->notify_onsignup = $notify_onsignup;
$event->max_attendees = $max_attendees;
$event->waiting_list = $waiting_list;
$event->venue = $venue;
$event->contact_details = $contact_details;
$event->region = $region;
$event->website = $website;
$event->event_type = $event_type;
$event->organizer = $organizer;
$event->fee = $fee;
$event->start_day = $start_day;
$event->start_time = $start_time;

if (!empty($end_ts)) {
	$event->end_ts = $end_ts;
}

$event->with_program = $with_program;
$event->endregistration_day = $endregistration_day;
$event->register_nologin = $register_nologin;

$event->event_interested = $event_interested;
$event->event_presenting = $event_presenting;
$event->event_exhibiting = $event_exhibiting;
$event->event_organizing = $event_organizing;

$event->waiting_list_enabled = $waiting_list_enabled;
$event->registration_completed = $registration_completed;

$eventDays = $event->getEventDays();
if ($with_program && !$eventDays) {
	$eventDay = new \ColdTrick\EventManager\Event\Day();
	$eventDay->title = 'Event day 1';
	$eventDay->container_guid = $event->getGUID();
	$eventDay->owner_guid = $event->getGUID();
	$eventDay->access_id = $event->access_id;
	$eventDay->save();
	$eventDay->date = $event->start_day;
	$eventDay->addRelationship($event->getGUID(), 'event_day_relation');

	$eventSlot = new \ColdTrick\EventManager\Event\Slot();
	$eventSlot->title = 'Activity title';
	$eventSlot->description = 'Activity description';
	$eventSlot->container_guid = $event->container_guid;
	$eventSlot->owner_guid = $event->owner_guid;
	$eventSlot->access_id = $event->access_id;
	$eventSlot->save();

	$eventSlot->location = $event->location;
	$eventSlot->start_time = mktime('08', '00', 1, 0, 0, 0);
	$eventSlot->end_time = mktime('09', '00', 1, 0, 0, 0);
	$eventSlot->addRelationship($eventDay->getGUID(), 'event_day_slot_relation');
}

$event->setAccessToOwningObjects($access_id);

$icon_sizes = elgg_get_config('icon_sizes');
$icon_sizes['event_banner'] = ['w' => 1920, 'h' => 1080, 'square' => false, 'upscale' => false];

$icon_file = get_resized_image_from_uploaded_file('icon', 100, 100);

if ($icon_file) {
	// create icons

	$fh = new \ElggFile();
	$fh->owner_guid = $event->guid;

	foreach ($icon_sizes as $icon_name => $icon_info) {
		$icon_file = get_resized_image_from_uploaded_file("icon", $icon_info["w"], $icon_info["h"], $icon_info["square"], $icon_info["upscale"]);

		if ($icon_file) {
			$fh->setFilename($icon_name . ".jpg");

			if ($fh->open("write")) {
				$fh->write($icon_file);
				$fh->close();
			}
		}
	}

	$event->icontime = time();
} elseif ($delete_current_icon) {
	$fh = new \ElggFile();
	$fh->owner_guid = $event->guid;

	foreach ($icon_sizes as $name => $info) {
		$fh->setFilename($name . ".jpg");

		if ($fh->exists()) {
			$fh->delete();
		}
	}

	unset($event->icontime);
}

// added because we need an update event
$event->save();

// remove sticky form entries
elgg_clear_sticky_form('event');

system_message(elgg_echo("event_manager:action:event:edit:ok"));

forward($event->getURL());
