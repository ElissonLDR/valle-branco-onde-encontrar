<?php
/**
 * Widget Elementor: Filtro.
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_Widget_Filtro
 */
class VB_OE_Widget_Filtro extends VB_OE_Widget_Base {

	/**
	 * Nome interno.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'vb_oe_filtro';
	}

	/**
	 * Título.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'Onde Encontrar — Filtro';
	}

	/**
	 * Ícone.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-filter';
	}

	/**
	 * Controles.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'conteudo',
			array(
				'label' => 'Filtro',
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->controle_grupo();

		$this->add_control(
			'ajuda',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => '<p>Filtro por cidade e botão “Usar minha localização”. Funciona junto com o mapa e a busca do mesmo grupo.</p>',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		$s = $this->get_settings_for_display();
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->front()->sc_filtro( array( 'grupo' => $s['grupo'] ) );
	}
}
