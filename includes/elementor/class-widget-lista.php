<?php
/**
 * Widget Elementor: Lista de lojas.
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_Widget_Lista
 */
class VB_OE_Widget_Lista extends VB_OE_Widget_Base {

	/**
	 * Nome interno.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'vb_oe_lista';
	}

	/**
	 * Título.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'Onde Encontrar — Lista';
	}

	/**
	 * Ícone.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-bullet-list';
	}

	/**
	 * Controles.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'conteudo',
			array(
				'label' => 'Lista',
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->controle_grupo();

		$this->add_control(
			'ajuda',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => '<p>Lista das lojas encontradas. Ao clicar em um item, o mapa centraliza nesse ponto.</p>',
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
		echo $this->front()->sc_lista( array( 'grupo' => $s['grupo'] ) );
	}
}
