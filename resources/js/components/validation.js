// resources/js/components/validation.js
export default function validation() {
    return {
        // Règles de validation
        rules: {
            email: {
                pattern: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
                message: 'Veuillez saisir une adresse email valide'
            },
            phone: {
                pattern: /^[\+]?[(]?[0-9]{1,3}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{3,4}[-\s\.]?[0-9]{3,4}$/,
                message: 'Veuillez saisir un numéro de téléphone valide'
            },
            ice: {
                pattern: /^[A-Z0-9]{15}$/,
                message: 'L\'ICE doit contenir exactement 15 caractères alphanumériques majuscules'
            },
            name: {
                pattern: /^[a-zA-ZÀ-ÿ\s\-\']+$/,
                message: 'Le nom ne doit contenir que des lettres, espaces, tirets et apostrophes'
            },
            rib: {
                pattern: /^[A-Z0-9]{10,50}$/,
                message: 'Le RIB doit contenir uniquement des lettres majuscules et des chiffres (10-50 caractères)'
            }
        },
        
        // Validation en temps réel
        validate(field, value, customRules = null) {
            const rulesToApply = customRules || this.rules[field];
            if (!rulesToApply) return { valid: true, message: '' };
            
            if (!value || value.trim() === '') {
                return { valid: false, message: 'Ce champ est obligatoire' };
            }
            
            const isValid = rulesToApply.pattern.test(value);
            return {
                valid: isValid,
                message: isValid ? '' : rulesToApply.message
            };
        },
        
        // Validation de longueur
        validateLength(field, value, min, max) {
            if (!value) return { valid: false, message: 'Ce champ est obligatoire' };
            
            const length = value.length;
            if (min && length < min) {
                return { valid: false, message: `Ce champ doit contenir au moins ${min} caractères` };
            }
            if (max && length > max) {
                return { valid: false, message: `Ce champ ne doit pas dépasser ${max} caractères` };
            }
            return { valid: true, message: '' };
        },
        
        // Validation de nombre
        validateNumber(field, value, min, max) {
            const num = parseFloat(value);
            if (isNaN(num)) {
                return { valid: false, message: 'Veuillez saisir un nombre valide' };
            }
            if (min !== undefined && num < min) {
                return { valid: false, message: `La valeur doit être supérieure ou égale à ${min}` };
            }
            if (max !== undefined && num > max) {
                return { valid: false, message: `La valeur doit être inférieure ou égale à ${max}` };
            }
            return { valid: true, message: '' };
        },
        
        // Validation de date
        validateDate(field, value, compareTo = null, operator = 'gte') {
            if (!value) return { valid: false, message: 'La date est obligatoire' };
            
            const date = new Date(value);
            if (isNaN(date.getTime())) {
                return { valid: false, message: 'Veuillez saisir une date valide' };
            }
            
            if (compareTo) {
                const compareDate = new Date(compareTo);
                if (operator === 'gte' && date < compareDate) {
                    return { valid: false, message: `La date doit être postérieure ou égale à ${compareTo}` };
                }
                if (operator === 'lte' && date > compareDate) {
                    return { valid: false, message: `La date doit être antérieure ou égale à ${compareTo}` };
                }
            }
            
            return { valid: true, message: '' };
        }
    };
}