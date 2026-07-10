<?php
/**
 * Integração com Elementor (widgets separados).
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_Elementor
 */
class VB_OE_Elementor {

	/**
	 * Liga os hooks se o Elementor existir.
	 */
	public function hooks() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'categoria' ) );
		add_action( 'elementor/widgets/register', array( $this, 'registrar_widgets' ) );
	}

	/**
	 * Categoria "Valle Branco" no painel do Elementor.
	 *
	 * @param \Elementor\Elements_Manager $manager Manager.
	 */
	public function categoria( $manager ) {
		$manager->add_category(
			'valle-branco',
			array(
				'title' => 'Valle Branco',
				'icon'  => 'fa fa-map-marker',
			)
		);
	}

	/**
	 * Registra os 3 widgets (+ lista).
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Manager.
	 */
	public function registrar_widgets( $widgets_manager ) {
		require_once VB_OE_PATH . 'includes/elementor/class-widget-base.php';
		require_once VB_OE_PATH . 'includes/elementor/class-widget-mapa.php';
		require_once VB_OE_PATH . 'includes/elementor/class-widget-busca.php';
		require_once VB_OE_PATH . 'includes/elementor/class-widget-filtro.php';
		require_once VB_OE_PATH . 'includes/elementor/class-widget-lista.php';

		$widgets_manager->register( new VB_OE_Widget_Mapa() );
		$widgets_manager->register( new VB_OE_Widget_Busca() );
		$widgets_manager->register( new VB_OE_Widget_Filtro() );
		$widgets_manager->register( new VB_OE_Widget_Lista() );
	}
}
