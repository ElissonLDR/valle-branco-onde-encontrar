<?php
/**
 * API REST para o n8n e para o mapa no front.
 *
 * Endpoints:
 * - POST /wp-json/valle-branco/v1/sincronizar  (n8n, com chave)
 * - GET  /wp-json/valle-branco/v1/locais       (público, mapa)
 * - GET  /wp-json/valle-branco/v1/relatorio    (admin)
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_REST_API
 */
class VB_OE_REST_API {

	const NAMESPACE = 'valle-branco/v1';

	/**
	 * Registra rotas.
	 */
	public function hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Rotas da API.
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/sincronizar',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'sincronizar' ),
				'permission_callback' => array( $this, 'check_api_key' ),
			)
		);

		// Alias mais claro para o n8n (mesmo comportamento).
		register_rest_route(
			self::NAMESPACE,
			'/webhook',
			array(
				'methods'             => array( 'POST', 'GET' ),
				'callback'            => array( $this, 'sincronizar' ),
				'permission_callback' => array( $this, 'check_api_key' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/sincronizar-lote',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'sincronizar_lote' ),
				'permission_callback' => array( $this, 'check_api_key' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/locais',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'listar_locais' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/relatorio',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'relatorio' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Valida a chave de API (header X-VB-API-Key ou ?api_key=).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function check_api_key( $request ) {
		$chave_salva = get_option( 'vb_oe_api_key', '' );
		$chave_envio = $request->get_header( 'X-VB-API-Key' );

		if ( ! $chave_envio ) {
			$chave_envio = $request->get_param( 'api_key' );
		}

		if ( ! $chave_salva || ! hash_equals( $chave_salva, (string) $chave_envio ) ) {
			return new WP_Error(
				'vb_oe_nao_autorizado',
				'Chave de API inválida.',
				array( 'status' => 401 )
			);
		}

		return true;
	}

	/**
	 * Um item da nota fiscal → mapa.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function sincronizar( $request ) {
		$payload = $request->get_json_params();
		if ( empty( $payload ) ) {
			$payload = $request->get_params();
		}

		$resultado = VB_OE_Relacao::sincronizar_da_nota( $payload );

		if ( is_wp_error( $resultado ) ) {
			return $resultado;
		}

		return rest_ensure_response( $resultado );
	}

	/**
	 * Vários itens de uma vez (atualização diária).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function sincronizar_lote( $request ) {
		$payload = $request->get_json_params();
		$itens   = isset( $payload['itens'] ) && is_array( $payload['itens'] ) ? $payload['itens'] : array();
		$ok      = array();
		$erros   = array();

		foreach ( $itens as $i => $item ) {
			$resultado = VB_OE_Relacao::sincronizar_da_nota( $item );
			if ( is_wp_error( $resultado ) ) {
				$erros[] = array(
					'indice'  => $i,
					'mensagem'=> $resultado->get_error_message(),
				);
			} else {
				$ok[] = $resultado;
			}
		}

		return rest_ensure_response(
			array(
				'sucesso' => count( $ok ),
				'erros'   => count( $erros ),
				'itens'   => $ok,
				'falhas'  => $erros,
			)
		);
	}

	/**
	 * Locais para o mapa (público, cacheável).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function listar_locais( $request ) {
		$produto_id = absint( $request->get_param( 'produto_id' ) );

		$posts = get_posts(
			array(
				'post_type'      => 'vb_estabelecimento',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$locais = array();

		foreach ( $posts as $post ) {
			$lat = get_post_meta( $post->ID, '_vb_lat', true );
			$lng = get_post_meta( $post->ID, '_vb_lng', true );

			if ( '' === $lat || '' === $lng ) {
				continue;
			}

			$produtos_raw = VB_OE_Database::produtos_do_estabelecimento( $post->ID );
			$produtos     = array();

			foreach ( $produtos_raw as $p ) {
				if ( $produto_id && (int) $p->produto_id !== $produto_id ) {
					continue;
				}
				$produtos[] = array(
					'id'   => (int) $p->produto_id,
					'nome' => $p->nome,
				);
			}

			if ( $produto_id && empty( $produtos ) ) {
				continue;
			}

			$cidade = get_post_meta( $post->ID, '_vb_cidade', true );
			$uf     = get_post_meta( $post->ID, '_vb_uf', true );

			$locais[] = array(
				'id'        => $post->ID,
				'nome'      => wp_specialchars_decode( $post->post_title, ENT_QUOTES ),
				'tipo'      => get_post_meta( $post->ID, '_vb_tipo', true ),
				'endereco'  => get_post_meta( $post->ID, '_vb_endereco', true ),
				'cidade'    => $uf ? $cidade . '/' . $uf : $cidade,
				'lat'       => (float) $lat,
				'lng'       => (float) $lng,
				'produtos'  => $produtos,
			);
		}

		$response = rest_ensure_response( array( 'locais' => $locais ) );
		$response->header( 'Cache-Control', 'public, max-age=300' );
		return $response;
	}

	/**
	 * Relatório para o painel (JSON).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function relatorio( $request ) {
		$linhas = VB_OE_Database::listar(
			array(
				'status'             => sanitize_key( (string) $request->get_param( 'status' ) ),
				'produto_id'         => absint( $request->get_param( 'produto_id' ) ),
				'estabelecimento_id' => absint( $request->get_param( 'estabelecimento_id' ) ),
				'busca'              => sanitize_text_field( (string) $request->get_param( 'busca' ) ),
				'limit'              => 200,
			)
		);

		$settings = get_option( 'vb_oe_settings', array() );
		$alerta   = isset( $settings['dias_alerta'] ) ? (int) $settings['dias_alerta'] : 90;
		$saida    = array();

		foreach ( $linhas as $linha ) {
			$dias = VB_OE_Database::dias_no_local( $linha->data_entrada );
			$saida[] = array(
				'id'                   => (int) $linha->id,
				'produto_id'           => (int) $linha->produto_id,
				'produto_nome'         => $linha->produto_nome,
				'estabelecimento_id'   => (int) $linha->estabelecimento_id,
				'estabelecimento_nome' => $linha->estabelecimento_nome,
				'data_entrada'         => $linha->data_entrada,
				'data_atualizacao'     => $linha->data_atualizacao,
				'dias_no_local'        => $dias,
				'alerta'               => $dias >= $alerta,
				'origem'               => $linha->origem,
				'nota_fiscal'          => $linha->nota_fiscal,
				'status'               => $linha->status,
			);
		}

		return rest_ensure_response( array( 'itens' => $saida ) );
	}
}
