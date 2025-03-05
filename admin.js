jQuery(document).ready(function ($) {

    let responseData;

        
    class SyncManager {
        constructor() {
            this.processed = 0;
            this.totalItems = 0;

            this.processedCategories = 0;
            this.totalCategories = 0;
            this.processedSubcategories = 0;
            this.totalSubcategories = 0;
            this.processedBranches = 0;
            this.totalBranches = 0;
            this.processedProducts = 0;
            this.totalProducts = 0;

            this.batchSize = 5;
            this.maxProducts = 0;
        }

        initializeEventListeners() {
            $('#sync-form').on('submit', (e) => {
                e.preventDefault();
                this.handleFormSubmit();
            });

            $('#sync-one-product').on('submit', (e) => {
                e.preventDefault();
                this.handleSingleItemFormSubmit();
            });
        }

        async handleSingleItemFormSubmit() { 

            $('#sync-one-product_status').show();

            try {


                const formData = $('#sync-one-product').serializeArray();
                const product_id_field = formData.find(field => field.name === 'product_id');
                const product_id = product_id_field.value;

                $('#sync-one-product_status').html('searching for ' + product_id);

                if (!product_id) return;

                const response = await this.fetchInitialData();
                if (!response.success) {
                    $('#sync-one-product_status').html('Failed to fetch initial data');
                    throw new Error('Failed to fetch initial data');
                }

                const { categories, subcategories, branches, products } = response.data;

                console.log('got products', products);

                const productData = this.findInProducts(products, product_id);

                $('#sync-one-product_status').html("syncing one product, <pre>" + productData + "</pre>");
                await this.syncItems('products', [productData]);
                $('#sync-one-product_status').html("syncing one product successfuly! <pre>" + productData + "</pre>");
            } catch (error) {
                this.handleError('Sync failed: ' + error.message);
            }
        }

        findInProducts(products, product_id) { 
            for(const prod of products) {
                console.log(prod);
                if (prod.id == product_id) { 
                    console.log('match');
                    return prod;
                }
            }
        }

        handleFormSubmit() {
            this.showLoadingState('#sync-form');
            const formData = $('#sync-form').serializeArray();
            this.maxProducts = this.getMaxProducts(formData);
            this.batchSize = this.getBatchSize(formData);
            this.startSync();
        }

        showLoadingState(formId) {
            $('#sync-progress').show();
            $(formId+' input[type="submit"]').prop('disabled', true);
        }

        getBatchSize(formData) { 
            const batchSizeField = formData.find(field => field.name === 'batch_size');
            return batchSizeField ? parseInt(batchSizeField.value) : 5;
        }

        getMaxProducts(formData) {
            const maxProductsField = formData.find(field => field.name === 'max_sync_products');
            return maxProductsField ? parseInt(maxProductsField.value) : Infinity;
        }

        async startSync() {
            try {
                const response = await this.fetchInitialData();
                if (!response.success) {
                    throw new Error('Failed to fetch initial data');
                }

                const { categories, subcategories, branches, products } = response.data;
                this.totalItems = categories.length + subcategories.length + branches.length + products.length;
                this.totalBranches = branches.length;
                this.totalCategories = categories.length;
                this.totalSubcategories = subcategories.length;
                this.totalProducts = products.length;

                await this.syncInOrder(categories, subcategories, branches, products);
                this.finishSync();
            } catch (error) {
                this.handleError('Sync failed: ' + error.message);
            }
        }

        async fetchInitialData() {
            return $.ajax({
                url: clickeatAdmin.ajaxurl,
                method: 'POST',
                data: {
                    action: 'start_sync',
                    nonce: clickeatAdmin.nonce
                }
            });
        }

        async syncInOrder(categories, subcategories, branches, products) {
            await this.syncItems('categories', categories);
            await this.syncItems('subcategories', subcategories);
            await this.syncItems('branches', branches);
            await this.syncProductsInBatches(products);
        }

        async syncItems(type, items) {
            if (!items.length) return;

            await $.ajax({
                url: clickeatAdmin.ajaxurl,
                method: 'POST',
                data: {
                    action: 'process_items',
                    type: type,
                    items: items,
                    nonce: clickeatAdmin.nonce
                }
            });

            this.updateProgress(items.length, type);
        }

        async syncProductsInBatches(products) {
            const batches = this.createBatches(products);
            
            for (let i = 0; i < batches.length; i++) {
                if (this.batchSize * i >= this.maxProducts) break;
                
                await this.syncItems('products', batches[i]);
            }
        }

        createBatches(items) {
            const batches = [];
            for (let i = 0; i < items.length; i += this.batchSize) {
                batches.push(items.slice(i, i + this.batchSize));
            }
            return batches;
        }

        updateProgress(processedItems, type = '') {
            this.processed += processedItems;

            if (type === 'categories') {
                this.processedCategories += processedItems;
            } else if (type === 'subcategories') {
                this.processedSubcategories += processedItems;
            } else if (type === 'branches') {
                this.processedBranches += processedItems;
            } else if (type === 'products') {
                this.processedProducts += processedItems;
            }

            const percentage = Math.round((this.processed / this.totalItems) * 100);
            $('#sync-progress-bar').css('width', percentage + '%');
            $('#sync-status').html(`Processing... ${percentage}% (${this.processed}/${this.totalItems})` +
                `<br/>(${this.processedCategories}/${this.totalCategories}) categories, ` +
                `<br/>(${this.processedSubcategories}/${this.totalSubcategories}) subcategories, ` +
                `<br/>(${this.processedBranches}/${this.totalBranches}) branches, ` +
                `<br/>(${this.processedProducts}/${this.totalProducts}) products`
            );
        }

        finishSync() {
            $('#sync-status').html($('#sync-status').html() + '<br/>Sync completed successfully!');
            $('#sync-form input[type="submit"]').prop('disabled', false);
        }

        handleError(message) {
            $('#sync-status').text(message);
            $('#sync-form input[type="submit"]').prop('disabled', false);
        }
    }

    // Initialize the sync manager
    const syncManager = new SyncManager();
    syncManager.initializeEventListeners();
});
