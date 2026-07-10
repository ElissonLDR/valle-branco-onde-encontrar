/**
 * Onde Encontrar — mapa, busca, filtro, lista
 * Pin customizado + card no clique.
 */
(function () {
	'use strict';

	if (!window.vbOeMapa || !window.L) {
		return;
	}

	var cfg = window.vbOeMapa;
	var grupos = {};

	var pinIcon = L.icon({
		iconUrl: cfg.pinUrl || '',
		iconSize: [44, 56],
		iconAnchor: [22, 56],
		popupAnchor: [0, -48],
	});

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

	function nomeCurtoProduto(nome) {
		return String(nome || '')
			.replace(/\s+/g, ' ')
			.trim();
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

	function mapsUrl(l) {
		return (
			'https://www.google.com/maps/search/?api=1&query=' +
			encodeURIComponent(l.lat + ',' + l.lng)
		);
	}

	function rotaUrl(l, origem) {
		var dest = l.lat + ',' + l.lng;
		if (origem) {
			return (
				'https://www.google.com/maps/dir/?api=1&origin=' +
				encodeURIComponent(origem.lat + ',' + origem.lng) +
				'&destination=' +
				encodeURIComponent(dest)
			);
		}
		return 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(dest);
	}

	function tituloLocal(l) {
		// Se o título for só o endereço, usa o endereço limpo; senão o nome.
		if (l.nome && l.endereco && l.nome.indexOf(l.endereco) === 0) {
			return l.endereco;
		}
		return l.nome || l.endereco || 'Estabelecimento';
	}

	function montarPopup(l, g) {
		var produtos = (l.produtos || [])
			.slice(0, 8)
			.map(function (p) {
				return '<li>' + escapeHtml(nomeCurtoProduto(p.nome)) + '</li>';
			})
			.join('');

		return (
			'<div class="vb-oe-popup-card">' +
			'<button type="button" class="vb-oe-popup-close" aria-label="Fechar">×</button>' +
			'<h3 class="vb-oe-popup-titulo">' +
			escapeHtml(tituloLocal(l)) +
			'</h3>' +
			'<p class="vb-oe-popup-cidade">' +
			escapeHtml(l.cidade || '') +
			'</p>' +
			'<p class="vb-oe-popup-end">' +
			escapeHtml(l.endereco || '') +
			'</p>' +
			(produtos
				? '<div class="vb-oe-popup-produtos"><span class="vb-oe-popup-label">PRODUTOS DISPONÍVEIS</span><ul>' +
				  produtos +
				  '</ul></div>'
				: '') +
			'<a class="vb-oe-popup-btn" target="_blank" rel="noopener" href="' +
			mapsUrl(l) +
			'">Abrir no Google Maps</a>' +
			'</div>'
		);
	}

	function montarCardLista(l, g, marker) {
		var produtos = (l.produtos || [])
			.slice(0, 6)
			.map(function (p) {
				return (
					'<span class="vb-oe-chip">' +
					escapeHtml(nomeCurtoProduto(p.nome)) +
					'</span>'
				);
			})
			.join('');

		var card = document.createElement('article');
		card.className = 'vb-oe-card';
		card.innerHTML =
			'<div class="vb-oe-card-topo">' +
			'<span class="vb-oe-card-icone" aria-hidden="true"></span>' +
			'<div class="vb-oe-card-textos">' +
			'<h3>' +
			escapeHtml(tituloLocal(l)) +
			'</h3>' +
			'<p class="vb-oe-card-cidade">' +
			escapeHtml(l.cidade || '') +
			'</p>' +
			'<p class="vb-oe-card-end">' +
			escapeHtml(l.endereco || '') +
			'</p>' +
			'</div></div>' +
			(produtos
				? '<div class="vb-oe-card-produtos"><span class="vb-oe-popup-label">PRODUTOS DISPONÍVEIS</span><div class="vb-oe-chips">' +
				  produtos +
				  '</div></div>'
				: '') +
			'<div class="vb-oe-card-acoes">' +
			'<a class="vb-oe-btn-rota" target="_blank" rel="noopener" href="' +
			rotaUrl(l, g.origem) +
			'">Traçar rota</a>' +
			'<a class="vb-oe-btn-maps" target="_blank" rel="noopener" href="' +
			mapsUrl(l) +
			'">Ver no Maps</a>' +
			'</div>';

		card.addEventListener('click', function (e) {
			if (e.target.closest('a')) {
				return;
			}
			if (g.map && marker) {
				g.map.flyTo([l.lat, l.lng], 15);
				marker.openPopup();
			}
		});

		return card;
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
		var iconOpts = cfg.pinUrl ? { icon: pinIcon } : {};

		filtrados.forEach(function (l) {
			if (!l.lat || !l.lng) {
				return;
			}

			var popupHtml = montarPopup(l, g);
			var marker = L.marker([l.lat, l.lng], iconOpts).bindPopup(popupHtml, {
				className: 'vb-oe-leaflet-popup',
				maxWidth: 320,
				minWidth: 260,
			});

			marker.on('popupopen', function (ev) {
				var el = ev.popup.getElement();
				if (!el) {
					return;
				}
				var btn = el.querySelector('.vb-oe-popup-close');
				if (btn) {
					btn.onclick = function () {
						g.map.closePopup();
					};
				}
			});

			g.markers.addLayer(marker);
			bounds.push([l.lat, l.lng]);

			if (g.listaEl) {
				g.listaEl.appendChild(montarCardLista(l, g, marker));
			}
		});

		if (bounds.length) {
			g.map.fitBounds(bounds, { padding: [40, 40], maxZoom: 12 });
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

		g.map = L.map(g.mapaEl, {
			scrollWheelZoom: false,
			smoothWheelZoom: false,
		}).setView([cfg.mapaLat, cfg.mapaLng], cfg.mapaZoom);

		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; OpenStreetMap',
			maxZoom: 18,
		}).addTo(g.map);

		// Ctrl / ⌘ / Alt + rolagem = zoom (igual ao preview).
		var container = g.map.getContainer();
		container.addEventListener(
			'wheel',
			function (e) {
				if (!e.ctrlKey && !e.metaKey && !e.altKey) {
					return;
				}
				e.preventDefault();
				e.stopPropagation();
				if (e.deltaY > 0) {
					g.map.zoomOut();
				} else if (e.deltaY < 0) {
					g.map.zoomIn();
				}
			},
			{ passive: false }
		);

		// Dica visual.
		if (!container.querySelector('.vb-oe-zoom-hint')) {
			var hint = document.createElement('p');
			hint.className = 'vb-oe-zoom-hint';
			hint.textContent = 'Ctrl + rolagem para zoom';
			container.appendChild(hint);
		}

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
								color: '#1a3a6b',
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

	window.addEventListener('elementor/frontend/init', function () {
		if (window.elementorFrontend && elementorFrontend.hooks) {
			elementorFrontend.hooks.addAction('frontend/element_ready/global', function () {
				boot();
			});
		}
	});
})();
