# Cammino product catalogue

1. Create or edit a WordPress page.
2. Choose **Cammino — WooCommerce products** in the page's Template setting and save.
3. To use it as the main store, select that page under **WooCommerce → Settings → Products → Shop page**.

The page title is the catalogue heading. Products, images, short descriptions, prices, availability, and cart actions come from WooCommerce. Add and edit them under **Products**. The main Shop uses WooCommerce's catalogue query and pagination; a regular page uses a paginated product shortcode with 12 products per page. The result count and sorting dropdown are removed from this template. Hidden products stay out of the catalogue. The page body is not rendered; no product block or shortcode needs to be added manually.

The catalogue uses the shared Cammino header, footer, fonts, and background. Its card styling is in `assets/products.css`. `bootstrap.php` loads the integration through one require in the theme's `functions.php`. Other store pages retain their existing templates.

The catalogue uses `parts/product-card.php` for a dedicated image, title, description, price, and single action. The image fits inside its pastel panel without cropping. Astra's category label and duplicate overlay action are omitted. WooCommerce still generates the cart action, including variable-product links and AJAX cart attributes. The custom card is selected only for this catalogue. Reference: [WooCommerce product shortcodes](https://woocommerce.com/document/woocommerce-shortcodes/products/).
