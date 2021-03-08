<div class="ppu-settings">
    <h1>Peleman Product Uploader</h1>
    <hr>
    <p>This plugin uses the <a href="http://woocommerce.github.io/woocommerce-rest-api-docs/">WooCommerce REST API</a> and has the following requirements:</p>
    <ul>
        <li>WooCommerce 3.5+</li>
        <li>WordPress 4.4+</li>
        <li>Composer</li>
        <li>Pretty permalinks in Settings > Permalinks so that the custom endpoints are supported. Default permalinks will not work.</li>
    </ul>
    <hr>
    <h2>Upload a JSON file</h2>
    <form action="admin-post.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_products_json">
        <div class="grid-medium-column">
            <label for="ppu-upload">Click to select a products file</label>
        </div>
        <div class="grid-large-column">
            <input id="ppu-upload" name="ppu-upload" type="file" accept="application/json">
        </div>
        <button type="submit" class="button button-primary">Upload products</button>
    </form>
</div>