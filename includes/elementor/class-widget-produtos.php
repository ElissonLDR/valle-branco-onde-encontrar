<?php
/**
 * Widget Elementor: Produtos na rede.
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_Widget_Produtos
 */
class VB_OE_Widget_Produtos extends VB_OE_Widget_Base {

	/**
	 * Nome interno.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'vb_oe_produtos';
	}

	/**
	 * Título.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'Onde Encontrar — Produtos';
	}

	/**
	 * Ícone.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-products';
	}

	/**
	 * Controles.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'conteudo',
			array(
				'label' => 'Produtos',
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->controle_grupo();

		$this->add_control(
			'ajuda',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => '<p>Lista de produtos em chips. Ao clicar, o mapa e a lista mostram só os locais com aquele produto.</p>',
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
		echo $this->front()->sc_produtos( array( 'grupo' => $s['grupo'] ) );
	}
}
