/**
 * COSMIC SURGICALS - Invoice Management System
 * Main JavaScript
 */

// Utility functions
const App = {
    // Format currency
    formatCurrency: function(amount) {
        return parseFloat(amount || 0).toFixed(2);
    },

    // Format number with commas (Indian format)
    formatIndian: function(num) {
        const x = num.toString().split('.');
        let lastThree = x[0].substring(x[0].length - 3);
        const otherNumbers = x[0].substring(0, x[0].length - 3);
        if (otherNumbers !== '') {
            lastThree = ',' + lastThree;
        }
        const result = otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + lastThree;
        return x.length > 1 ? result + '.' + x[1] : result;
    },

    // Show alert
    showAlert: function(message, type = 'success') {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;

        const container = document.querySelector('.main-content');
        container.insertBefore(alert, container.firstChild);

        setTimeout(() => alert.remove(), 5000);
    },

    // Confirm dialog
    confirm: function(message, callback) {
        if (confirm(message)) {
            callback();
        }
    },

    // AJAX helper
    ajax: function(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const config = { ...defaults, ...options };

        return fetch(url, config)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            });
    }
};

// Autocomplete functionality
class Autocomplete {
    constructor(input, options = {}) {
        this.input = input;
        this.options = {
            minLength: 2,
            delay: 300,
            url: options.url,
            onSelect: options.onSelect || function() {},
            renderItem: options.renderItem || this.defaultRenderItem
        };

        this.results = null;
        this.timeout = null;
        this.init();
    }

    init() {
        // Create results container
        this.results = document.createElement('div');
        this.results.className = 'autocomplete-results';
        this.input.parentNode.classList.add('autocomplete-container');
        this.input.parentNode.appendChild(this.results);

        // Event listeners
        this.input.addEventListener('input', () => this.onInput());
        this.input.addEventListener('focus', () => this.onFocus());
        this.input.addEventListener('blur', () => setTimeout(() => this.hide(), 200));
        this.input.addEventListener('keydown', (e) => this.onKeydown(e));
    }

    onInput() {
        clearTimeout(this.timeout);
        const value = this.input.value.trim();

        if (value.length < this.options.minLength) {
            this.hide();
            return;
        }

        this.timeout = setTimeout(() => this.search(value), this.options.delay);
    }

    onFocus() {
        if (this.results.children.length > 0) {
            this.show();
        }
    }

    onKeydown(e) {
        const items = this.results.querySelectorAll('.autocomplete-item');
        const current = this.results.querySelector('.autocomplete-item.selected');

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (current) {
                current.classList.remove('selected');
                const next = current.nextElementSibling || items[0];
                next.classList.add('selected');
            } else if (items.length > 0) {
                items[0].classList.add('selected');
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (current) {
                current.classList.remove('selected');
                const prev = current.previousElementSibling || items[items.length - 1];
                prev.classList.add('selected');
            }
        } else if (e.key === 'Enter') {
            if (current) {
                e.preventDefault();
                current.click();
            }
        } else if (e.key === 'Escape') {
            this.hide();
        }
    }

    search(query) {
        const url = this.options.url + '?q=' + encodeURIComponent(query);

        fetch(url)
            .then(response => response.json())
            .then(data => this.render(data))
            .catch(error => console.error('Autocomplete error:', error));
    }

    render(items) {
        this.results.innerHTML = '';

        if (items.length === 0) {
            this.hide();
            return;
        }

        items.forEach(item => {
            const div = document.createElement('div');
            div.className = 'autocomplete-item';
            div.innerHTML = this.options.renderItem(item);
            div.addEventListener('click', () => {
                this.options.onSelect(item);
                this.hide();
            });
            this.results.appendChild(div);
        });

        this.show();
    }

    defaultRenderItem(item) {
        return `<div class="primary">${item.name || item.label}</div>`;
    }

    show() {
        this.results.classList.add('show');
    }

    hide() {
        this.results.classList.remove('show');
    }
}

// Modal functionality
class Modal {
    constructor(id) {
        this.overlay = document.getElementById(id);
        if (this.overlay) {
            this.overlay.querySelector('.modal-close')?.addEventListener('click', () => this.close());
            this.overlay.addEventListener('click', (e) => {
                if (e.target === this.overlay) this.close();
            });
        }
    }

    open() {
        this.overlay?.classList.add('show');
    }

    close() {
        this.overlay?.classList.remove('show');
    }
}

// Number to words (Indian format)
function numberToWords(num) {
    const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen'];
    const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    function convert(n) {
        if (n < 20) return ones[n];
        if (n < 100) return tens[Math.floor(n / 10)] + (n % 10 ? ' ' + ones[n % 10] : '');
        if (n < 1000) return ones[Math.floor(n / 100)] + ' Hundred' + (n % 100 ? ' ' + convert(n % 100) : '');
        if (n < 100000) return convert(Math.floor(n / 1000)) + ' Thousand' + (n % 1000 ? ' ' + convert(n % 1000) : '');
        if (n < 10000000) return convert(Math.floor(n / 100000)) + ' Lakh' + (n % 100000 ? ' ' + convert(n % 100000) : '');
        return convert(Math.floor(n / 10000000)) + ' Crore' + (n % 10000000 ? ' ' + convert(n % 10000000) : '');
    }

    num = Math.round(num * 100) / 100;
    const rupees = Math.floor(num);
    const paise = Math.round((num - rupees) * 100);

    let result = 'Rupees ' + (rupees === 0 ? 'Zero' : convert(rupees));
    if (paise > 0) {
        result += ' and ' + convert(paise) + ' Paise';
    }
    return result + ' Only';
}

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});
