// resources/js/app.js
import Alpine from 'alpinejs';
import mask from '@alpinejs/mask';
import intersect from '@alpinejs/intersect';
import focus from '@alpinejs/focus';

// Enregistrer les plugins Alpine
Alpine.plugin(mask);
Alpine.plugin(intersect);
Alpine.plugin(focus);

// Composant global de validation
Alpine.data('validation', () => ({
    errors: {},
    
    validateField(field, value, rules) {
        let error = null;
        
        // Règle required
        if (rules.required && (!value || value === '')) {
            error = 'Ce champ est obligatoire';
        }
        
        // Règle email
        if (!error && rules.email && value) {
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailRegex.test(value)) {
                error = 'Veuillez saisir une adresse email valide';
            }
        }
        
        // Règle min
        if (!error && rules.min && value && value.length < rules.min) {
            error = `Ce champ doit contenir au moins ${rules.min} caractères`;
        }
        
        // Règle max
        if (!error && rules.max && value && value.length > rules.max) {
            error = `Ce champ ne doit pas dépasser ${rules.max} caractères`;
        }
        
        // Règle pattern
        if (!error && rules.pattern && value && !rules.pattern.test(value)) {
            error = rules.message || 'Format invalide';
        }
        
        if (error) {
            this.errors[field] = error;
        } else {
            delete this.errors[field];
        }
        
        return !error;
    },
    
    hasErrors() {
        return Object.keys(this.errors).length > 0;
    },
    
    clearErrors() {
        this.errors = {};
    }
}));

// Composant de notification global
Alpine.data('notify', () => ({
    show: false,
    type: 'info',
    message: '',
    timeout: null,
    
    success(message, duration = 3000) {
        this.showMessage('success', message, duration);
    },
    
    error(message, duration = 5000) {
        this.showMessage('error', message, duration);
    },
    
    warning(message, duration = 4000) {
        this.showMessage('warning', message, duration);
    },
    
    info(message, duration = 3000) {
        this.showMessage('info', message, duration);
    },
    
    showMessage(type, message, duration) {
        this.type = type;
        this.message = message;
        this.show = true;
        
        if (this.timeout) clearTimeout(this.timeout);
        this.timeout = setTimeout(() => {
            this.show = false;
        }, duration);
    }
}));

// Composant de chargement
Alpine.data('loading', () => ({
    loading: false,
    
    start() {
        this.loading = true;
    },
    
    stop() {
        this.loading = false;
    },
    
    withLoading(callback) {
        this.start();
        try {
            callback();
        } finally {
            this.stop();
        }
    },
    
    async withLoadingAsync(callback) {
        this.start();
        try {
            await callback();
        } finally {
            this.stop();
        }
    }
}));

// Initialiser Alpine
window.Alpine = Alpine;
Alpine.start();

// Configuration CSRF pour les requêtes AJAX
window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;