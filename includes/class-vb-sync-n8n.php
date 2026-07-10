<?php
/**
 * Busca dados no webhook do n8n e atualiza o mapa (diário).
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_Sync_N8N
 */
class VB_OE_Sync_N8N {

	const CRON_HOOK = 'vb_oe_sync_diario';
	const DEFAULT_WEBHOOK = 'https://webhook-n8n.v4companyamaral.com/webhook/8f02e2f2-0a49-4daf-9dfd-b8f55e7788ff';

	/**
	 * Liga hooks.
	 */
	public function hooks() {
		add_action( self::CRON_HOOK, array( $this, 'rodar_sync' ) );
		add_action( 'admin_post_vb_oe_sync_agora', array( $this, 'sync_manual' ) );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * URL do webhook n8n.
	 *
	 * @return string
	 */
	public static function webhook_url() {
		$settings = get_option( 'vb_oe_settings', array() );
		$url      = ! empty( $settings['n8n_webhook_url'] ) ? $settings['n8n_webhook_url'] : self::DEFAULT_WEBHOOK;
		return esc_url_raw( $url );
	}

	/**
	 * Clique em “Atualizar agora” no painel.
	 */
	public function sync_manual() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sem permissão.' );
		}
		check_admin_referer( 'vb_oe_sync_agora' );

		$resultado = self::buscar_e_importar();
		set_transient( 'vb_oe_ultimo_sync', $resultado, DAY_IN_SECONDS );

		$ok = empty( $resultado['erro'] ) ? '1' : '0';
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => 'vb-oe-config',
					'sync'      => $ok,
					'sucesso'   => isset( $resultado['sucesso'] ) ? (int) $resultado['sucesso'] : 0,
					'erros'     => isset( $resultado['erros'] ) ? (int) $resultado['erros'] : 0,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Cron diário.
	 */
	public function rodar_sync() {
		$resultado = self::buscar_e_importar();
		set_transient( 'vb_oe_ultimo_sync', $resultado, WEEK_IN_SECONDS );
	}

	/**
	 * Chama o n8n e importa o JSON retornado.
	 *
	 * @return array
	 */
	public static function buscar_e_importar() {
		$url = self::webhook_url();

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 90,
				'headers' => array(
					'Accept'       => 'application/json',
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'origem'  => 'wordpress-valle-branco',
						'acao'    => 'atualizar_onde_encontrar',
						'data'    => current_time( 'mysql' ),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'erro'    => $response->get_error_message(),
				'sucesso' => 0,
				'erros'   => 0,
				'quando'  => current_time( 'mysql' ),
				'url'     => $url,
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( $code < 200 || $code >= 300 ) {
			return array(
				'erro'    => 'O n8n respondeu com código ' . $code . '. Ative o fluxo no n8n e tente de novo. Resposta: ' . wp_strip_all_tags( substr( $body, 0, 300 ) ),
				'sucesso' => 0,
				'erros'   => 0,
				'quando'  => current_time( 'mysql' ),
				'url'     => $url,
				'http'    => $code,
				'amostra' => substr( $body, 0, 2000 ),
			);
		}

		$dados = json_decode( $body, true );
		if ( null === $dados && '' !== trim( $body ) ) {
			return array(
				'erro'    => 'A resposta do n8n não é JSON válido.',
				'sucesso' => 0,
				'erros'   => 0,
				'quando'  => current_time( 'mysql' ),
				'amostra' => substr( $body, 0, 2000 ),
			);
		}

		$itens = self::extrair_itens( $dados );
		update_option( 'vb_oe_ultimo_payload_amostra', self::amostra_segura( $dados ), false );

		$ok    = 0;
		$falhas = 0;
		$msgs  = array();

		foreach ( $itens as $item ) {
			$resultado = VB_OE_Relacao::sincronizar_da_nota( $item );
			if ( is_wp_error( $resultado ) ) {
				$falhas++;
				$msgs[] = $resultado->get_error_message();
			} else {
				$ok++;
			}
		}

		// Depois do sync, localiza no mapa os endereços sem lat/lng.
		VB_OE_Geocoder::agendar_agora();

		return array(
			'sucesso'       => $ok,
			'erros'         => $falhas,
			'total_recebido'=> count( $itens ),
			'mensagens'     => array_slice( $msgs, 0, 5 ),
			'quando'        => current_time( 'mysql' ),
			'url'           => $url,
			'http'          => $code,
			'chaves'        => self::listar_chaves( $dados ),
			'geo_pendentes' => VB_OE_Geocoder::contar_pendentes(),
		);
	}

	/**
	 * Descobre a lista de itens no JSON (vários formatos).
	 *
	 * @param mixed $dados JSON decodificado.
	 * @return array
	 */
	public static function extrair_itens( $dados ) {
		if ( ! is_array( $dados ) ) {
			return array();
		}

		// Formato Power BI / n8n: [ { results: [ { tables: [ { rows: [...] } ] } ] } ]
		$rows = self::extrair_rows_powerbi( $dados );
		if ( ! empty( $rows ) ) {
			return $rows;
		}

		// Lista pura de itens já “flat”.
		if ( self::eh_lista( $dados ) && self::parece_item( $dados[0] ?? null ) ) {
			return $dados;
		}

		foreach ( array( 'itens', 'items', 'data', 'results', 'rows', 'DocumentLines', 'value' ) as $chave ) {
			if ( isset( $dados[ $chave ] ) && is_array( $dados[ $chave ] ) && self::eh_lista( $dados[ $chave ] ) ) {
				if ( self::parece_item( $dados[ $chave ][0] ?? null ) ) {
					return $dados[ $chave ];
				}
				$aninhado = self::extrair_rows_powerbi( $dados[ $chave ] );
				if ( ! empty( $aninhado ) ) {
					return $aninhado;
				}
			}
		}

		if ( self::parece_item( $dados ) ) {
			return array( $dados );
		}

		return array();
	}

	/**
	 * Percorre estrutura results → tables → rows.
	 *
	 * @param mixed $dados Dados.
	 * @return array
	 */
	private static function extrair_rows_powerbi( $dados ) {
		if ( ! is_array( $dados ) ) {
			return array();
		}

		// Se for lista, tenta em cada elemento.
		if ( self::eh_lista( $dados ) ) {
			$todos = array();
			foreach ( $dados as $bloco ) {
				$parte = self::extrair_rows_powerbi( $bloco );
				if ( $parte ) {
					$todos = array_merge( $todos, $parte );
				}
			}
			return $todos;
		}

		if ( isset( $dados['results'] ) && is_array( $dados['results'] ) ) {
			return self::extrair_rows_powerbi( $dados['results'] );
		}

		if ( isset( $dados['tables'] ) && is_array( $dados['tables'] ) ) {
			return self::extrair_rows_powerbi( $dados['tables'] );
		}

		if ( isset( $dados['rows'] ) && is_array( $dados['rows'] ) && self::eh_lista( $dados['rows'] ) ) {
			return $dados['rows'];
		}

		return array();
	}

	/**
	 * Parece uma linha de nota/produto?
	 *
	 * @param mixed $item Item.
	 * @return bool
	 */
	private static function parece_item( $item ) {
		if ( ! is_array( $item ) ) {
			return false;
		}
		$flat = VB_OE_SAP::achatar_chaves( $item );
		return isset( $flat['ItemName'] )
			|| isset( $flat['ItemCode'] )
			|| isset( $flat['CardCode'] )
			|| isset( $flat['DocNum'] )
			|| isset( $flat['produto'] )
			|| isset( $item['OITM - Produtos[ItemName]'] )
			|| isset( $item['OINV - Notas[DocNum]'] );
	}

	/**
	 * Array sequencial?
	 *
	 * @param array $arr Array.
	 * @return bool
	 */
	private static function eh_lista( $arr ) {
		if ( ! is_array( $arr ) || empty( $arr ) ) {
			return false;
		}
		return array_keys( $arr ) === range( 0, count( $arr ) - 1 );
	}

	/**
	 * Lista chaves do primeiro nível (para diagnóstico).
	 *
	 * @param mixed $dados Dados.
	 * @return array
	 */
	private static function listar_chaves( $dados ) {
		$itens = self::extrair_itens( $dados );
		if ( ! empty( $itens[0] ) && is_array( $itens[0] ) ) {
			return array_keys( $itens[0] );
		}
		if ( is_array( $dados ) && ! self::eh_lista( $dados ) ) {
			return array_keys( $dados );
		}
		return array();
	}

	/**
	 * Guarda amostra pequena do payload.
	 *
	 * @param mixed $dados Dados.
	 * @return mixed
	 */
	private static function amostra_segura( $dados ) {
		$itens = self::extrair_itens( $dados );
		if ( ! empty( $itens ) ) {
			return array_slice( $itens, 0, 3 );
		}
		return $dados;
	}
}
