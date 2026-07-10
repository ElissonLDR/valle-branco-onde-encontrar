<?php
/**
 * Normaliza dados do SAP / n8n (Power BI) para o formato do plugin.
 *
 * Formato real do webhook:
 * [{ results: [{ tables: [{ rows: [ { "OINV - Notas[DocNum]": ..., ... } ] }] }] }]
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_SAP
 */
class VB_OE_SAP {

	/**
	 * Converte um item (linha do n8n) para o formato interno.
	 *
	 * @param array $raw Payload bruto.
	 * @return array
	 */
	public static function normalizar_item( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		// Achata chaves tipo "OCRD - Clientes[City]" → também disponíveis como City, etc.
		$raw = self::achatar_chaves( $raw );

		if ( isset( $raw['produto'] ) || isset( $raw['estabelecimento'] ) ) {
			return self::completar_formato_plugin( $raw );
		}

		$produto = self::extrair_produto( $raw );
		$local   = self::extrair_estabelecimento( $raw );

		$nota = self::primeiro_valor(
			$raw,
			array( 'nota_fiscal', 'DocNum', 'docNum', 'DocEntry', 'docEntry', 'NumAtCard' )
		);

		$data = self::primeiro_valor(
			$raw,
			array( 'data_entrada', 'DocDate', 'docDate', 'TaxDate', 'CreateDate' )
		);

		$qtd   = self::primeiro_valor( $raw, array( 'Qtd Vendida', 'Quantity', 'qtd' ) );
		$valor = self::primeiro_valor( $raw, array( 'Valor Total', 'LineTotal', 'valor' ) );

		$obs_partes = array();
		if ( null !== $qtd && '' !== $qtd ) {
			$obs_partes[] = 'Qtd: ' . $qtd;
		}
		if ( null !== $valor && '' !== $valor ) {
			$obs_partes[] = 'Valor: ' . $valor;
		}
		if ( ! empty( $raw['observacao'] ) ) {
			$obs_partes[] = $raw['observacao'];
		}

		return array(
			'nota_fiscal'     => $nota ? (string) $nota : '',
			'data_entrada'    => self::formatar_data( $data ),
			'observacao'      => implode( ' | ', $obs_partes ),
			'produto'         => $produto,
			'estabelecimento' => $local,
			'origem_sap'      => true,
		);
	}

	/**
	 * Copia valores de chaves "Tabela[Campo]" para o nome curto do campo.
	 *
	 * @param array $raw Dados.
	 * @return array
	 */
	public static function achatar_chaves( $raw ) {
		foreach ( $raw as $chave => $valor ) {
			if ( ! is_string( $chave ) ) {
				continue;
			}
			// "OINV - Notas[DocNum]" ou "[Qtd Vendida]"
			if ( preg_match( '/\[([^\]]+)\]\s*$/', $chave, $m ) ) {
				$curto = $m[1];
				if ( ! isset( $raw[ $curto ] ) || '' === $raw[ $curto ] || null === $raw[ $curto ] ) {
					$raw[ $curto ] = $valor;
				}
			}
		}
		return $raw;
	}

	/**
	 * Completa payload já no formato do plugin.
	 *
	 * @param array $raw Payload.
	 * @return array
	 */
	private static function completar_formato_plugin( $raw ) {
		$produto = isset( $raw['produto'] ) && is_array( $raw['produto'] ) ? $raw['produto'] : array();
		$local   = isset( $raw['estabelecimento'] ) && is_array( $raw['estabelecimento'] ) ? $raw['estabelecimento'] : array();

		if ( empty( $produto['sku'] ) && ! empty( $produto['ItemCode'] ) ) {
			$produto['sku'] = $produto['ItemCode'];
		}
		if ( empty( $produto['nome'] ) && ! empty( $produto['ItemName'] ) ) {
			$produto['nome'] = $produto['ItemName'];
		}
		if ( empty( $local['codigo_sap'] ) && ! empty( $local['CardCode'] ) ) {
			$local['codigo_sap'] = $local['CardCode'];
		}
		if ( empty( $local['nome'] ) && ! empty( $local['CardName'] ) ) {
			$local['nome'] = $local['CardName'];
		}

		$raw['produto']         = $produto;
		$raw['estabelecimento'] = $local;

		if ( empty( $raw['nota_fiscal'] ) ) {
			$raw['nota_fiscal'] = self::primeiro_valor( $raw, array( 'DocNum', 'docNum', 'DocEntry' ) );
		}
		if ( ! empty( $raw['data_entrada'] ) ) {
			$raw['data_entrada'] = self::formatar_data( $raw['data_entrada'] );
		}

		return $raw;
	}

	/**
	 * Extrai produto (OITM).
	 *
	 * @param array $raw Dados.
	 * @return array
	 */
	private static function extrair_produto( $raw ) {
		$sku  = self::primeiro_valor( $raw, array( 'sku', 'ItemCode', 'itemCode', 'U_ItemCode' ) );
		$nome = self::primeiro_valor( $raw, array( 'nome', 'ItemName', 'itemName', 'Dscription' ) );
		$cat  = self::primeiro_valor( $raw, array( 'categoria', 'ItmsGrpNam', 'itmsGrpNam', 'ItmsGrpCod' ) );

		$nome = $nome ? trim( (string) $nome ) : '';
		if ( ! $sku && $nome ) {
			$sku = 'SKU-' . substr( md5( mb_strtoupper( $nome ) ), 0, 12 );
		}

		$marca = '';
		$upper = mb_strtoupper( $nome );
		if ( false !== strpos( $upper, 'VALLE BRANCO' ) ) {
			$marca = 'Valle Branco';
		} elseif ( false !== strpos( $upper, 'CASTELAO' ) || false !== strpos( $upper, 'CASTELÃO' ) ) {
			$marca = 'Castelão';
		} elseif ( false !== strpos( $upper, 'AENE' ) ) {
			$marca = 'Aene';
		} elseif ( false !== strpos( $upper, 'VITA' ) ) {
			$marca = 'Vita';
		}

		return array(
			'sku'       => $sku ? (string) $sku : '',
			'nome'      => $nome,
			'categoria' => $cat ? (string) $cat : '',
			'marca'     => $marca,
		);
	}

	/**
	 * Extrai estabelecimento (OCRD).
	 * O webhook atual não manda CardName — montamos pelo endereço + cidade.
	 *
	 * @param array $raw Dados.
	 * @return array
	 */
	private static function extrair_estabelecimento( $raw ) {
		$codigo = self::primeiro_valor( $raw, array( 'codigo_sap', 'CardCode', 'cardCode' ) );
		$nome   = self::primeiro_valor( $raw, array( 'nome_local', 'CardName', 'cardName', 'CardFName' ) );
		$cidade = self::primeiro_valor( $raw, array( 'cidade', 'City', 'city', 'County' ) );
		$rua    = self::primeiro_valor( $raw, array( 'Address', 'address', 'MailAddres', 'Street', 'endereco' ) );
		$numero = self::primeiro_valor( $raw, array( 'StreetNo', 'streetNo', 'BuildingFloorRoom' ) );
		$cep    = self::primeiro_valor( $raw, array( 'cep', 'ZipCode', 'zipCode' ) );
		$uf     = self::primeiro_valor( $raw, array( 'uf', 'State1', 'State', 'state' ) );

		$endereco = trim( (string) $rua );
		if ( $numero ) {
			$endereco = trim( $endereco . ', ' . $numero );
		}

		if ( ! $nome ) {
			$nome = $endereco ? $endereco : (string) $cidade;
			if ( $cidade && $endereco ) {
				$nome = $endereco . ' — ' . $cidade;
			}
		}

		if ( ! $codigo ) {
			$base   = mb_strtoupper( trim( (string) $cidade . '|' . (string) $rua . '|' . (string) $numero ) );
			$codigo = $base ? 'LOC-' . substr( md5( $base ), 0, 12 ) : '';
		}

		return array(
			'codigo_sap' => $codigo ? (string) $codigo : '',
			'nome'       => $nome ? (string) $nome : '',
			'tipo'       => 'mercado',
			'endereco'   => $endereco,
			'cidade'     => $cidade ? (string) $cidade : '',
			'uf'         => $uf ? strtoupper( substr( (string) $uf, 0, 2 ) ) : '',
			'cep'        => $cep ? (string) $cep : '',
			'lat'        => self::primeiro_valor( $raw, array( 'lat', 'U_Latitude', 'Latitude' ) ),
			'lng'        => self::primeiro_valor( $raw, array( 'lng', 'U_Longitude', 'Longitude' ) ),
		);
	}

	/**
	 * Primeiro valor não vazio entre chaves.
	 *
	 * @param array $arr    Array.
	 * @param array $chaves Chaves.
	 * @return mixed|null
	 */
	private static function primeiro_valor( $arr, $chaves ) {
		foreach ( $chaves as $chave ) {
			if ( isset( $arr[ $chave ] ) && '' !== $arr[ $chave ] && null !== $arr[ $chave ] ) {
				return $arr[ $chave ];
			}
		}
		return null;
	}

	/**
	 * Converte data SAP / ISO para MySQL.
	 *
	 * @param mixed $data Data.
	 * @return string
	 */
	private static function formatar_data( $data ) {
		if ( empty( $data ) ) {
			return '';
		}
		// Mantém o dia da nota (evita mudar por fuso com gmdate).
		if ( preg_match( '/^(\d{4}-\d{2}-\d{2})/', (string) $data, $m ) ) {
			return $m[1] . ' 00:00:00';
		}
		$ts = strtotime( (string) $data );
		if ( ! $ts ) {
			return '';
		}
		return gmdate( 'Y-m-d H:i:s', $ts );
	}
}
