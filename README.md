# Maquette Char Promo - Faceted Search

Plugin WordPress/WooCommerce avec moteur de recherche à facettes et endpoint REST.

## Structure

```
maquettecharpromo/
├── src/
│   ├── include/
│   │   ├── rest-products.php    # Endpoint REST
│   │   └── shortcodes.php       # Shortcodes [mcp_products] & [mcp_search]
│   ├── template/
│   │   ├── products-list.php    # Template liste produits
│   │   └── search-facets.php    # Template moteur recherche
│   └── assets/
│       ├── js/
│       │   ├── products-list.js     # JS liste produits
│       │   └── search-facets.js     # JS moteur recherche
│       └── css/                     # Styles
└── .gitignore
```

## Endpoint REST

**URL** : `GET /wp-json/maquettecharpromo/v1/products`

### Paramètres

- **Pagination** : `page`, `per_page` (max 48)
- **Tri** : `orderby` (date|price|popularity|rating), `order` (asc|desc)
- **Recherche** : `search`
- **Prix** : `price_min`, `price_max`
- **Statut** : `in_stock`, `on_sale`
- **Taxonomies** : `categories`, `tags`, `attributes`
- **Inclusion/Exclusion** : `include`, `exclude`

### Réponse

```json
{
  "items": [...],
  "pagination": {
    "page": 1,
    "per_page": 12,
    "total": 352,
    "total_pages": 30
  },
  "facets": {
    "categories": [...],
    "tags": [...],
    "attributes": {...},
    "price_ranges": [...]
  }
}
```

### Cache

- Durée : 120 secondes
- Invalidation automatique sur `save_post_product`
- Rate limiting : 100 req/min par IP

## Shortcodes

### 1. Liste produits (AJAX)

```
[mcp_products limit="12" interactWithSearch="false" filters='{}']
```

**Attributs** :
- `limit` : Nombre de produits par page
- `interactWithSearch` : Liaison avec le moteur de recherche
- `filters` : Filtres initiaux (JSON)

### 2. Moteur de recherche

```
[mcp_search defaults='{}']
```

**Attributs** :
- `defaults` : Filtres par défaut (JSON)

## Logs console

Les deux modules JS utilisent `console.log` pour le debugging :
- `[MCP Products List]` : Logs de la liste produits
- `[MCP Search Facets]` : Logs du moteur de recherche

## Sécurité

- Nonce WP REST requis
- Validation/sanitization stricte de tous les paramètres
- Rate limiting anti-abus
- Limite `per_page` max = 48

## i18n

Text domain : `maquette-char-promo`

Toutes les chaînes sont prêtes pour la traduction.
