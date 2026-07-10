<?php
/**
 * Normaliza dados do SAP Business One (via n8n) para o formato do plugin.
 *
 * Tabelas usadas no fluxo:
 * - OCRD  Clientes (estabelecimento / ponto de venda)
 * - OITM  Produtos
 * - OITB  Grupo de itens (categoria)
 * - OINV  Notas fiscais
 * - ORDR  Pedidos
 * - RDR1  Itens do pedido
 * - OCPR  Contatos
 * - OSLP  Vendedores
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
	 * Converte um item (já juntado no n8n) para o formato interno.
	 *
	 * Aceita tanto campos amigáveis quanto nomes SAP (CardCode, ItemCode…).
	 *
	 * @param array $raw Payload bruto do n8n.
	 * @return array
	 */
	public static function normalizar_item( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		// Se já veio no formato do plugin, só completa o que faltar.
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

		$obs_partes = array();
		$vendedor   = self::primeiro_valor( $raw, array( 'SlpName', 'slpName', 'vendedor' ) );
		$contato    = self::primeiro_valor( $raw, array( 'Name', 'CntctPrsn', 'contato' ) );
		if ( $vendedor ) {
			$obs_partes[] = 'Vendedor: ' . $vendedor;
		}
		if ( $contato ) {
			$obs_partes[] = 'Contato: ' . $contato;
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
	 * Completa payload já no formato do plugin com aliases SAP.
	 *
	 * @param array $raw Payload.
	 * @return array
	 */
	private static function completar_formato_plugin( $raw ) {
		$produto = isset( $raw['produto'] ) && is_array( $raw['produto'] ) ? $raw['produto'] : array();
		$local   = isset( $raw['estabelecimento'] ) && is_array( $raw['estabelecimento'] ) ? $raw['estabelecimento'] : array();

		// Aliases comuns dentro de produto / estabelecimento.
		if ( empty( $produto['sku'] ) && ! empty( $produto['ItemCode'] ) ) {
			$produto['sku'] = $produto['ItemCode'];
		}
		if ( empty( $produto['nome'] ) && ! empty( $produto['ItemName'] ) ) {
			$produto['nome'] = $produto['ItemName'];
		}
		if ( empty( $produto['categoria'] ) && ! empty( $produto['ItmsGrpNam'] ) ) {
			$produto['categoria'] = $produto['ItmsGrpNam'];
		}

		if ( empty( $local['codigo_sap'] ) && ! empty( $local['CardCode'] ) ) {
			$local['codigo_sap'] = $local['CardCode'];
		}
		if ( empty( $local['nome'] ) && ! empty( $local['CardName'] ) ) {
			$local['nome'] = $local['CardName'];
		}
		if ( empty( $local['cidade'] ) && ! empty( $local['City'] ) ) {
			$local['cidade'] = $local['City'];
		}
		if ( empty( $local['endereco'] ) && ! empty( $local['Address'] ) ) {
			$local['endereco'] = $local['Address'];
		}
		if ( empty( $local['cep'] ) && ! empty( $local['ZipCode'] ) ) {
			$local['cep'] = $local['ZipCode'];
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
	 * Extrai produto (OITM + OITB).
	 *
	 * @param array $raw Dados.
	 * @return array
	 */
	private static function extrair_produto( $raw ) {
		$sku  = self::primeiro_valor( $raw, array( 'sku', 'ItemCode', 'itemCode', 'U_ItemCode' ) );
		$nome = self::primeiro_valor( $raw, array( 'nome', 'ItemName', 'itemName', 'Dscription' ) );
		$cat  = self::primeiro_valor( $raw, array( 'categoria', 'ItmsGrpNam', 'itmsGrpNam', 'ItmsGrpCod' ) );

		return array(
			'sku'       => $sku ? (string) $sku : '',
			'nome'      => $nome ? (string) $nome : '',
			'categoria' => $cat ? (string) $cat : '',
			'marca'     => self::primeiro_valor( $raw, array( 'marca', 'FirmName', 'U_Marca' ) ) ?: '',
		);
	}

	/**
	 * Extrai estabelecimento (OCRD).
	 *
	 * @param array $raw Dados.
	 * @return array
	 */
	private static function extrair_estabelecimento( $raw ) {
		$codigo = self::primeiro_valor( $raw, array( 'codigo_sap', 'CardCode', 'cardCode' ) );
		$nome   = self::primeiro_valor( $raw, array( 'nome_local', 'CardName', 'cardName', 'CardFName' ) );
		$cidade = self::primeiro_valor( $raw, array( 'cidade', 'City', 'city', 'County' ) );
		$end    = self::primeiro_valor( $raw, array( 'endereco', 'Address', 'address', 'MailAddres', 'Street' ) );
		$cep    = self::primeiro_valor( $raw, array( 'cep', 'ZipCode', 'zipCode' ) );
		$uf     = self::primeiro_valor( $raw, array( 'uf', 'State', 'state', 'State1' ) );

		// Tipo: se vier GroupName / U_Tipo, mapeia; senão mercado.
		$tipo_raw = strtolower( (string) self::primeiro_valor( $raw, array( 'tipo', 'GroupName', 'U_Tipo' ) ) );
		$tipo     = 'mercado';
		if ( false !== strpos( $tipo_raw, 'super' ) ) {
			$tipo = 'supermercado';
		} elseif ( false !== strpos( $tipo_raw, 'atac' ) ) {
			$tipo = 'atacado';
		} elseif ( false !== strpos( $tipo_raw, 'emp' ) ) {
			$tipo = 'emporio';
		}

		return array(
			'codigo_sap' => $codigo ? (string) $codigo : '',
			'nome'       => $nome ? (string) $nome : '',
			'tipo'       => $tipo,
			'endereco'   => $end ? (string) $end : '',
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
		$ts = strtotime( (string) $data );
		if ( ! $ts ) {
			return '';
		}
		return gmdate( 'Y-m-d H:i:s', $ts );
	}
}
