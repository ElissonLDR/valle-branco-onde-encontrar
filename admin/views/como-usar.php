<?php
/**
 * View: como usar no site (linguagem simples).
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap vb-oe-admin vb-oe-ajuda">
	<h1>Como usar o Onde Encontrar</h1>

	<div class="vb-oe-card-ajuda">
		<h2>1. Cadastre os dados</h2>
		<ol>
			<li>Em <strong>Onde Encontrar → Produtos</strong>, cadastre (ou deixe a automação cadastrar) os produtos.</li>
			<li>Em <strong>Onde Encontrar → Estabelecimentos</strong>, cadastre as lojas com endereço e, se possível, latitude/longitude (para aparecer no mapa).</li>
			<li>Em <strong>Relatório</strong> ou <strong>Nova entrada</strong>, vincule produto ↔ loja (ou deixe a automação fazer isso).</li>
		</ol>
	</div>

	<div class="vb-oe-card-ajuda">
		<h2>2. Monte a página no Elementor</h2>
		<p>No editor do Elementor, procure a categoria <strong>Valle Branco</strong>. Há 5 peças:</p>
		<ul>
			<li><strong>Busca</strong> — digite e pressione Enter (no celular, toque na lupa).</li>
			<li><strong>Filtro</strong> — cidade + “usar minha localização”.</li>
			<li><strong>Produtos</strong> — chips “Produtos na rede” (filtra o mapa).</li>
			<li><strong>Mapa</strong> — o mapa com os pinos.</li>
			<li><strong>Lista</strong> — estabelecimentos em grade com paginação.</li>
		</ul>
		<p><strong>Importante:</strong> em todas as peças da mesma página, deixe o campo <em>Grupo</em> igual — por exemplo <code>padrao</code>.</p>
	</div>

	<div class="vb-oe-card-ajuda">
		<h2>3. Ou use códigos curtos (shortcodes)</h2>
		<p>Se preferir colar em uma página/bloco HTML:</p>
		<table class="widefat striped" style="max-width:640px">
			<thead>
				<tr><th>O que coloca</th><th>Código</th></tr>
			</thead>
			<tbody>
				<tr><td>Só o mapa</td><td><code>[vb_oe_mapa]</code></td></tr>
				<tr><td>Só a busca</td><td><code>[vb_oe_busca]</code></td></tr>
				<tr><td>Só o filtro</td><td><code>[vb_oe_filtro]</code></td></tr>
				<tr><td>Produtos na rede</td><td><code>[vb_oe_produtos]</code></td></tr>
				<tr><td>Lista de lojas</td><td><code>[vb_oe_lista]</code></td></tr>
				<tr><td>Tudo junto</td><td><code>[vb_onde_encontrar]</code></td></tr>
			</tbody>
		</table>
		<p>Exemplo com grupo personalizado: <code>[vb_oe_mapa grupo="home"]</code> + <code>[vb_oe_busca grupo="home"]</code></p>
	</div>

	<div class="vb-oe-card-ajuda">
		<h2>4. O que cada menu faz</h2>
		<ul>
			<li><strong>Relatório</strong> — vê o que está em cada loja e há quantos dias.</li>
			<li><strong>Produtos / Estabelecimentos</strong> — cadastro manual.</li>
			<li><strong>Nova entrada</strong> — liga um produto a uma loja na mão.</li>
			<li><strong>Configurações</strong> — ajustes simples do mapa e (se precisar) da automação.</li>
		</ul>
	</div>
</div>
