<?php
/**
 * View: configurações (linguagem simples).
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
						<p class="description">Número da posição norte/sul. Padrão cobre SP e norte do Paraná.</p>
					</td>
				</tr>
				<tr>
					<th><label for="mapa_lng">Centro do mapa — longitude</label></th>
					<td>
						<input type="text" name="mapa_lng" id="mapa_lng" value="<?php echo esc_attr( (string) ( $settings['mapa_lng'] ?? -49.5 ) ); ?>" class="regular-text">
						<p class="description">Número da posição leste/oeste.</p>
					</td>
				</tr>
				<tr>
					<th><label for="mapa_zoom">Zoom inicial</label></th>
					<td>
						<input type="number" name="mapa_zoom" id="mapa_zoom" value="<?php echo esc_attr( (string) ( $settings['mapa_zoom'] ?? 7 ) ); ?>" min="1" max="18" class="small-text">
						<p class="description">Número menor = mapa mais “aberto”. Número maior = mais perto. Sugestão: 7.</p>
					</td>
				</tr>
			</table>

			<?php submit_button( 'Salvar configurações do mapa' ); ?>
		</div>
	</form>

	<details class="vb-oe-card-ajuda vb-oe-avancado">
		<summary><strong>Automação (só se for conectar o sistema da empresa)</strong></summary>
		<div class="vb-oe-avancado-corpo">
			<p>Isso é para a equipe técnica / n8n. Se você só vai montar a página e cadastrar lojas na mão, <strong>pode ignorar esta parte</strong>.</p>
			<p>A automação lê as notas/pedidos no sistema da empresa e atualiza sozinha o que aparece no mapa.</p>
			<p>
				<a class="button" href="<?php echo esc_url( $n8n_url ); ?>" target="_blank" rel="noopener noreferrer">Abrir fluxo da automação</a>
			</p>

			<h3>O que a automação precisa enviar</h3>
			<p>Ela manda para o site: <strong>qual produto</strong> e <strong>qual loja</strong> (e o número da nota, se tiver).</p>

			<table class="form-table" role="presentation">
				<tr>
					<th>Endereço para 1 item</th>
					<td><code class="vb-oe-quebra"><?php echo esc_html( $endpoint ); ?></code></td>
				</tr>
				<tr>
					<th>Endereço para vários itens</th>
					<td><code class="vb-oe-quebra"><?php echo esc_html( $lote ); ?></code></td>
				</tr>
				<tr>
					<th>Senha de acesso</th>
					<td>
						<code class="vb-oe-chave"><?php echo esc_html( $api_key ); ?></code>
						<p class="description">A automação usa essa senha no cabeçalho chamado <code>X-VB-API-Key</code>. Não compartilhe em público.</p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:8px">
							<?php wp_nonce_field( 'vb_oe_regenerar_chave' ); ?>
							<input type="hidden" name="action" value="vb_oe_regenerar_chave">
							<button type="submit" class="button" onclick="return confirm('Gerar nova senha? A antiga deixa de funcionar na automação.');">Gerar nova senha</button>
						</form>
					</td>
				</tr>
			</table>

			<p class="description">Detalhes das tabelas do sistema (SAP) estão no arquivo CONTEXTO.md do plugin e no GitHub.</p>
		</div>
	</details>

	<div class="vb-oe-card-ajuda">
		<h2>Montar a página</h2>
		<p>Veja o passo a passo em <a href="<?php echo esc_url( admin_url( 'admin.php?page=vb-oe-como-usar' ) ); ?>">Como usar</a>.</p>
		<p>Resumo rápido no Elementor: widgets <em>Busca</em>, <em>Filtro</em> e <em>Mapa</em> (categoria Valle Branco), todos com o mesmo <em>Grupo</em>.</p>
	</div>
</div>
