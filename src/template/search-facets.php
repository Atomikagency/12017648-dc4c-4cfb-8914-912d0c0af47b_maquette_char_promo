<?php
/**
 * Template: Search Facets
 * Shortcode: [mcp_search]
 *
 * Available variables:
 * - $defaults: Array of default filter values
 * - $data: Complete data array
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="mcp-search-facets" data-defaults="<?php echo esc_attr(json_encode($defaults)); ?>">

    <!-- Search input -->
    <div class="mcp-search-field">
        <label for="mcp-search-query"><?php esc_html_e('Rechercher', 'maquette-char-promo'); ?></label>
        <input type="text"
               id="mcp-search-query"
               name="q"
               placeholder="<?php esc_attr_e('Rechercher un produit...', 'maquette-char-promo'); ?>">
    </div>

    <!-- Price range filters -->
    <div class="mcp-price-filters">
        <h3><?php esc_html_e('Prix', 'maquette-char-promo'); ?></h3>

        <div class="mcp-price-inputs">
            <label>
                <?php esc_html_e('Min', 'maquette-char-promo'); ?>
                <input type="number"
                       name="price_min"
                       min="0"
                       step="0.01"
                       placeholder="0">
            </label>

            <label>
                <?php esc_html_e('Max', 'maquette-char-promo'); ?>
                <input type="number"
                       name="price_max"
                       min="0"
                       step="0.01"
                       placeholder="999">
            </label>
        </div>
    </div>

    <!-- Stock/Sale filters -->
    <div class="mcp-status-filters">
        <h3><?php esc_html_e('Disponibilité', 'maquette-char-promo'); ?></h3>

        <label>
            <input type="checkbox" name="in_stock" value="1">
            <?php esc_html_e('En stock', 'maquette-char-promo'); ?>
        </label>

        <label>
            <input type="checkbox" name="on_sale" value="1">
            <?php esc_html_e('En promotion', 'maquette-char-promo'); ?>
        </label>
    </div>

    <!-- Categories filter -->
    <div class="mcp-categories-filter">
        <h3><?php esc_html_e('Catégories', 'maquette-char-promo'); ?></h3>
        <div class="mcp-categories-container">
            <!-- Will be populated by JS -->
        </div>
    </div>

    <!-- Tags filter -->
    <div class="mcp-tags-filter">
        <h3><?php esc_html_e('Tags', 'maquette-char-promo'); ?></h3>
        <div class="mcp-tags-container">
            <!-- Will be populated by JS -->
        </div>
    </div>

    <!-- Attributes filter -->
    <div class="mcp-attributes-filter">
        <h3><?php esc_html_e('Attributs', 'maquette-char-promo'); ?></h3>
        <div class="mcp-attributes-container">
            <!-- Will be populated by JS -->
        </div>
    </div>

    <!-- Sort -->
    <div class="mcp-sort-filter">
        <label for="mcp-sort-select"><?php esc_html_e('Trier par', 'maquette-char-promo'); ?></label>
        <select id="mcp-sort-select" name="orderby">
            <option value="date"><?php esc_html_e('Plus récents', 'maquette-char-promo'); ?></option>
            <option value="price"><?php esc_html_e('Prix', 'maquette-char-promo'); ?></option>
            <option value="popularity"><?php esc_html_e('Popularité', 'maquette-char-promo'); ?></option>
            <option value="rating"><?php esc_html_e('Note', 'maquette-char-promo'); ?></option>
        </select>

        <select name="order">
            <option value="asc"><?php esc_html_e('Croissant', 'maquette-char-promo'); ?></option>
            <option value="desc"><?php esc_html_e('Décroissant', 'maquette-char-promo'); ?></option>
        </select>
    </div>

    <!-- Actions -->
    <div class="mcp-search-actions">
        <button type="button" class="mcp-apply-filters">
            <?php esc_html_e('Appliquer les filtres', 'maquette-char-promo'); ?>
        </button>

        <button type="button" class="mcp-reset-filters">
            <?php esc_html_e('Réinitialiser', 'maquette-char-promo'); ?>
        </button>
    </div>

</div>

<script>
/**
 * Search Facets Handler
 * This script will be replaced by the bundled version from Vite
 */
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.mcp-search-facets');

    if (!container) {
        console.warn('[MCP Search Facets] Container not found');
        return;
    }

    // Get defaults
    const defaults = JSON.parse(container.dataset.defaults || '{}');

    console.log('[MCP Search Facets] Initialized with config:', {
        defaults,
        restUrl: mcpSearchFacetsConfig?.restUrl,
        hasNonce: !!mcpSearchFacetsConfig?.nonce
    });

    // Read URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const currentFilters = {};

    for (const [key, value] of urlParams.entries()) {
        currentFilters[key] = value;
        console.log(`[MCP Search Facets] URL param: ${key} = ${value}`);
    }

    // Apply URL params to form inputs
    applyFiltersToForm(currentFilters);

    // Listen to form changes
    container.addEventListener('change', function(e) {
        console.log('[MCP Search Facets] Input changed:', e.target.name, '=', e.target.value);
    });

    // Apply filters button
    const applyBtn = container.querySelector('.mcp-apply-filters');
    if (applyBtn) {
        applyBtn.addEventListener('click', function() {
            const filters = collectFilters();
            console.log('[MCP Search Facets] Apply filters:', filters);

            // Update URL
            updateURL(filters);
        });
    }

    // Reset filters button
    const resetBtn = container.querySelector('.mcp-reset-filters');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            console.log('[MCP Search Facets] Reset filters');

            // Clear form
            container.querySelectorAll('input[type="text"], input[type="number"]').forEach(input => {
                input.value = '';
            });
            container.querySelectorAll('input[type="checkbox"]').forEach(input => {
                input.checked = false;
            });
            container.querySelectorAll('select').forEach(select => {
                select.selectedIndex = 0;
            });

            // Update URL (clear all params)
            updateURL({});
        });
    }

    /**
     * Collect filters from form inputs
     */
    function collectFilters() {
        const filters = {};

        // Text inputs
        const searchInput = container.querySelector('[name="q"]');
        if (searchInput && searchInput.value) {
            filters.search = searchInput.value;
        }

        // Number inputs
        const priceMin = container.querySelector('[name="price_min"]');
        if (priceMin && priceMin.value) {
            filters.price_min = priceMin.value;
        }

        const priceMax = container.querySelector('[name="price_max"]');
        if (priceMax && priceMax.value) {
            filters.price_max = priceMax.value;
        }

        // Checkboxes
        const inStock = container.querySelector('[name="in_stock"]');
        if (inStock && inStock.checked) {
            filters.in_stock = '1';
        }

        const onSale = container.querySelector('[name="on_sale"]');
        if (onSale && onSale.checked) {
            filters.on_sale = '1';
        }

        // Select inputs
        const orderby = container.querySelector('[name="orderby"]');
        if (orderby && orderby.value) {
            filters.orderby = orderby.value;
        }

        const order = container.querySelector('[name="order"]');
        if (order && order.value) {
            filters.order = order.value;
        }

        return filters;
    }

    /**
     * Apply filters to form inputs
     */
    function applyFiltersToForm(filters) {
        // Search
        if (filters.q) {
            const input = container.querySelector('[name="q"]');
            if (input) input.value = filters.q;
        }

        // Price
        if (filters.price_min) {
            const input = container.querySelector('[name="price_min"]');
            if (input) input.value = filters.price_min;
        }
        if (filters.price_max) {
            const input = container.querySelector('[name="price_max"]');
            if (input) input.value = filters.price_max;
        }

        // Checkboxes
        if (filters.in_stock) {
            const input = container.querySelector('[name="in_stock"]');
            if (input) input.checked = true;
        }
        if (filters.on_sale) {
            const input = container.querySelector('[name="on_sale"]');
            if (input) input.checked = true;
        }

        // Selects
        if (filters.orderby) {
            const input = container.querySelector('[name="orderby"]');
            if (input) input.value = filters.orderby;
        }
        if (filters.order) {
            const input = container.querySelector('[name="order"]');
            if (input) input.value = filters.order;
        }

        console.log('[MCP Search Facets] Applied filters to form:', filters);
    }

    /**
     * Update URL with filters
     */
    function updateURL(filters) {
        const url = new URL(window.location);

        // Clear all search params
        url.search = '';

        // Add new filters
        Object.keys(filters).forEach(key => {
            if (filters[key]) {
                url.searchParams.set(key, filters[key]);
            }
        });

        console.log('[MCP Search Facets] New URL:', url.toString());

        // Update browser URL without reload
        window.history.pushState({}, '', url);

        // Dispatch event for products list to react
        window.dispatchEvent(new CustomEvent('mcpFiltersChanged', {
            detail: filters
        }));
    }

    // Listen to popstate (browser back/forward)
    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const filters = {};

        for (const [key, value] of urlParams.entries()) {
            filters[key] = value;
        }

        console.log('[MCP Search Facets] Browser navigation, applying filters:', filters);
        applyFiltersToForm(filters);

        // Dispatch event
        window.dispatchEvent(new CustomEvent('mcpFiltersChanged', {
            detail: filters
        }));
    });
});
</script>
