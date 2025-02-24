jQuery(document).ready(function ($) {

        
    class SyncManager {
        constructor() {
            this.processed = 0;
            this.totalItems = 0;
            this.batchSize = 5;
            this.maxProducts = 0;
        }

        initializeEventListeners() {
            $('#sync-form').on('submit', (e) => {
                e.preventDefault();
                this.handleFormSubmit();
            });
        }

        handleFormSubmit() {
            this.showLoadingState();
            const formData = $('#sync-form').serializeArray();
            this.maxProducts = this.getMaxProducts(formData);
            this.startSync();
        }

        showLoadingState() {
            $('#sync-progress').show();
            $('#sync-form input[type="submit"]').prop('disabled', true);
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

                const { categories, subcategories, branches ,products } = response.data;
                this.totalItems = categories.length + subcategories.length + products.length;

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

            this.updateProgress(items.length);
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

        updateProgress(processedItems) {
            this.processed += processedItems;
            const percentage = Math.round((this.processed / this.totalItems) * 100);
            $('#sync-progress-bar').css('width', percentage + '%');
            $('#sync-status').text(`Processing... ${percentage}% (${this.processed}/${this.totalItems})`);
        }

        finishSync() {
            $('#sync-status').text('Sync completed successfully!');
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
