<?php

namespace ClimbUI\Service;

require_once __DIR__ . '/../../support/lib/vendor/autoload.php';
require_once __DIR__ . '/../Component/tabs.content.php';

use Approach\Render\HTML;
use Approach\Service\Service;
use Approach\Service\target;
use Approach\Service\format;
use Approach\Service\flow;
use ClimbUI\Component;

class Server extends Service
{
	public static $registrar = [];

	public static function View($action)
	{
		$title = $action['Climb']['title'];

		$climbForm = $action['Climb'];
		$surveyForm = $action['Survey'];
		$describeForm = $action['Describe'];

		$requirements = [];
		foreach ($climbForm as $key => $value) {
			if (substr($key, 0, 11) == 'requirement') {
				$requirements[] = $value;
			}
		}

		$interests = [];
		$obstructions = [];
		foreach ($surveyForm as $key => $value) {
			if (substr($key, 0, 8) == 'interest') {
				$interests[] = $value;
			}
			if (substr($key, 0, 11) == 'obstruction') {
				$obstructions[] = $value;
			}
		}

		$time_intent = $action['Time']['time_intent'];
		$energy_intent = $action['Time']['energy_req'];
		$resources_intent = $action['Time']['resources'];

		$work = $action['Work']['document_progress'];
		$budget_res = $describeForm['budget_res'];

		$d_interests = [];
		$hazards = [];
		foreach ($describeForm as $key => $value) {
			if (substr($key, 0, 10) == 'd_interest') {
				$d_interests[] = $value;
			}
			if (substr($key, 0, 6) == 'hazard') {
				$hazards[] = $value;
			}
		}

		$div = new HTML(tag: 'div', classes: ['p-3']);
		$div[] = new HTML(tag: 'h1', content: 'Form Submitted!');
		$div[] = $climbRes = new HTML(tag: 'div');
		$climbRes->content = 'Title: ' . $title . '<br>Requirements: ' . implode(', ', $requirements);
		$div[] = $surveyRes = new HTML(tag: 'div');
		$surveyRes->content = 'Interests: ' . implode(', ', $interests) . '<br>Obstructions: ' . implode(', ', $obstructions);
		$div[] = $timeRes = new HTML(tag: 'div');
		$timeRes->content = 'Time Intent: ' . $time_intent . '<br>Energy Intent: ' . $energy_intent . '<br>Resources Intent: ' . $resources_intent;
		$div[] = $workRes = new HTML(tag: 'div');
		$workRes->content = 'Work: ' . $work . '<br>Budget: ' . $budget_res;
		$div[] = $describeRes = new HTML(tag: 'div');
		$describeRes->content = 'Interests: ' . implode(', ', $d_interests) . '<br>Hazards: ' . implode(', ', $hazards);

		return [[
			'REFRESH' => ['#result' => $div->render()],
		]];
	}

	public static function Click($action)
	{
		$name = $action['support']['name'];
		$tabsForm = Component\getTabsForm();

		return [[
			'REFRESH' => ['#some_content' => $tabsForm->render()],
		]];
	}

	public function __construct(
		flow $flow = flow::in,
		bool $auto_dispatch = false,
		?format $format_in = format::json,
		?format $format_out = format::json,
		?target $target_in = target::stream,
		?target $target_out = target::stream,
		$input = [Service::STDIN],
		$output = [Service::STDOUT],
		mixed $metadata = [],
		bool $register_connection = true
	) {

		self::$registrar['Climb']['Save'] = function ($context) {
			return self::View($context);
		};
		self::$registrar['Climb']['Click'] = function ($context) {
			return self::Click($context);
		};
		parent::__construct($flow, $auto_dispatch, $format_in, $format_out, $target_in, $target_out, $input, $output, $metadata);
	}

	public function Process(?array $payload = null): void
	{
		$payload = $payload ?? $this->payload;

		$action = $payload[0]['support'];

		foreach ($payload[0] as $verb => $intent) {
			foreach ($intent as $scope => $instruction) {
				foreach ($instruction as $command => $context) {
					if ($command == 'Climb') {
						// print_r($command);
						$this->payload = self::$registrar[$command][$context]($action);
					}
				}
			}
		}
	}
}
