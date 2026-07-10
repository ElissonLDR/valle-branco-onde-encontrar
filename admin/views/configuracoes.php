<?php
/**
 * View: configurações e integração n8n + SAP B1.
 *
 * @package ValleBrancoOndeEncontrar
 * @var array  $settings
 * @var string $api_key
 * @var string $endpoint
 * @var string $lote
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$n8n_url = 'https://n8n.v4companyamaral.com/workflow/lBNujNGwhttefPIl?projectId=ZqW5ySVXaI1Z9iy2';
?>
<div class="wrap vb-oe-admin">
	<h1>Configurações — Onde Encontrar</h1>

	<?php if ( isset( $_GET['atualizado'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p>Configurações salvas.</p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['chave'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p>Nova chave de API gerada.</p></div>
	<?php endif; ?>

	<h2>Integração n8n + SAP B1</h2>
	<p>
		Workflow:
		<a href="<?php echo esc_url( $n8n_url ); ?>" target="_blank" rel="noopener noreferrer">abrir no n8n</a>.
		O fluxo lê o SAP e envia produto + local para estes endpoints (atualização diária).
	</p>

	<table class="widefat striped" style="max-width:720px;margin-bottom:16px">
		<thead>
			<tr><th>Tabela SAP</th><th>Uso no Onde Encontrar</th></tr>
		</thead>
		<tbody>
			<tr><td><code>OINV</code></td><td>Notas fiscais (DocNum, DocDate, CardCode)</td></tr>
			<tr><td><code>ORDR</code> + <code>RDR1</code></td><td>Pedidos e itens (alternativa / complemento às notas)</td></tr>
			<tr><td><code>OITM</code></td><td>Produtos (ItemCode → SKU, ItemName)</td></tr>
			<tr><td><code>OITB</code></td><td>Grupo de itens → categoria</td></tr>
			<tr><td><code>OCRD</code></td><td>Clientes → estabelecimento no mapa (CardCode, CardName, City…)</td></tr>
			<tr><td><code>OCPR</code></td><td>Contatos (observação no relatório)</td></tr>
			<tr><td><code>OSLP</code></td><td>Vendedores (observação no relatório)</td></tr>
		</tbody>
	</table>

	<table class="form-table">
		<tr>
			<th>Endpoint (1 item)</th>
			<td><code><?php echo esc_html( $endpoint ); ?></code></td>
		</tr>
		<tr>
			<th>Endpoint (lote)</th>
			<td><code><?php echo esc_html( $lote ); ?></code></td>
		</tr>
		<tr>
			<th>Chave de API</th>
			<td>
				<code class="vb-oe-chave"><?php echo esc_html( $api_key ); ?></code>
				<p class="description">Envie no header <code>X-VB-API-Key</code>.</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:8px">
					<?php wp_nonce_field( 'vb_oe_regenerar_chave' ); ?>
					<input type="hidden" name="action" value="vb_oe_regenerar_chave">
					<button type="submit" class="button" onclick="return confirm('Gerar nova chave? A antiga deixa de funcionar.');">Regenerar chave</button>
				</form>
			</td>
		</tr>
	</table>

	<h3>Exemplo JSON (campos SAP — o n8n pode enviar assim, flat)</h3>
	<pre class="vb-oe-json">{
  "DocNum": "123456",
  "DocDate": "2026-07-10",
  "ItemCode": "VB-ARROZ-EXTRA",
  "ItemName": "Arroz Valle Branco Extra 5kg",
  "ItmsGrpNam": "Arroz",
  "CardCode": "C00045",
  "CardName": "Mercado Boa Vista",
  "Address": "Rua Piauí, 320",
  "City": "Londrina",
  "State": "PR",
  "ZipCode": "86010-000",
  "SlpName": "João Silva"
}</pre>

	<h3>Ou no formato organizado</h3>
	<pre class="vb-oe-json">{
  "nota_fiscal": "123456",
  "data_entrada": "2026-07-10",
  "produto": {
    "sku": "VB-ARROZ-EXTRA",
    "nome": "Arroz Valle Branco Extra 5kg",
    "categoria": "Arroz"
  },
  "estabelecimento": {
    "codigo_sap": "C00045",
    "nome": "Mercado Boa Vista",
    "tipo": "mercado",
    "endereco": "Rua Piauí, 320",
    "cidade": "Londrina",
    "uf": "PR",
    "lat": -23.3045,
    "lng": -51.1696
  }
}</pre>
	<p class="description">Lat/lng: se o SAP não tiver, o n8n pode geocodificar o endereço antes de enviar (senão o local fica cadastrado, mas sem pin no mapa).</p>

	<hr>

	<h2>Mapa e alertas</h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'vb_oe_salvar_config' ); ?>
		<input type="hidden" name="action" value="vb_oe_salvar_config">

		<table class="form-table">
			<tr>
				<th><label for="dias_alerta">Dias para alerta</label></th>
				<td>
					<input type="number" name="dias_alerta" id="dias_alerta" value="<?php echo esc_attr( (string) ( $settings['dias_alerta'] ?? 90 ) ); ?>" min="1">
					<p class="description">No relatório, destaca produtos há mais tempo que isso no local.</p>
				</td>
			</tr>
			<tr>
				<th><label for="mapa_lat">Centro do mapa (lat)</label></th>
				<td><input type="text" name="mapa_lat" id="mapa_lat" value="<?php echo esc_attr( (string) ( $settings['mapa_lat'] ?? -23.0 ) ); ?>"></td>
			</tr>
			<tr>
				<th><label for="mapa_lng">Centro do mapa (lng)</label></th>
				<td><input type="text" name="mapa_lng" id="mapa_lng" value="<?php echo esc_attr( (string) ( $settings['mapa_lng'] ?? -49.5 ) ); ?>"></td>
			</tr>
			<tr>
				<th><label for="mapa_zoom">Zoom inicial</label></th>
				<td><input type="number" name="mapa_zoom" id="mapa_zoom" value="<?php echo esc_attr( (string) ( $settings['mapa_zoom'] ?? 7 ) ); ?>" min="1" max="18"></td>
			</tr>
		</table>

		<?php submit_button( 'Salvar configurações' ); ?>
	</form>

	<hr>
	<h2>Shortcode</h2>
	<p>Use na página do site: <code>[vb_onde_encontrar]</code> ou <code>[vb_onde_encontrar altura="520"]</code></p>
</div>
