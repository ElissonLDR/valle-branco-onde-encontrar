<?php
/**
 * Base dos widgets Elementor do Onde Encontrar.
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_Widget_Base
 */
abstract class VB_OE_Widget_Base extends \Elementor\Widget_Base {

	/**
	 * Categoria no Elementor.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'valle-branco' );
	}

	/**
	 * Controle comum: grupo (liga as peças).
	 */
	protected function controle_grupo() {
		$this->add_control(
			'grupo',
			array(
				'label'       => 'Grupo',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => 'padrao',
				'description' => 'Use o mesmo nome em Mapa, Busca e Filtro da mesma página (ex.: padrao). Assim eles funcionam juntos.',
			)
		);
	}

	/**
	 * Front helper.
	 *
	 * @return VB_OE_Frontend
	 */
	protected function front() {
		static $front = null;
		if ( null === $front ) {
			$front = new VB_OE_Frontend();
		}
		return $front;
	}
}
