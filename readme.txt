=== Valle Branco — Onde Encontrar ===
Contributors: vallebranco
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later

Mapa de pontos de venda com produtos, painel de controle e API para n8n + SAP B1.

== Description ==

Plugin do site Valle Branco para a funcionalidade **Onde encontrar**.

* Produtos e estabelecimentos (CPTs)
* Relação produto × local com data de entrada e atualização
* Relatório no painel (dias no local, alertas)
* API REST para automação n8n (notas fiscais → mapa)
* Shortcode `[vb_onde_encontrar]` com mapa Leaflet

O preview visual foi feito no Lovable; este plugin é a versão WordPress de produção.

== Installation ==

1. Envie a pasta `valle-branco-onde-encontrar` para `/wp-content/plugins/`
2. Ative o plugin em Plugins
3. Vá em **Onde Encontrar → Configurações** e copie a chave de API para o n8n
4. Crie uma página e use o shortcode `[vb_onde_encontrar]`

== Changelog ==

= 1.1.0 =
* Aceita payload SAP B1 (ItemCode, CardCode, DocNum…) via n8n
* Código SAP (CardCode) no estabelecimento
* Documentação das tabelas OINV, OCRD, OITM, OITB, ORDR, RDR1, OCPR, OSLP

= 1.0.0 =
* Versão inicial: CPTs, tabela de relações, API n8n, relatório e mapa
