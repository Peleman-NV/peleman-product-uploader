<div class="ppu-settings">
    <h1>Peleman Product Uploader</h1>
    <hr>
    <p>This plugin uses the <a href="http://woocommerce.github.io/woocommerce-rest-api-docs/">WooCommerce REST API</a> and has the following requirements:</p>
    <ul>
        <li>WooCommerce 3.5+</li>
        <li>WordPress 4.4+</li>
        <li>Pretty permalinks in <strong>Settings > Permalinks</strong> so that the custom endpoints are supported. Default permalinks will not work.</li>
        <li>When calling this plugin via API, you need to generate Woocommerce API keys here: <strong>WooCommerce > Settings > Advanced > REST API > Add Key</strong>. The key needs Read/Write permissions.</li>
    </ul>
    <hr>
    <h2>Enter your WooCommerce API keys here</h2>
    <form method="POST" action="options.php">
        <?php
        settings_fields('ppu_custom_settings');
        do_settings_sections('ppu_custom_settings');
        ?>
        <div class="form-row">
            <div class="grid-medium-column">
                <label for="ppu-wc-key">WooCommerce key</label>
            </div>
            <div class="grid-large-column">
                <input type="text" id="ppu-wc-key" name="ppu-wc-key" value="<?= get_option('ppu-wc-key'); ?>" placeholder="WooCommerce key">
            </div>
        </div>
        <div class="form-row">
            <div class="grid-medium-column">
                <label for="ppu-wc-secret">WooCommerce secret</label>
            </div>
            <div class="grid-large-column">
                <input type="text" id="ppu-wc-secret" name="ppu-wc-secret" value="<?= get_option('ppu-wc-secret'); ?>" placeholder="WooCommerce secret">
            </div>
        </div>
        <button type="submit" class="button button-primary">Save changes</button>
    </form>
    <hr>
    <h2>Upload JSON for products, variations, attributes, or terms.</h2>
    <form action="admin-post.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_json">
        <?php wp_nonce_field('upload_json') ?>
        <div class="form-row">
            <div class="grid-medium-column">
                <label for="ppu-upload">Click to select a JSON file</label>
            </div>
            <div class="grid-large-column">
                <input id="ppu-upload" name="ppu-upload" type="file" accept="application/json">
            </div>
        </div>
        <button type="submit" class="button button-primary">Upload JSON</button>
    </form>
    <hr>
    <h2>Show orders (temp dev function)</h2>
    <form action="admin-post.php" method="POST">
        <input type="hidden" name="action" value="show_orders">
        <div class="form-row">
            <div class="grid-medium-column">
                <label for="order_id">Order ID</label>
            </div>
            <div class="grid-large-column">
                <input type="text" id="order_id" name="order_id" placeholder="Leave empty for all orders">
            </div>
        </div>
        <button type="submit" class="button button-primary">Show it</button>
    </form>
    <hr>
    <h2>Show product (temp dev function)</h2>
    <form action="admin-post.php" method="POST">
        <input type="hidden" name="action" value="show_products">
        <div class="form-row">
            <div class="grid-medium-column">
                <label for="order_id">Product ID</label>
            </div>
            <div class="grid-large-column">
                <input type="text" id="product_id" name="product_id" placeholder="Leave empty for all product">
            </div>
        </div>
        <button type="submit" class="button button-primary">Show it</button>
    </form>
</div>