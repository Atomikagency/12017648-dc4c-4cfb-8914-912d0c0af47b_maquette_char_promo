<?php
/**
 * Template: Products List (AJAX)
 * Shortcode: [mcp_products]
 *
 * Available variables:
 * - $limit: Number of products per page
 * - $interact_with_search: Boolean to interact with search
 * - $filters: Array of initial filters
 * - $data: Complete data array
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="mcp-products-list"
     data-limit="<?php echo esc_attr($limit); ?>"
     data-interact="<?php echo esc_attr($interact_with_search ? '1' : '0'); ?>"
     data-filters="<?php echo esc_attr(json_encode($filters)); ?>">

    <!-- Loading state -->
    <div class="mcp-products-loading">
        <p><?php esc_html_e('Chargement des produits...', 'maquette-char-promo'); ?></p>
    </div>

    <!-- Products container (populated by JS) -->
    <div class="mcp-products-container"></div>

    <!-- Pagination container (populated by JS) -->
    <div class="mcp-products-pagination"></div>

</div>

<script>
/**
 * Products List AJAX Handler
 * This script will be replaced by the bundled version from Vite
 */
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.mcp-products-list');

    if (!container) {
        console.warn('[MCP Products List] Container not found');
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
        restUrl: mcpProductsListConfig?.restUrl,
        hasNonce: !!mcpProductsListConfig?.nonce
    });

    // Build query string
    const params = new URLSearchParams({
        per_page: limit,
        page: 1,
        ...filters
    });

    // Make AJAX request
    const url = `${mcpProductsListConfig.restUrl}?${params.toString()}`;

    console.log('[MCP Products List] Fetching products from:', url);

    fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': mcpProductsListConfig.nonce
        }
    })
    .then(response => {
        console.log('[MCP Products List] Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('[MCP Products List] Response data:', data);

        // Hide loading state
        const loading = container.querySelector('.mcp-products-loading');
        if (loading) {
            loading.style.display = 'none';
        }

        // TODO: Render products in UI (to be implemented later)
        console.log(`[MCP Products List] Received ${data.items?.length || 0} products`);
        console.log('[MCP Products List] Pagination:', data.pagination);
        console.log('[MCP Products List] Facets:', data.facets);
    })
    .catch(error => {
        console.error('[MCP Products List] Error:', error);

        // Hide loading state
        const loading = container.querySelector('.mcp-products-loading');
        if (loading) {
            loading.innerHTML = '<p style="color: red;">Erreur lors du chargement des produits.</p>';
        }
    });
});
</script>
