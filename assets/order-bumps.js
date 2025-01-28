document.addEventListener("DOMContentLoaded", () => {
    const orderBumpContainer = document.getElementById("order-bump-products");
    let excludedProducts = []; // Global state for excluded products

    // Log when DOMContentLoaded runs
    console.log("DOMContentLoaded: JavaScript initialized");

    /**
     * Display a loading spinner in the order bump section.
     */
    const showLoadingSpinner = () => {
        console.log("showLoadingSpinner: Displaying loading spinner");
        orderBumpContainer.innerHTML = '<p>Loading...</p>';
    };

    /**
     * Display an error message in the order bump section.
     */
    const showError = (message = "Failed to load products. Please try again.") => {
        console.log("showError: " + message);
        orderBumpContainer.innerHTML = `<p style="color: red;">${message}</p>`;
    };

    /**
     * Fetch order bump products dynamically.
     */
    const fetchOrderBumpProducts = () => {
        console.log("fetchOrderBumpProducts: Fetching order bump products");

        showLoadingSpinner();

        fetch(`${orderBumpConfig.ajaxUrl}?action=get_order_bump_products`, {
            method: "GET",
            headers: { "Content-Type": "application/json" },
        })
            .then((response) => response.json())
            .then((data) => {
                console.log("fetchOrderBumpProducts: AJAX response received", data);

                if (data.success) {
                    const filteredProducts = data.data.filter(
                        (product) => !excludedProducts.includes(product.id.toString())
                    );
                    console.log("fetchOrderBumpProducts: Filtered products", filteredProducts);
                    renderOrderBumpProducts(filteredProducts);
                } else {
                    showError("No products available for the order bump.");
                }
            })
            .catch((error) => {
                console.error("fetchOrderBumpProducts: Error fetching products", error);
                showError();
            });
    };

    /**
     * Render order bump products dynamically in the DOM.
     */
    const renderOrderBumpProducts = (products) => {
        console.log("renderOrderBumpProducts: Rendering products", products);

        if (!products.length) {
            orderBumpContainer.innerHTML = "<p>No products available for the order bump.</p>";
            return;
        }

        let productsHtml = "";
        products.forEach((product) => {
            productsHtml += `
                <div class="order-bump-product">
                    <img src="${product.image}" alt="${product.name}" />
                    <div class="order-bump-info">
                        <p>${product.name}</p>
                        <p>${product.price}</p>
                        <button type="button" class="add-to-cart" data-product-id="${product.id}">Add this</button>
                    </div>
                </div>`;
        });
        orderBumpContainer.innerHTML = productsHtml;

        console.log("renderOrderBumpProducts: Products rendered in DOM");
        attachAddToCartEvent();
    };

    /**
     * Attach event listeners to "Add to Cart" buttons.
     */
    const attachAddToCartEvent = () => {
        console.log("attachAddToCartEvent: Attaching event listeners to Add to Cart buttons");

        const addButtons = document.querySelectorAll(".add-to-cart");
        addButtons.forEach((button) => {
            button.addEventListener("click", (event) => {
                event.preventDefault();
                const productId = button.getAttribute("data-product-id");
                console.log(`attachAddToCartEvent: Add to Cart button clicked for product ID ${productId}`);
                addToCart(productId, button);
            });
        });
    };

    /**
     * Add a product to the cart.
     */
    const addToCart = (productId, button) => {
        console.log(`addToCart: Adding product ID ${productId} to cart`);

        jQuery.ajax({
            url: orderBumpConfig.ajaxUrl,
            type: "POST",
            data: {
                action: "add_product_to_cart",
                product_id: productId,
                quantity: 1,
            },
            success: (response) => {
                console.log("addToCart: AJAX response received", response);

                if (response.success) {
                    addExcludedProduct(productId);

                    // Trigger WooCommerce checkout update
                    jQuery("body").trigger("update_checkout");

                    // Remove the clicked product from the list
                    const productElement = button.closest(".order-bump-product");
                    if (productElement) {
                        productElement.remove();
                        console.log(`addToCart: Product ID ${productId} removed from DOM`);
                    }

                    // Display a success message
                    displayCustomMessage("Product successfully added to the cart!");
                } else {
                    console.error("addToCart: Error adding product to cart", response.data.message);
                    alert(response.data.message);
                }
            },
            error: (error) => {
                console.error("addToCart: AJAX error", error);
                alert("An error occurred while adding the product. Please try again.");
            },
        });
    };

    /**
     * Display a custom message above the checkout order review section.
     */
    const displayCustomMessage = (message) => {
        console.log("displayCustomMessage: Displaying custom message", message);

        const customMessage = `
            <div class="custom-checkout-message" style="padding: 10px; background: #e0ffe0; border: 1px solid #00a000; margin-top: 10px;">
                <p>${message}</p>
            </div>`;
        jQuery("#order_review").prepend(customMessage);

        setTimeout(() => {
            jQuery(".custom-checkout-message").fadeOut(300, function () {
                jQuery(this).remove();
            });
        }, 5000); // Remove message after 5 seconds
    };

    /**
     * Add a product to the excluded products list (state).
     */
    const addExcludedProduct = (productId) => {
        console.log(`addExcludedProduct: Excluding product ID ${productId}`);

        if (!excludedProducts.includes(productId)) {
            excludedProducts.push(productId);
        }
    };

    // Fetch and render order bump products on page load
    console.log("Fetching products on page load");
    fetchOrderBumpProducts();

    // Refresh order bump products whenever the cart is updated
    jQuery("body").on("updated_cart_totals update_checkout", () => {
        console.log("update_checkout: Re-fetching order bump products");
        fetchOrderBumpProducts();
    });
});