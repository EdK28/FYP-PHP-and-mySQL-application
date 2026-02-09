function showAuthPopup() {
    const backdrop = document.getElementById('authPopupBackdrop');
    if (backdrop) {
        backdrop.style.display = 'flex'; // Show popup
    }
}

const closeBtn = document.getElementById('closePopup');
if (closeBtn) {
    closeBtn.addEventListener('click', () => {
        document.getElementById('authPopupBackdrop').style.display = 'none';
    });
}

// Optional: click outside to close
const backdrop = document.getElementById('authPopupBackdrop');
if (backdrop) {
    backdrop.addEventListener('click', (e) => {
        if (e.target === backdrop) {
            backdrop.style.display = 'none';
        }
    });
}

// =========================
// CART HANDLER LOGIC (new)
// =========================
function addToCart(productId) {
    fetch('customer/cart_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId, quantity: 1 })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 1) {
            alert(data.message); // optional, or you can show a toast
            const cartCountSpan = document.getElementById('cart-count');
            if (cartCountSpan && data.cart_count !== undefined) {
                cartCountSpan.textContent = data.cart_count;
            }
        } else {
            alert(data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error adding to cart.');
    });
}

// Redirect to orders page when View Orders is clicked
const viewOrdersBtn = document.getElementById('view-orders-btn');
if (viewOrdersBtn) {
    viewOrdersBtn.addEventListener('click', () => {
        window.location.href = 'customer/orders.php';
    });
}

// Fetch cart count on page load (if you store it in session)
fetch('customer/cart_count.php')
    .then(res => res.json())
    .then(data => {
        const cartCountSpan = document.getElementById('cart-count');
        if (cartCountSpan) cartCountSpan.textContent = data.count || 0;
    })
    .catch(err => console.error(err));

    // --- Add to Cart via AJAX ---
function addToCart(productId, quantity = 1) {
    fetch('customer/add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId, quantity: quantity })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message); // optional feedback
        // Update cart count
        const cartCountSpan = document.getElementById('cart-count');
        if (cartCountSpan && data.status === 1) {
            let current = parseInt(cartCountSpan.textContent) || 0;
            cartCountSpan.textContent = current + quantity;
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error adding to cart.');
    });
}

// Optional: load cart count on page load
window.addEventListener('DOMContentLoaded', () => {
    fetch('customer/cart_count.php')
        .then(res => res.json())
        .then(data => {
            const cartCountSpan = document.getElementById('cart-count');
            if (cartCountSpan) cartCountSpan.textContent = data.count || 0;
        });
});
