<?php
/**
 * Widget Elementor: Mapa.
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_Widget_Mapa
 */
class VB_OE_Widget_Mapa extends VB_OE_Widget_Base {

	/**
	 * Nome interno.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'vb_oe_mapa';
	}

	/**
	 * Título no painel.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'Onde Encontrar — Mapa';
	}

	/**
	 * Ícone.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-google-maps';
	}

	/**
	 * Controles.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'conteudo',
			array(
				'label' => 'Mapa',
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->controle_grupo();

		$this->add_control(
			'altura',
			array(
				'label'   => 'Altura do mapa (pixels)',
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 480,
				'min'     => 200,
				'max'     => 900,
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Renderiza no site.
	 */
	protected function render() {
		$s = $this->get_settings_for_display();
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->front()->sc_mapa(
			array(
				'grupo'  => $s['grupo'],
				'altura' => $s['altura'],
			)
		);
	}
}
