<?php
/**
 * View: configurações (linguagem simples).
 *
 * @package ValleBrancoOndeEncontrar
 * @var array  $settings
 * @var string $api_key
 * @var string $endpoint
 * @var string $lote
 * @var string $webhook
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$n8n_fluxo   = 'https://n8n.v4companyamaral.com/workflow/lBNujNGwhttefPIl?projectId=ZqW5ySVXaI1Z9iy2';
$n8n_webhook = ! empty( $settings['n8n_webhook_url'] )
	? $settings['n8n_webhook_url']
	: 'https://n8n.v4companyamaral.com/webhook-test/8f02e2f2-0a49-4daf-9dfd-b8f55e7788ff';
?>
<div class="wrap vb-oe-admin">
	<h1>Configurações</h1>
	<p class="vb-oe-lead">Aqui você ajusta o mapa e, se precisar, a ligação com a automação. Na dúvida, comece só pela seção “Mapa”.</p>

	<?php if ( isset( $_GET['atualizado'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p>Pronto! Configurações salvas.</p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['chave'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p>Nova senha de acesso gerada. Atualize na automação se ela já estava usando a antiga.</p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'vb_oe_salvar_config' ); ?>
		<input type="hidden" name="action" value="vb_oe_salvar_config">

		<div class="vb-oe-card-ajuda">
			<h2>Mapa (o que o visitante vê)</h2>
			<p>Define o ponto inicial do mapa quando a página abre. Você pode deixar os valores padrão.</p>

			<table class="form-table" role="presentation">
				<tr>
					<th><label for="dias_alerta">Avisar no relatório após quantos dias?</label></th>
					<td>
						<input type="number" name="dias_alerta" id="dias_alerta" value="<?php echo esc_attr( (string) ( $settings['dias_alerta'] ?? 90 ) ); ?>" min="1" class="small-text">
						<p class="description">Ex.: 90 = se um produto está há mais de 90 dias na loja sem atualização, ele aparece destacado no relatório para você conferir se ainda existe lá.</p>
					</td>
				</tr>
				<tr>
					<th><label for="mapa_lat">Centro do mapa — latitude</label></th>
					<td>
						<input type="text" name="mapa_lat" id="mapa_lat" value="<?php echo esc_attr( (string) ( $settings['mapa_lat'] ?? -23.0 ) ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th><label for="mapa_lng">Centro do mapa — longitude</label></th>
					<td>
						<input type="text" name="mapa_lng" id="mapa_lng" value="<?php echo esc_attr( (string) ( $settings['mapa_lng'] ?? -49.5 ) ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th><label for="mapa_zoom">Zoom inicial</label></th>
					<td>
						<input type="number" name="mapa_zoom" id="mapa_zoom" value="<?php echo esc_attr( (string) ( $settings['mapa_zoom'] ?? 7 ) ); ?>" min="1" max="18" class="small-text">
						<p class="description">Número menor = mapa mais “aberto”. Sugestão: 7.</p>
					</td>
				</tr>
			</table>

			<?php submit_button( 'Salvar configurações do mapa' ); ?>
		</div>
	</form>

	<details class="vb-oe-card-ajuda vb-oe-avancado" open>
		<summary><strong>Automação — como os dados chegam no mapa</strong></summary>
		<div class="vb-oe-avancado-corpo">
			<ol class="vb-oe-passos">
				<li><strong>Alguém chama o webhook do n8n</strong> (ou o n8n roda no horário marcado) → ele busca os dados no sistema da empresa.</li>
				<li><strong>No final do fluxo n8n</strong>, um passo “HTTP Request” envia produto + loja para o WordPress.</li>
				<li><strong>O WordPress recebe</strong> e atualiza o mapa / relatório.</li>
			</ol>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'vb_oe_salvar_config' ); ?>
				<input type="hidden" name="action" value="vb_oe_salvar_config">

				<table class="form-table" role="presentation">
					<tr>
						<th><label for="n8n_webhook_url">Webhook do n8n (entrada)</label></th>
						<td>
							<input type="url" name="n8n_webhook_url" id="n8n_webhook_url" value="<?php echo esc_attr( $n8n_webhook ); ?>" class="large-text">
							<p class="description">
								É o endereço <em>do n8n</em> — quem dispara a automação. Não é o endereço do WordPress.<br>
								Atenção: URLs com <code>webhook-test</code> só funcionam enquanto o fluxo está em teste no editor.
								Em produção use a URL com <code>/webhook/</code> (sem “test”).
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Salvar URL do n8n', 'secondary' ); ?>
			</form>

			<p>
				<a class="button" href="<?php echo esc_url( $n8n_fluxo ); ?>" target="_blank" rel="noopener noreferrer">Abrir fluxo no n8n</a>
				<a class="button" href="<?php echo esc_url( $n8n_webhook ); ?>" target="_blank" rel="noopener noreferrer">Abrir webhook n8n</a>
			</p>

			<h3>Para onde o n8n deve enviar (WordPress)</h3>
			<p>No n8n, depois de ler o SAP, adicione um nó <strong>HTTP Request</strong>:</p>
			<ul>
				<li>Método: <strong>POST</strong></li>
				<li>URL: um dos endereços abaixo</li>
				<li>Header: <code>X-VB-API-Key</code> = a senha desta tela</li>
			</ul>

			<table class="form-table" role="presentation">
				<tr>
					<th>Webhook do site (recomendado)</th>
					<td><code class="vb-oe-quebra"><?php echo esc_html( $webhook ); ?></code></td>
				</tr>
				<tr>
					<th>Mesmo destino (nome antigo)</th>
					<td><code class="vb-oe-quebra"><?php echo esc_html( $endpoint ); ?></code></td>
				</tr>
				<tr>
					<th>Vários itens de uma vez</th>
					<td><code class="vb-oe-quebra"><?php echo esc_html( $lote ); ?></code></td>
				</tr>
				<tr>
					<th>Senha de acesso</th>
					<td>
						<code class="vb-oe-chave"><?php echo esc_html( $api_key ); ?></code>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:8px">
							<?php wp_nonce_field( 'vb_oe_regenerar_chave' ); ?>
							<input type="hidden" name="action" value="vb_oe_regenerar_chave">
							<button type="submit" class="button" onclick="return confirm('Gerar nova senha? A antiga deixa de funcionar na automação.');">Gerar nova senha</button>
						</form>
					</td>
				</tr>
			</table>
		</div>
	</details>

	<div class="vb-oe-card-ajuda">
		<h2>Montar a página</h2>
		<p>Veja o passo a passo em <a href="<?php echo esc_url( admin_url( 'admin.php?page=vb-oe-como-usar' ) ); ?>">Como usar</a>.</p>
	</div>
</div>
