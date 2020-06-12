<?php

namespace Groundhogg\Reporting\New_Reports;

abstract class Base_Chart_Report extends Base_Report {

	/**
	 * @return array[]
	 */
	abstract protected function get_datasets();

	/**
	 * @return string
	 */
	abstract protected function get_type();

	/**
	 * @return array[]
	 */
	abstract protected function get_options();


	/**
	 * Get the report data
	 *
	 * @return mixed
	 */
	public function get_data() {

		return [
			'type'  => 'chart',
			'chart' => [
				'type'    => $this->get_type(),
				'data'    => $this->get_datasets(),
				'options' => $this->get_options()
			]
		];
	}


	public function get_pie_chart_options() {
		return [
			'legend' => [
				'display' => false
			],
//			'responsive' => true,
			'tooltips'   => [
				'backgroundColor' => '#FFF',
				'bodyFontColor'   => '#000',
				'borderColor'     => '#727272',
				'borderWidth'     => 2,
				'titleFontColor'  => '#000'
			]
		];
	}


}
