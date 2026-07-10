/**
 * Onde Encontrar — peças separadas (mapa, busca, filtro, lista)
 * Peças com o mesmo data-vb-grupo conversam entre si na página.
 */
(function () {
	'use strict';

	if (!window.vbOeMapa || !window.L) {
		return;
	}

	var cfg = window.vbOeMapa;
	var grupos = {};

	function getGrupo(id) {
		id = id || 'padrao';
		if (!grupos[id]) {
			grupos[id] = {
				id: id,
				mapaEl: null,
				buscaEl: null,
				cidadeEl: null,
				geoBtn: null,
				listaEl: null,
				map: null,
				markers: null,
				locais: null,
				origem: null,
				carregando: false,
				pronto: false,
			};
		}
		return grupos[id];
	}

	function escapeHtml(str) {
		return String(str || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function distanciaKm(aLat, aLng, bLat, bLng) {
		var R = 6371;
		var dLat = ((bLat - aLat) * Math.PI) / 180;
		var dLng = ((bLng - aLng) * Math.PI) / 180;
		var a =
			Math.sin(dLat / 2) * Math.sin(dLat / 2) +
			Math.cos((aLat * Math.PI) / 180) *
				Math.cos((bLat * Math.PI) / 180) *
				Math.sin(dLng / 2) *
				Math.sin(dLng / 2);
		return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
	}

	function filtrar(g) {
		var q = g.buscaEl ? (g.buscaEl.value || '').toLowerCase().trim() : '';
		var cidade = g.cidadeEl ? g.cidadeEl.value : '';
		var lista = g.locais || [];

		return lista.filter(function (l) {
			if (cidade && l.cidade !== cidade) {
				return false;
			}
			if (!q) {
				return true;
			}
			var texto =
				(l.nome || '') +
				' ' +
				(l.cidade || '') +
				' ' +
				(l.endereco || '') +
				' ' +
				(l.produtos || [])
					.map(function (p) {
						return p.nome;
					})
					.join(' ');
			return texto.toLowerCase().indexOf(q) !== -1;
		});
	}

	function preencherCidades(g) {
		if (!g.cidadeEl || !g.locais) {
			return;
		}
		var atual = g.cidadeEl.value;
		var set = {};
		g.locais.forEach(function (l) {
			if (l.cidade) {
				set[l.cidade] = true;
			}
		});
		g.cidadeEl.innerHTML = '';
		var opt0 = document.createElement('option');
		opt0.value = '';
		opt0.textContent = cfg.i18n.todas;
		g.cidadeEl.appendChild(opt0);
		Object.keys(set)
			.sort()
			.forEach(function (c) {
				var opt = document.createElement('option');
				opt.value = c;
				opt.textContent = c;
				g.cidadeEl.appendChild(opt);
			});
		if (atual && set[atual]) {
			g.cidadeEl.value = atual;
		}
	}

	function render(g) {
		if (!g.map || !g.markers || !g.locais) {
			return;
		}

		var filtrados = filtrar(g);
		g.markers.clearLayers();

		if (g.listaEl) {
			g.listaEl.innerHTML = '';
		}

		if (!filtrados.length) {
			if (g.listaEl) {
				g.listaEl.textContent = cfg.i18n.nenhum;
			}
			return;
		}

		var bounds = [];

		filtrados.forEach(function (l) {
			var dist = '';
			if (g.origem) {
				dist =
					' · ' +
					distanciaKm(g.origem.lat, g.origem.lng, l.lat, l.lng).toFixed(1) +
					' km';
			}

			var produtosHtml = (l.produtos || [])
				.map(function (p) {
					return '<li>' + escapeHtml(p.nome) + '</li>';
				})
				.join('');

			var popup =
				'<div class="vb-oe-popup"><strong>' +
				escapeHtml(l.nome) +
				'</strong>' +
				escapeHtml(l.cidade || '') +
				'<br>' +
				escapeHtml(l.endereco || '') +
				dist +
				(produtosHtml
					? '<p><em>' + cfg.i18n.produtos + '</em></p><ul>' + produtosHtml + '</ul>'
					: '') +
				'<p><a target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query=' +
				encodeURIComponent(l.lat + ',' + l.lng) +
				'">' +
				cfg.i18n.abrirMaps +
				'</a></p></div>';

			var marker = L.marker([l.lat, l.lng]).bindPopup(popup);
			g.markers.addLayer(marker);
			bounds.push([l.lat, l.lng]);

			if (g.listaEl) {
				var card = document.createElement('article');
				card.className = 'vb-oe-card';
				card.innerHTML =
					'<h3>' +
					escapeHtml(l.nome) +
					'</h3><p>' +
					escapeHtml(l.cidade || '') +
					' — ' +
					escapeHtml(l.endereco || '') +
					dist +
					'</p>' +
					(produtosHtml ? '<ul>' + produtosHtml + '</ul>' : '');
				card.addEventListener('click', function () {
					g.map.flyTo([l.lat, l.lng], 14);
					marker.openPopup();
				});
				g.listaEl.appendChild(card);
			}
		});

		if (bounds.length) {
			g.map.fitBounds(bounds, { padding: [30, 30], maxZoom: 12 });
		}

		setTimeout(function () {
			g.map.invalidateSize();
		}, 100);
	}

	function carregarDados(g) {
		if (g.carregando || g.locais) {
			if (g.locais) {
				preencherCidades(g);
				render(g);
			}
			return;
		}
		g.carregando = true;
		if (g.listaEl) {
			g.listaEl.textContent = cfg.i18n.carregando;
		}

		fetch(cfg.apiUrl)
			.then(function (r) {
				return r.json();
			})
			.then(function (data) {
				g.locais = data.locais || [];
				g.carregando = false;
				preencherCidades(g);
				render(g);
			})
			.catch(function () {
				g.carregando = false;
				g.locais = [];
				if (g.listaEl) {
					g.listaEl.textContent = cfg.i18n.nenhum;
				}
			});
	}

	function iniciarMapa(g) {
		if (!g.mapaEl || g.map) {
			return;
		}

		g.map = L.map(g.mapaEl, { scrollWheelZoom: false }).setView(
			[cfg.mapaLat, cfg.mapaLng],
			cfg.mapaZoom
		);

		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; OpenStreetMap',
			maxZoom: 18,
		}).addTo(g.map);

		g.map.on('click', function () {
			g.map.scrollWheelZoom.enable();
		});

		g.markers = L.layerGroup().addTo(g.map);
		carregarDados(g);
	}

	function ligarEventos(g) {
		if (g.pronto) {
			return;
		}
		g.pronto = true;

		if (g.buscaEl) {
			g.buscaEl.addEventListener('input', function () {
				render(g);
			});
		}
		if (g.cidadeEl) {
			g.cidadeEl.addEventListener('change', function () {
				render(g);
			});
		}
		if (g.geoBtn) {
			g.geoBtn.addEventListener('click', function () {
				if (!navigator.geolocation) {
					return;
				}
				g.geoBtn.disabled = true;
				navigator.geolocation.getCurrentPosition(
					function (pos) {
						g.origem = {
							lat: pos.coords.latitude,
							lng: pos.coords.longitude,
						};
						if (g.map) {
							L.circleMarker([g.origem.lat, g.origem.lng], {
								radius: 8,
								color: '#1a5c3a',
							}).addTo(g.map);
							g.map.setView([g.origem.lat, g.origem.lng], 10);
						}
						g.geoBtn.disabled = false;
						render(g);
					},
					function () {
						g.geoBtn.disabled = false;
					}
				);
			});
		}
	}

	function registrarPeca(el) {
		var grupoId = el.getAttribute('data-vb-grupo') || 'padrao';
		var g = getGrupo(grupoId);

		if (el.hasAttribute('data-vb-oe-mapa')) {
			g.mapaEl = el;
		}
		if (el.hasAttribute('data-vb-oe-busca')) {
			g.buscaEl = el.querySelector('.vb-oe-busca') || el;
		}
		if (el.hasAttribute('data-vb-oe-filtro')) {
			g.cidadeEl = el.querySelector('.vb-oe-cidade');
			g.geoBtn = el.querySelector('.vb-oe-geo');
		}
		if (el.hasAttribute('data-vb-oe-lista')) {
			g.listaEl = el;
		}

		ligarEventos(g);
		iniciarMapa(g);
		if (g.locais) {
			preencherCidades(g);
			render(g);
		} else if (!g.mapaEl) {
			// Busca/filtro sozinhos ainda podem carregar dados para preencher cidades.
			carregarDados(g);
		}
	}

	function boot() {
		document
			.querySelectorAll(
				'[data-vb-oe-mapa], [data-vb-oe-busca], [data-vb-oe-filtro], [data-vb-oe-lista]'
			)
			.forEach(registrarPeca);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	// Elementor: reinicia ao editar/preview.
	window.addEventListener('elementor/frontend/init', function () {
		if (window.elementorFrontend && elementorFrontend.hooks) {
			elementorFrontend.hooks.addAction('frontend/element_ready/global', function () {
				boot();
			});
		}
	});
})();
