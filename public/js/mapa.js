/**
 * Mapa Onde Encontrar — front-end
 * Carrega locais da API e filtra por texto / cidade / geolocalização.
 */
(function () {
	'use strict';

	function init(root) {
		if (!window.vbOeMapa || !window.L) {
			return;
		}

		var cfg = window.vbOeMapa;
		var mapaEl = root.querySelector('.vb-oe-mapa');
		var listaEl = root.querySelector('.vb-oe-lista');
		var buscaEl = root.querySelector('.vb-oe-busca');
		var cidadeEl = root.querySelector('.vb-oe-cidade');
		var geoBtn = root.querySelector('.vb-oe-geo');

		var map = L.map(mapaEl, { scrollWheelZoom: false }).setView(
			[cfg.mapaLat, cfg.mapaLng],
			cfg.mapaZoom
		);

		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; OpenStreetMap',
			maxZoom: 18,
		}).addTo(map);

		map.on('click', function () {
			map.scrollWheelZoom.enable();
		});

		var markers = L.layerGroup().addTo(map);
		var locais = [];
		var origem = null;

		listaEl.textContent = cfg.i18n.carregando;

		fetch(cfg.apiUrl)
			.then(function (r) {
				return r.json();
			})
			.then(function (data) {
				locais = data.locais || [];
				preencherCidades(locais);
				render();
			})
			.catch(function () {
				listaEl.textContent = cfg.i18n.nenhum;
			});

		function preencherCidades(lista) {
			var set = {};
			lista.forEach(function (l) {
				if (l.cidade) {
					set[l.cidade] = true;
				}
			});
			Object.keys(set)
				.sort()
				.forEach(function (c) {
					var opt = document.createElement('option');
					opt.value = c;
					opt.textContent = c;
					cidadeEl.appendChild(opt);
				});
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

		function filtrar() {
			var q = (buscaEl.value || '').toLowerCase().trim();
			var cidade = cidadeEl.value;

			return locais.filter(function (l) {
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

		function render() {
			var filtrados = filtrar();
			markers.clearLayers();
			listaEl.innerHTML = '';

			if (!filtrados.length) {
				listaEl.textContent = cfg.i18n.nenhum;
				return;
			}

			var bounds = [];

			filtrados.forEach(function (l) {
				var dist = '';
				if (origem) {
					dist =
						' · ' +
						distanciaKm(origem.lat, origem.lng, l.lat, l.lng).toFixed(1) +
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
				markers.addLayer(marker);
				bounds.push([l.lat, l.lng]);

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
					map.flyTo([l.lat, l.lng], 14);
					marker.openPopup();
				});
				listaEl.appendChild(card);
			});

			if (bounds.length) {
				map.fitBounds(bounds, { padding: [30, 30], maxZoom: 12 });
			}
		}

		function escapeHtml(str) {
			return String(str || '')
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;');
		}

		buscaEl.addEventListener('input', render);
		cidadeEl.addEventListener('change', render);

		geoBtn.addEventListener('click', function () {
			if (!navigator.geolocation) {
				return;
			}
			geoBtn.disabled = true;
			navigator.geolocation.getCurrentPosition(
				function (pos) {
					origem = { lat: pos.coords.latitude, lng: pos.coords.longitude };
					L.circleMarker([origem.lat, origem.lng], {
						radius: 8,
						color: '#1a5c3a',
					}).addTo(map);
					map.setView([origem.lat, origem.lng], 10);
					geoBtn.disabled = false;
					render();
				},
				function () {
					geoBtn.disabled = false;
				}
			);
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('[data-vb-oe]').forEach(init);
	});
})();
