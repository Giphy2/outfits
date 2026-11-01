/**
 * Outfit Store - Client Cart & UI Interactions
 */

const Cart = {
    KEY: 'outfit_cart',

    getItems() {
        try {
            return JSON.parse(localStorage.getItem(this.KEY)) || [];
        } catch (e) {
            return [];
        }
    },

    saveItems(items) {
        localStorage.setItem(this.KEY, JSON.stringify(items));
        this.updateBadge();
    },

    addItem(product) {
        const items = this.getItems();
        const existing = items.find(i => i.id == product.id);
        if (existing) {
            existing.quantity += (product.quantity || 1);
        } else {
            items.push({
                id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                image_url: product.image_url,
                quantity: product.quantity || 1
            });
        }
        this.saveItems(items);
        showToast(`Added "${product.name}" to cart!`, 'success');
    },

    updateQuantity(productId, qty) {
        let items = this.getItems();
        qty = parseInt(qty);
        if (qty <= 0) {
            this.removeItem(productId);
            return;
        }
        const item = items.find(i => i.id == productId);
        if (item) {
            item.quantity = qty;
            this.saveItems(items);
        }
    },

    removeItem(productId) {
        let items = this.getItems();
        items = items.filter(i => i.id != productId);
        this.saveItems(items);
        showToast('Item removed from cart', 'info');
    },

    clear() {
        localStorage.removeItem(this.KEY);
        this.updateBadge();
    },

    getTotalCount() {
        return this.getItems().reduce((total, item) => total + item.quantity, 0);
    },

    getTotalPrice() {
        return this.getItems().reduce((total, item) => total + (item.price * item.quantity), 0);
    },

    updateBadge() {
        const badges = document.querySelectorAll('.cart-count');
        const count = this.getTotalCount();
        badges.forEach(b => {
            b.textContent = count;
            b.style.display = count > 0 ? 'inline-flex' : 'none';
        });
    }
};

// Toast notification helper
function showToast(message, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.position = 'fixed';
        container.style.bottom = '24px';
        container.style.right = '24px';
        container.style.zIndex = '9999';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '10px';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.style.background = '#1e293b';
    toast.style.color = '#fff';
    toast.style.border = '1px solid #3b82f6';
    toast.style.padding = '12px 20px';
    toast.style.borderRadius = '8px';
    toast.style.boxShadow = '0 10px 25px rgba(0,0,0,0.5)';
    toast.style.fontSize = '0.9rem';
    toast.style.display = 'flex';
    toast.style.alignItems = 'center';
    toast.style.gap = '10px';
    toast.style.transition = 'all 0.3s ease';

    if (type === 'success') {
        toast.style.borderColor = '#10b981';
        toast.innerHTML = `<span>✓</span> <span>${message}</span>`;
    } else {
        toast.innerHTML = `<span>ℹ</span> <span>${message}</span>`;
    }

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => toast.remove(), 300);
    }, 2800);
}

// Copy to clipboard helper
function copyToClipboard(text, btnElement) {
    navigator.clipboard.writeText(text).then(() => {
        const originalText = btnElement.innerText;
        btnElement.innerText = 'Copied!';
        btnElement.style.background = '#10b981';
        btnElement.style.color = '#fff';
        setTimeout(() => {
            btnElement.innerText = originalText;
            btnElement.style.background = '';
            btnElement.style.color = '';
        }, 2000);
    }).catch(err => {
        console.error('Copy failed:', err);
    });
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    Cart.updateBadge();
});

