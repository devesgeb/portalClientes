/**
 * PortalApp.js — Capa Core genérica reutilizable
 * ─────────────────────────────────────────────────────────────────────────
 * Razón: En ausencia de framework (React/Angular), este archivo actúa como
 * la capa de utilidades compartidas del Portal.
 * NUNCA contiene lógica de negocio específica de un módulo.
 * ─────────────────────────────────────────────────────────────────────────
 */
'use strict';

window.PortalApp = (function () {

    // ── Formateo de moneda CLP ────────────────────────────────────────────
    function fmt(n) {
        return '$' + Math.round(n || 0).toLocaleString('es-CL');
    }

    // ── Toast notifications ───────────────────────────────────────────────
    function toast(msg, type = 'success') {
        const colors = {
            success: '#22c55e', warning: '#f59e0b',
            danger: '#ef4444', info: '#0ea5e9',
        };
        const icons = {
            success: 'bi-check-circle-fill', warning: 'bi-exclamation-triangle-fill',
            danger: 'bi-trash3-fill', info: 'bi-info-circle-fill',
        };
        const div = document.createElement('div');
        div.style.cssText = `background:${colors[type] || colors.info};color:#fff;padding:10px 18px;` +
            `border-radius:10px;font-size:.80rem;font-weight:500;` +
            `box-shadow:0 4px 16px rgba(0,0,0,.15);margin-top:8px;transition:opacity .3s;`;
        div.innerHTML = `<i class="bi ${icons[type] || icons.info} me-2"></i>${msg}`;
        const wrapper = document.getElementById('toastWrapper');
        if (wrapper) wrapper.appendChild(div);
        setTimeout(() => { div.style.opacity = '0'; setTimeout(() => div.remove(), 300); }, 2800);
    }

    // ── Fetch helper centralizado ─────────────────────────────────────────
    async function apiFetch(url, options = {}) {
        const baseUrl = window.BD_BASE_URL || '/Portal/index.php';
        const headers = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.headers || {}),
        };
        const res = await fetch(baseUrl + url, { ...options, headers });
        const data = await res.json();
        return { res, data, ok: res.ok && (data.success !== false) };
    }

    // ── Bootstrap Modal helpers ───────────────────────────────────────────
    function showModal(id) {
        const el = document.getElementById(id);
        if (el) new bootstrap.Modal(el).show();
    }

    function hideModal(id) {
        const el = document.getElementById(id);
        if (el) {
            const m = bootstrap.Modal.getInstance(el);
            if (m) m.hide();
        }
    }

    // ── DOMReady ─────────────────────────────────────────────────────────
    function DOMReady(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    // ── Autocomplete genérico ─────────────────────────────────────────────
    /**
     * @param {HTMLElement} inputEl   — input donde escribe el usuario
     * @param {string}      fetchUrl  — endpoint sin base_url ej: '/clientes/buscar?q='
     * @param {Function}    onSelect  — callback(item) cuando el usuario selecciona
     * @param {Object}      opts      — { minLen, labelFn }
     */
    function acInit(inputEl, fetchUrl, onSelect, opts = {}) {
        if (!inputEl) return;
        const minLen = opts.minLen ?? 2;
        const labelFn = opts.labelFn || (item => item.nombre || item.razon_social || item.rut || '');

        let dropdown = null;
        let debounceTimer = null;

        function destroyDropdown() {
            if (dropdown) { dropdown.remove(); dropdown = null; }
        }

        function buildDropdown(items) {
            destroyDropdown();
            if (!items.length) return;
            dropdown = document.createElement('ul');
            dropdown.style.cssText =
                'position:absolute;z-index:9999;background:#fff;border:1px solid #d1d5db;' +
                'border-radius:8px;list-style:none;padding:4px 0;margin:2px 0 0;' +
                'min-width:100%;box-shadow:0 4px 12px rgba(0,0,0,.12);font-size:.82rem;max-height:200px;overflow-y:auto;';
            items.forEach(item => {
                const li = document.createElement('li');
                li.style.cssText = 'padding:6px 14px;cursor:pointer;color:#374151;';
                li.textContent = labelFn(item);
                li.addEventListener('mouseenter', () => li.style.background = '#f0f4ff');
                li.addEventListener('mouseleave', () => li.style.background = '');
                li.addEventListener('mousedown', e => {
                    e.preventDefault();
                    onSelect(item);
                    destroyDropdown();
                });
                dropdown.appendChild(li);
            });

            // Posicionar relativo al input
            const parent = inputEl.parentElement;
            const prevPos = parent.style.position;
            parent.style.position = 'relative';
            parent.appendChild(dropdown);
            // restore si no queremos forzar position
            if (!prevPos) parent.style.position = 'relative';
        }

        async function onInput() {
            const q = inputEl.value.trim();
            if (q.length < minLen) { destroyDropdown(); return; }
            try {
                const baseUrl = window.BD_BASE_URL || '/Portal/index.php';
                const res = await fetch(baseUrl + fetchUrl + encodeURIComponent(q),
                    { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const items = await res.json();
                buildDropdown(Array.isArray(items) ? items : []);
            } catch (_) { destroyDropdown(); }
        }

        inputEl.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(onInput, 260);
        });
        inputEl.addEventListener('blur', () => {
            setTimeout(destroyDropdown, 200);
        });
    }

    // API pública
    return { fmt, toast, apiFetch, showModal, hideModal, DOMReady, Autocomplete: { init: acInit } };
})();
