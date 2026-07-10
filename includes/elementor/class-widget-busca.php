<?php
/**
 * Widget Elementor: Busca.
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_Widget_Busca
 */
class VB_OE_Widget_Busca extends VB_OE_Widget_Base {

	/**
	 * Nome interno.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'vb_oe_busca';
	}

	/**
	 * Título.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'Onde Encontrar — Busca';
	}

	/**
	 * Ícone.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-search';
	}

	/**
	 * Controles.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'conteudo',
			array(
				'label' => 'Busca',
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->controle_grupo();

		$this->add_control(
			'ajuda',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => '<p>Campo de texto para digitar produto, cidade ou nome da loja. Atualiza o mapa automaticamente.</p>',
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
		echo $this->front()->sc_busca( array( 'grupo' => $s['grupo'] ) );
	}
}
