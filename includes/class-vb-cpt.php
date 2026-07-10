<?php
/**
 * Custom Post Types: produto e estabelecimento.
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_CPT
 */
class VB_OE_CPT {

	/**
	 * Registra os tipos de conteúdo.
	 */
	public static function register() {
		self::register_produto();
		self::register_estabelecimento();
	}

	/**
	 * CPT Produto.
	 */
	private static function register_produto() {
		$labels = array(
			'name'               => 'Produtos',
			'singular_name'      => 'Produto',
			'menu_name'          => 'Produtos VB',
			'add_new'            => 'Adicionar novo',
			'add_new_item'       => 'Adicionar produto',
			'edit_item'          => 'Editar produto',
			'new_item'           => 'Novo produto',
			'view_item'          => 'Ver produto',
			'search_items'       => 'Buscar produtos',
			'not_found'          => 'Nenhum produto encontrado',
			'not_found_in_trash' => 'Nenhum produto na lixeira',
		);

		register_post_type(
			'vb_produto',
			array(
				'labels'              => $labels,
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_rest'        => true,
				'has_archive'         => false,
				'exclude_from_search' => true,
				'capability_type'     => 'post',
				'hierarchical'        => false,
				'menu_icon'           => 'dashicons-products',
				'supports'            => array( 'title', 'thumbnail', 'excerpt' ),
				'rewrite'             => array( 'slug' => 'produto-vb' ),
			)
		);
	}

	/**
	 * CPT Estabelecimento (ponto de venda).
	 */
	private static function register_estabelecimento() {
		$labels = array(
			'name'               => 'Estabelecimentos',
			'singular_name'      => 'Estabelecimento',
			'menu_name'          => 'Estabelecimentos',
			'add_new'            => 'Adicionar novo',
			'add_new_item'       => 'Adicionar estabelecimento',
			'edit_item'          => 'Editar estabelecimento',
			'new_item'           => 'Novo estabelecimento',
			'view_item'          => 'Ver estabelecimento',
			'search_items'       => 'Buscar estabelecimentos',
			'not_found'          => 'Nenhum estabelecimento encontrado',
			'not_found_in_trash' => 'Nenhum estabelecimento na lixeira',
		);

		register_post_type(
			'vb_estabelecimento',
			array(
				'labels'              => $labels,
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_rest'        => true,
				'has_archive'         => false,
				'exclude_from_search' => true,
				'capability_type'     => 'post',
				'hierarchical'        => false,
				'menu_icon'           => 'dashicons-store',
				'supports'            => array( 'title' ),
				'rewrite'             => array( 'slug' => 'estabelecimento-vb' ),
			)
		);
	}
}
