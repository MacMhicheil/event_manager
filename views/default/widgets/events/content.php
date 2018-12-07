<?php

/* @var $widget ElggWidget */
$widget = elgg_extract('entity', $vars);

$num_display = (int) $widget->num_display;
if ($num_display < 1) {
	$num_display = 5;
}

$event_options = [
	'limit' => $num_display,
	'pagination' => false,
];

$owner = $widget->getOwnerEntity();

$more_link = elgg_generate_url('default:object:event');

switch ($owner->getType()) {
	case 'group':
		$event_options['container_guid'] = $owner->guid;
		$more_link = elgg_generate_url('collection:object:event:group', [
			'guid' => $owner->guid,
		]);
		break;
	case 'user':
		$event_options['user_guid'] = $owner->guid;
		switch ($widget->type_to_show) {
			case 'owning':
				$event_options['owning'] = true;
				$more_link = elgg_generate_url('collection:object:event:owner', [
					'username' => $owner->username,
				]);
				break;
			case 'attending':
				$event_options['meattending'] = true;
				$more_link = elgg_generate_url('collection:object:event:attending', [
					'username' => $owner->username,
				]);
				break;
		}
		break;
}

$group_guid = $widget->group_guid;
if (is_array($group_guid)) {
	$group_guid = $group_guid[0];
}

if (!empty($group_guid)) {
	$event_options['container_guid'] = $group_guid;
}

$options = event_manager_get_default_list_options($event_options);
$options['no_results'] = false;

$content = elgg_list_entities($options);
if (empty($content)) {
	echo elgg_echo('event_manager:list:noresults');
	return;
}

echo $content;

echo elgg_format_element('div', ['class' => 'elgg-widget-more'], elgg_view('output/url', [
	'text' => elgg_echo('event_manager:group:more'),
	'href' => $more_link,
]));
