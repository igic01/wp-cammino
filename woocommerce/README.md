# Cammino product catalogue

1. Create or edit a WordPress page.
2. Choose **Cammino — WooCommerce products** in the page's Template setting and save.
3. To use it as the main store, select that page under **WooCommerce → Settings → Products → Shop page**.

The page title is the catalogue heading. Products, images, short descriptions, prices, availability, and cart actions come from WooCommerce. Add and edit them under **Products**. The main Shop uses WooCommerce's catalogue query and pagination; a regular page uses a paginated product shortcode with 12 products per page. The result count and sorting dropdown are removed from this template. Hidden products stay out of the catalogue. The page body is not rendered; no product block or shortcode needs to be added manually.

The catalogue and individual product pages use the shared Cammino header, footer, fonts, and background. Catalogue card styling is in `assets/products.css`. `single-product.php` wraps WooCommerce's native product content, preserving its gallery, variations, purchase form, tabs, and related products; its styling is in `assets/single-product.css`. Product pages use this wrapper automatically. `bootstrap.php` loads the integration through one require in the theme's `functions.php`. Cart, checkout, account, and category pages retain their existing templates.

The catalogue and related products use `parts/product-card.php` for a dedicated image, title, description, price, and single action. The image fits inside its pastel panel without cropping. Astra's category label and duplicate overlay action are omitted. WooCommerce still generates the cart action, including variable-product links and AJAX cart attributes. Reference: [WooCommerce product shortcodes](https://woocommerce.com/document/woocommerce-shortcodes/products/).

Product details use a sage gallery panel beside a cream summary panel, with plum headings, coral purchase controls, pill-shaped tabs, and a back link to the Shop. The panels stack on narrow screens. Repeated category labels and the summary breadcrumb are hidden. Native gallery zoom, variation controls, quantity fields, and tab markup remain in place.

Validation: `php woocommerce/tests/catalogue-workflow.php`, `php woocommerce/tests/product-card-workflow.php`, and `node woocommerce/tests/product-card-browser.mjs --single-product`. The browser fixture uses sample content and a placeholder image; it does not connect to the live store. Set `PREVIEW_DIR` to save desktop/mobile screenshots.
