<?php
/**
 * Relação produto × estabelecimento (helpers).
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_Relacao
 */
class VB_OE_Relacao {

	/**
	 * Busca post pelo título exato.
	 *
	 * @param string $post_type Tipo.
	 * @param string $titulo    Título.
	 * @return int
	 */
	private static function buscar_por_titulo( $post_type, $titulo ) {
		$query = new WP_Query(
			array(
				'post_type'              => $post_type,
				'title'                  => $titulo,
				'posts_per_page'         => 1,
				'post_status'            => 'any',
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		return ! empty( $query->posts ) ? (int) $query->posts[0] : 0;
	}

	/**
	 * Busca ou cria produto pelo SKU ou nome.
	 *
	 * @param array $dados Dados do produto.
	 * @return int ID do produto.
	 */
	public static function encontrar_ou_criar_produto( $dados ) {
		$sku  = isset( $dados['sku'] ) ? sanitize_text_field( $dados['sku'] ) : '';
		$nome = isset( $dados['nome'] ) ? sanitize_text_field( $dados['nome'] ) : '';

		$post_id = 0;

		if ( $sku ) {
			$encontrados = get_posts(
				array(
					'post_type'      => 'vb_produto',
					'posts_per_page' => 1,
					'post_status'    => 'any',
					'meta_key'       => '_vb_sku',
					'meta_value'     => $sku,
					'fields'         => 'ids',
				)
			);
			if ( ! empty( $encontrados ) ) {
				$post_id = (int) $encontrados[0];
			}
		}

		if ( ! $post_id && $nome ) {
			$post_id = self::buscar_por_titulo( 'vb_produto', $nome );
		}

		if ( ! $post_id ) {
			if ( ! $nome && ! $sku ) {
				return 0;
			}
			$post_id = wp_insert_post(
				array(
					'post_type'   => 'vb_produto',
					'post_title'  => $nome ? $nome : $sku,
					'post_status' => 'publish',
				),
				true
			);
			if ( is_wp_error( $post_id ) ) {
				return 0;
			}
		} elseif ( $nome && get_the_title( $post_id ) !== $nome ) {
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $nome,
				)
			);
		}

		if ( $sku ) {
			update_post_meta( $post_id, '_vb_sku', $sku );
		}
		if ( ! empty( $dados['marca'] ) ) {
			update_post_meta( $post_id, '_vb_marca', sanitize_text_field( $dados['marca'] ) );
		}
		if ( ! empty( $dados['categoria'] ) ) {
			update_post_meta( $post_id, '_vb_categoria', sanitize_text_field( $dados['categoria'] ) );
		}

		return (int) $post_id;
	}

	/**
	 * Busca ou cria estabelecimento.
	 *
	 * @param array $dados Dados do local.
	 * @return int ID do estabelecimento.
	 */
	public static function encontrar_ou_criar_estabelecimento( $dados ) {
		$nome       = isset( $dados['nome'] ) ? sanitize_text_field( $dados['nome'] ) : '';
		$cidade     = isset( $dados['cidade'] ) ? sanitize_text_field( $dados['cidade'] ) : '';
		$endereco   = isset( $dados['endereco'] ) ? sanitize_text_field( $dados['endereco'] ) : '';
		$codigo_sap = isset( $dados['codigo_sap'] ) ? sanitize_text_field( $dados['codigo_sap'] ) : '';

		if ( ! $nome && ! $codigo_sap ) {
			return 0;
		}

		$post_id = 0;

		// Prioridade: CardCode do SAP (OCRD).
		if ( $codigo_sap ) {
			$por_codigo = get_posts(
				array(
					'post_type'      => 'vb_estabelecimento',
					'posts_per_page' => 1,
					'post_status'    => 'any',
					'meta_key'       => '_vb_codigo_sap',
					'meta_value'     => $codigo_sap,
					'fields'         => 'ids',
				)
			);
			if ( ! empty( $por_codigo ) ) {
				$post_id = (int) $por_codigo[0];
			}
		}

		if ( ! $post_id && $nome ) {
			$query = new WP_Query(
				array(
					'post_type'      => 'vb_estabelecimento',
					'posts_per_page' => 1,
					'post_status'    => 'any',
					'title'          => $nome,
					'meta_query'     => $cidade
						? array(
							array(
								'key'   => '_vb_cidade',
								'value' => $cidade,
							),
						)
						: array(),
					'fields'         => 'ids',
				)
			);
			$post_id = $query->have_posts() ? (int) $query->posts[0] : self::buscar_por_titulo( 'vb_estabelecimento', $nome );
		}

		if ( ! $post_id ) {
			$post_id = wp_insert_post(
				array(
					'post_type'   => 'vb_estabelecimento',
					'post_title'  => $nome ? $nome : $codigo_sap,
					'post_status' => 'publish',
				),
				true
			);
			if ( is_wp_error( $post_id ) ) {
				return 0;
			}
		} elseif ( $nome ) {
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $nome,
				)
			);
		}

		$metas = array(
			'_vb_codigo_sap' => $codigo_sap,
			'_vb_tipo'       => isset( $dados['tipo'] ) ? sanitize_key( $dados['tipo'] ) : 'mercado',
			'_vb_endereco'   => $endereco,
			'_vb_cidade'     => $cidade,
			'_vb_uf'         => isset( $dados['uf'] ) ? strtoupper( sanitize_text_field( $dados['uf'] ) ) : '',
			'_vb_cep'        => isset( $dados['cep'] ) ? sanitize_text_field( $dados['cep'] ) : '',
			'_vb_lat'        => isset( $dados['lat'] ) && '' !== $dados['lat'] ? (string) floatval( $dados['lat'] ) : '',
			'_vb_lng'        => isset( $dados['lng'] ) && '' !== $dados['lng'] ? (string) floatval( $dados['lng'] ) : '',
		);

		foreach ( $metas as $chave => $valor ) {
			if ( '' !== $valor && null !== $valor ) {
				update_post_meta( $post_id, $chave, $valor );
			}
		}

		return (int) $post_id;
	}

	/**
	 * Processa um item vindo do n8n (nota fiscal → mapa).
	 *
	 * @param array $payload Dados da nota.
	 * @return array|WP_Error
	 */
	public static function sincronizar_da_nota( $payload ) {
		// Aceita JSON amigável ou campos SAP (CardCode, ItemCode, DocNum…).
		$payload = VB_OE_SAP::normalizar_item( $payload );

		$produto = self::encontrar_ou_criar_produto( $payload['produto'] ?? array() );
		$local   = self::encontrar_ou_criar_estabelecimento( $payload['estabelecimento'] ?? array() );

		if ( ! $produto || ! $local ) {
			return new WP_Error(
				'vb_oe_dados_invalidos',
				'Produto ou estabelecimento inválido. Envie ItemCode/ItemName e CardCode/CardName.',
				array( 'status' => 400 )
			);
		}

		$id = VB_OE_Database::upsert_relacao(
			array(
				'produto_id'         => $produto,
				'estabelecimento_id' => $local,
				'origem'             => 'n8n',
				'nota_fiscal'        => $payload['nota_fiscal'] ?? '',
				'status'             => 'ativo',
				'observacao'         => $payload['observacao'] ?? '',
				'data_entrada'       => $payload['data_entrada'] ?? '',
			)
		);

		return array(
			'relacao_id'         => $id,
			'produto_id'         => $produto,
			'estabelecimento_id' => $local,
			'codigo_sap'         => $payload['estabelecimento']['codigo_sap'] ?? '',
			'sku'                => $payload['produto']['sku'] ?? '',
			'mensagem'           => 'Produto vinculado ao local com sucesso.',
		);
	}
}
