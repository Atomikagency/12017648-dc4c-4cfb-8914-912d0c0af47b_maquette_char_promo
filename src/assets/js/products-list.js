/**
 * Products List AJAX Handler
 * Bundled by Vite
 */

console.log('[MCP Products List] Module loaded');

document.addEventListener('DOMContentLoaded', function() {
    console.log('[MCP Products List] DOM ready');

    const container = document.querySelector('.mcp-products-list');

    if (!container) {
        console.warn('[MCP Products List] Container not found');
        return;
    }

    // Get configuration from WordPress
    if (typeof mcpProductsListConfig === 'undefined') {
        console.error('[MCP Products List] Configuration not found (mcpProductsListConfig)');
        return;
    }

    // Get data attributes
    const limit = parseInt(container.dataset.limit) || 12;
    const interact = container.dataset.interact === '1';
    const filters = JSON.parse(container.dataset.filters || '{}');

    console.log('[MCP Products List] Initialized with config:', {
        limit,
        interact,
        filters,
        restUrl: mcpProductsListConfig.restUrl,
        hasNonce: !!mcpProductsListConfig.nonce
    });

    // State
    let currentPage = 1;
    let currentFilters = { ...filters };

    // Elements
    const loadingEl = container.querySelector('.mcp-products-loading');
    const productsContainer = container.querySelector('.mcp-products-container');
    const paginationContainer = container.querySelector('.mcp-products-pagination');

    /**
     * Fetch products from REST API
     */
    async function fetchProducts(page = 1, customFilters = {}) {
        console.log('[MCP Products List] Fetching products:', { page, customFilters });

        // Show loading
        if (loadingEl) {
            loadingEl.style.display = 'block';
        }

        // Build query params
        const params = new URLSearchParams({
            per_page: limit,
            page: page,
            ...currentFilters,
            ...customFilters
        });

        // Clean up empty params
        for (const [key, value] of [...params.entries()]) {
            if (!value || value === '') {
                params.delete(key);
            }
        }

        const url = `${mcpProductsListConfig.restUrl}?${params.toString()}`;

        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': mcpProductsListConfig.nonce
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            console.log('[MCP Products List] Response received:', {
                itemsCount: data.items?.length || 0,
                pagination: data.pagination,
                facetsCount: Object.keys(data.facets || {}).length
            });

            console.log('[MCP Products List] Full data:', data);

            // Hide loading
            if (loadingEl) {
                loadingEl.style.display = 'none';
            }

            // Update current page
            currentPage = page;

            // TODO: Render products (UI implementation)
            renderProducts(data);

        } catch (error) {
            console.error('[MCP Products List] Fetch error:', error);

            // Show error
            if (loadingEl) {
                loadingEl.innerHTML = `<p style="color: red;">Erreur: ${error.message}</p>`;
            }
        }
    }

    /**
     * Render products (placeholder for UI)
     */
    function renderProducts(data) {
        console.log('[MCP Products List] Rendering products...');

        // Items
        if (data.items && data.items.length > 0) {
            console.log(`[MCP Products List] ${data.items.length} products to render`);
            console.table(data.items.map(item => ({
                ID: item.id,
                Title: item.title,
                Price: item.price.raw,
                'On Sale': item.on_sale ? 'Yes' : 'No',
                'In Stock': item.stock_status === 'instock' ? 'Yes' : 'No'
            })));
        } else {
            console.log('[MCP Products List] No products found');
        }

        // Pagination
        if (data.pagination) {
            console.log('[MCP Products List] Pagination:', data.pagination);
        }

        // Facets
        if (data.facets) {
            console.log('[MCP Products List] Facets:', data.facets);
        }
    }

    /**
     * Listen to filter changes from search facets
     */
    if (interact) {
        console.log('[MCP Products List] Listening to filter changes');

        window.addEventListener('mcpFiltersChanged', function(event) {
            console.log('[MCP Products List] Filters changed event received:', event.detail);

            // Update filters and fetch
            currentFilters = { ...filters, ...event.detail };
            fetchProducts(1, event.detail);
        });
    }

    // Initial fetch
    fetchProducts(currentPage);

    console.log('[MCP Products List] Ready');
});
