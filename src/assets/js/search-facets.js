/**
 * Search Facets Handler
 * Bundled by Vite
 */

console.log('[MCP Search Facets] Module loaded');

document.addEventListener('DOMContentLoaded', function() {
    console.log('[MCP Search Facets] DOM ready');

    const container = document.querySelector('.mcp-search-facets');

    if (!container) {
        console.warn('[MCP Search Facets] Container not found');
        return;
    }

    // Get configuration from WordPress
    if (typeof mcpSearchFacetsConfig === 'undefined') {
        console.error('[MCP Search Facets] Configuration not found (mcpSearchFacetsConfig)');
        return;
    }

    // Get defaults
    const defaults = JSON.parse(container.dataset.defaults || '{}');

    console.log('[MCP Search Facets] Initialized with config:', {
        defaults,
        restUrl: mcpSearchFacetsConfig.restUrl,
        hasNonce: !!mcpSearchFacetsConfig.nonce
    });

    // Initialize from URL
    initFromURL();

    // Setup event listeners
    setupEventListeners();

    console.log('[MCP Search Facets] Ready');

    /**
     * Initialize form from URL parameters
     */
    function initFromURL() {
        console.log('[MCP Search Facets] Reading URL parameters');

        const urlParams = new URLSearchParams(window.location.search);
        const currentFilters = {};

        for (const [key, value] of urlParams.entries()) {
            currentFilters[key] = value;
            console.log(`[MCP Search Facets] URL param: ${key} = ${value}`);
        }

        // Apply to form
        applyFiltersToForm(currentFilters);

        // If no URL params, apply defaults
        if (Object.keys(currentFilters).length === 0 && Object.keys(defaults).length > 0) {
            console.log('[MCP Search Facets] No URL params, applying defaults:', defaults);
            applyFiltersToForm(defaults);
            updateURL(defaults);
        }
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Listen to form changes (real-time logging)
        container.addEventListener('change', function(e) {
            if (e.target.matches('input, select')) {
                console.log('[MCP Search Facets] Input changed:', {
                    name: e.target.name,
                    value: e.target.type === 'checkbox' ? e.target.checked : e.target.value,
                    type: e.target.type
                });
            }
        });

        // Apply filters button
        const applyBtn = container.querySelector('.mcp-apply-filters');
        if (applyBtn) {
            applyBtn.addEventListener('click', handleApplyFilters);
        }

        // Reset filters button
        const resetBtn = container.querySelector('.mcp-reset-filters');
        if (resetBtn) {
            resetBtn.addEventListener('click', handleResetFilters);
        }

        // Browser back/forward
        window.addEventListener('popstate', handlePopState);
    }

    /**
     * Handle apply filters
     */
    function handleApplyFilters() {
        console.log('[MCP Search Facets] Apply filters clicked');

        const filters = collectFilters();
        console.log('[MCP Search Facets] Collected filters:', filters);

        // Update URL
        updateURL(filters);

        // Dispatch event for products list
        dispatchFiltersChanged(filters);
    }

    /**
     * Handle reset filters
     */
    function handleResetFilters() {
        console.log('[MCP Search Facets] Reset filters clicked');

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

        // Update URL (clear)
        updateURL({});

        // Dispatch event
        dispatchFiltersChanged({});

        console.log('[MCP Search Facets] Filters reset');
    }

    /**
     * Handle browser back/forward
     */
    function handlePopState() {
        console.log('[MCP Search Facets] Browser navigation detected');

        const urlParams = new URLSearchParams(window.location.search);
        const filters = {};

        for (const [key, value] of urlParams.entries()) {
            filters[key] = value;
        }

        console.log('[MCP Search Facets] Applying filters from URL:', filters);

        applyFiltersToForm(filters);
        dispatchFiltersChanged(filters);
    }

    /**
     * Collect filters from form
     */
    function collectFilters() {
        const filters = {};

        // Text search
        const searchInput = container.querySelector('[name="q"]');
        if (searchInput?.value) {
            filters.search = searchInput.value.trim();
        }

        // Price range
        const priceMin = container.querySelector('[name="price_min"]');
        if (priceMin?.value) {
            filters.price_min = priceMin.value;
        }

        const priceMax = container.querySelector('[name="price_max"]');
        if (priceMax?.value) {
            filters.price_max = priceMax.value;
        }

        // Checkboxes
        const inStock = container.querySelector('[name="in_stock"]');
        if (inStock?.checked) {
            filters.in_stock = '1';
        }

        const onSale = container.querySelector('[name="on_sale"]');
        if (onSale?.checked) {
            filters.on_sale = '1';
        }

        // Sort
        const orderby = container.querySelector('[name="orderby"]');
        if (orderby?.value && orderby.value !== 'date') {
            filters.orderby = orderby.value;
        }

        const order = container.querySelector('[name="order"]');
        if (order?.value && order.value !== 'desc') {
            filters.order = order.value;
        }

        return filters;
    }

    /**
     * Apply filters to form inputs
     */
    function applyFiltersToForm(filters) {
        console.log('[MCP Search Facets] Applying filters to form:', filters);

        // Search
        const searchInput = container.querySelector('[name="q"]');
        if (searchInput) {
            searchInput.value = filters.q || filters.search || '';
        }

        // Price
        const priceMin = container.querySelector('[name="price_min"]');
        if (priceMin) {
            priceMin.value = filters.price_min || '';
        }

        const priceMax = container.querySelector('[name="price_max"]');
        if (priceMax) {
            priceMax.value = filters.price_max || '';
        }

        // Checkboxes
        const inStock = container.querySelector('[name="in_stock"]');
        if (inStock) {
            inStock.checked = filters.in_stock === '1';
        }

        const onSale = container.querySelector('[name="on_sale"]');
        if (onSale) {
            onSale.checked = filters.on_sale === '1';
        }

        // Sort
        const orderby = container.querySelector('[name="orderby"]');
        if (orderby && filters.orderby) {
            orderby.value = filters.orderby;
        }

        const order = container.querySelector('[name="order"]');
        if (order && filters.order) {
            order.value = filters.order;
        }
    }

    /**
     * Update browser URL
     */
    function updateURL(filters) {
        const url = new URL(window.location);

        // Clear search params
        url.search = '';

        // Add non-empty filters
        Object.keys(filters).forEach(key => {
            if (filters[key] && filters[key] !== '') {
                url.searchParams.set(key, filters[key]);
            }
        });

        console.log('[MCP Search Facets] Updating URL:', url.toString());

        // Update without reload
        window.history.pushState({}, '', url);
    }

    /**
     * Dispatch custom event for products list
     */
    function dispatchFiltersChanged(filters) {
        console.log('[MCP Search Facets] Dispatching mcpFiltersChanged event:', filters);

        window.dispatchEvent(new CustomEvent('mcpFiltersChanged', {
            detail: filters
        }));
    }

    /**
     * Fetch facets from API (for dynamic rendering)
     */
    async function fetchFacets(currentFilters = {}) {
        console.log('[MCP Search Facets] Fetching facets with filters:', currentFilters);

        try {
            const params = new URLSearchParams(currentFilters);
            const url = `${mcpSearchFacetsConfig.restUrl}?${params.toString()}`;

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': mcpSearchFacetsConfig.nonce
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            console.log('[MCP Search Facets] Facets received:', data.facets);

            // TODO: Render facets dynamically (categories, tags, attributes)
            renderFacets(data.facets);

        } catch (error) {
            console.error('[MCP Search Facets] Error fetching facets:', error);
        }
    }

    /**
     * Render facets (placeholder for UI)
     */
    function renderFacets(facets) {
        console.log('[MCP Search Facets] Rendering facets...');

        if (facets.categories?.length > 0) {
            console.log('[MCP Search Facets] Categories:', facets.categories);
        }

        if (facets.tags?.length > 0) {
            console.log('[MCP Search Facets] Tags:', facets.tags);
        }

        if (facets.attributes && Object.keys(facets.attributes).length > 0) {
            console.log('[MCP Search Facets] Attributes:', facets.attributes);
        }

        if (facets.price_ranges?.length > 0) {
            console.log('[MCP Search Facets] Price ranges:', facets.price_ranges);
        }
    }

    // Optionally fetch initial facets
    // fetchFacets();
});
