<?php

namespace Groundhogg\Admin\Dashboard\Widgets;

use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */
abstract class Circle_Graph extends Reporting_Widget {

	protected $dataset;

	/**
	 * Output the widget HTML
	 */
	public function widget() {
		/*
		 * Get Data from the Override method.
		 */
		$data       = $this->get_data();
		$data_chart = $this->get_pie_chart_data();

		$is_empty = array_sum( wp_list_pluck( $data, 'data' ) ) === 0;

		if ( ! $is_empty ):

			if ( is_array( $data ) ) {
				$data = wp_json_encode( $data );
			}

			if ( is_array( $data_chart ) ) {
				$data_chart = wp_json_encode( $data_chart );
			}

			?>
            <div class="report">
                <script type="text/javascript">

                    jQuery(function ($) {
                        var dataSet = <?php echo $data; ?>;

                        var options = {
                            grid: {
                                clickable: true,
                                hoverable: true
                            },
                            series: {
                                pie: {
                                    show: true,
                                    // radius: 1,
                                    label: {
                                        show: true,
                                        radius: 7 / 8,
                                        formatter: function (label, series) {
                                            return "<div style='font-size:8pt; text-align:center; padding:2px; color:white;'>" + label + ' (' + Math.round(series.percent) + "%)</div>";
                                        },
                                        background: {
                                            opacity: 0.5,
                                            color: '#000'
                                        }
                                    }
                                }
                            },
                        };

                        if ($("#graph-<?php echo $this->get_id(); ?>").width() > 0) {

                            $.plot($("#graph-<?php echo $this->get_id(); ?>"), dataSet, options);

                            $('#graph-<?php echo $this->get_id(); ?>').bind("plotclick", function (event, pos, obj) {
                                try {
                                    window.location.replace(dataSet[obj.seriesIndex].url);
                                } catch (e) {
                                }
                            });

                        }
                    });

                </script>
                <div id="graph-<?php echo $this->get_id(); ?>" style="width:auto;height: 300px"></div>


                <canvas id="chart-<?php echo $this->get_id(); ?>" style="width:auto;height: 250px;"></canvas>

                <script>

                    var ctx = document.getElementById('<?php echo 'chart-' . $this->get_id();  ?>');
                    var myChart = new Chart(ctx, {
                        type: 'pie',
                        data:  <?php echo $data_chart; ?>

                    });

                </script>

            </div>
			<?php

			$this->extra_widget_info();

		else:

			echo Plugin::$instance->utils->html->description( __( 'No data to show yet.', 'groundhogg' ) );

		endif;
	}

	/**
	 * Any additional information needed for the widget.
	 *
	 * @return void
	 */
	abstract protected function extra_widget_info();

	/**
	 * Normalize a datum
	 *
	 * @param $item_key
	 * @param $item_data
	 *
	 * @return array
	 */
	abstract protected function normalize_datum( $item_key, $item_data );

	/**
	 * Format the data into a chart friendly format.
	 *
	 * @param $data array
	 *
	 * @return array
	 */
	protected function normalize_data( $data ) {
		$dataset = [];

		foreach ( $data as $key => $datum ) {
			$dataset[] = $this->normalize_datum( $key, $datum );
		}

		$dataset = array_values( $dataset );

//        var_dump( $dataset );

		usort( $dataset, array( $this, 'sort' ) );

		/* Pair down the results to largest 10 */
		if ( count( $dataset ) > 10 ) {

			$other_dataset = [
				'label' => __( 'Other' ),
				'data'  => 0,
				'url'   => '#'
			];

			$other   = array_slice( $dataset, 10 );
			$dataset = array_slice( $dataset, 0, 10 );

			foreach ( $other as $c_data ) {
				$other_dataset[ 'data' ] += $c_data[ 'data' ];
			}

			$dataset[] = $other_dataset;

		}

		usort( $dataset, array( $this, 'sort' ) );

		$this->dataset = $dataset;

		return $dataset;
	}

	public function sort( $a, $b ) {
		return $b[ 'data' ] - $a[ 'data' ];
	}

	public function get_pie_chart_data() {
		$data = $this->get_data();

		$mydata = [];

		$label_chart = [];
		$bg_color = [];

		foreach ( $data as $d ) {

			$label_chart[] = $d[ 'label' ];
			$mydata[] = $d[ 'data' ];
			$bg_color[] = $this -> random_color();
		}

		return [
			'labels'   => $label_chart,
			'datasets' => [
				0 =>[
						'data' => $mydata,
					    'backgroundColor' =>$bg_color
					],
			]
		];


	}


	function random_color_part() {
		return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
	}

	function random_color() {
		return  "#" .$this->random_color_part() . $this->random_color_part() . $this->random_color_part();
	}

}