// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    const inputs = document.querySelectorAll('.form-control-custom');

    // Form submission with loading state
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            // Basic form validation
            const email = document.getElementById('inputEmail').value;
            const password = document.getElementById('inputPassword').value;

            if (!email || !password) {
                e.preventDefault();
                
                // Create custom alert
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-custom alert-danger-custom';
                alertDiv.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div>
                            <strong>Champs requis</strong><br>
                            <small>Veuillez remplir tous les champs obligatoires</small>
                        </div>
                    </div>
                `;
                
                loginForm.insertBefore(alertDiv, loginForm.firstChild);
                
                // Auto-remove after 3 seconds
                setTimeout(() => {
                    alertDiv.style.transition = 'opacity 0.5s ease';
                    alertDiv.style.opacity = '0';
                    setTimeout(() => alertDiv.remove(), 500);
                }, 3000);

                // Focus first empty field
                if (!email) {
                    document.getElementById('inputEmail').focus();
                } else if (!password) {
                    document.getElementById('inputPassword').focus();
                }
                return;
            }

            // Add loading state
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Connexion en cours...';
            submitBtn.disabled = true;
        });
    }

    // Input focus effects
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });

        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });

        // Check initial state
        if (input.value) {
            input.parentElement.classList.add('focused');
        }

        // Real-time validation
        input.addEventListener('input', function() {
            // Remove error state on input
            this.classList.remove('error');
            
            // Remove any existing error messages
            const existingAlert = this.closest('form').querySelector('.alert-custom');
            if (existingAlert) {
                existingAlert.remove();
            }
        });
    });

    // Password visibility toggle
    const passwordInput = document.getElementById('inputPassword');
    if (passwordInput) {
        // Create toggle button
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'btn btn-link position-absolute';
        toggleBtn.style.cssText = 'right: 10px; top: 50%; transform: translateY(-50%); z-index: 4; color: var(--primary-color);';
        toggleBtn.innerHTML = '<i class="bi bi-eye"></i>';
        toggleBtn.setAttribute('aria-label', 'Afficher/masquer le mot de passe');
        
        passwordInput.parentElement.appendChild(toggleBtn);

        toggleBtn.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        });
    }

    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-custom');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        // Ctrl+Enter to submit form
        if (e.key === 'Enter' && e.ctrlKey && loginForm) {
            e.preventDefault();
            loginForm.dispatchEvent(new Event('submit'));
        }
        
        // Escape to clear form
        if (e.key === 'Escape' && loginForm) {
            loginForm.reset();
            inputs.forEach(input => {
                input.parentElement.classList.remove('focused');
                input.classList.remove('error');
            });
        }
    });

    // Add hover effects to buttons
    const buttons = document.querySelectorAll('.btn-submit, .link-custom');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Remember me functionality
    const rememberMeCheckbox = document.getElementById('rememberMe');
    const emailInput = document.getElementById('inputEmail');
    
    if (rememberMeCheckbox && emailInput) {
        // Load saved email if remember me was checked
        const savedEmail = localStorage.getItem('rememberedEmail');
        const wasRemembered = localStorage.getItem('rememberMe') === 'true';
        
        if (savedEmail && wasRemembered) {
            emailInput.value = savedEmail;
            rememberMeCheckbox.checked = true;
            emailInput.parentElement.classList.add('focused');
        }

        // Save email preference on form submission
        if (loginForm) {
            loginForm.addEventListener('submit', function() {
                if (rememberMeCheckbox.checked) {
                    localStorage.setItem('rememberedEmail', emailInput.value);
                    localStorage.setItem('rememberMe', 'true');
                } else {
                    localStorage.removeItem('rememberedEmail');
                    localStorage.removeItem('rememberMe');
                }
            });
        }
    }

    // Add loading animation to page
    window.addEventListener('load', function() {
        document.body.classList.add('loaded');
    });

    // Form field animations on focus
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });

    // Add shake animation to form on validation error
    function shakeForm() {
        if (loginForm) {
            loginForm.style.animation = 'shake 0.5s ease-in-out';
            setTimeout(() => {
                loginForm.style.animation = '';
            }, 500);
        }
    }

    // Enhanced error handling
    window.addEventListener('error', function(e) {
        console.error('Login page error:', e.error);
    });

    // Performance optimization: Debounce input events
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Apply debounce to input validation
    inputs.forEach(input => {
        input.addEventListener('input', debounce(function() {
            // Real-time validation logic here
            validateField(this);
        }, 300));
    });

    // Field validation function
    function validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';

        if (field.type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            isValid = emailRegex.test(value);
            errorMessage = 'Veuillez entrer une adresse email valide';
        } else if (field.type === 'password') {
            isValid = value.length >= 6;
            errorMessage = 'Le mot de passe doit contenir au moins 6 caractères';
        }

        if (!isValid && value.length > 0) {
            field.classList.add('error');
            showFieldError(field, errorMessage);
        } else {
            field.classList.remove('error');
            hideFieldError(field);
        }
    }

    // Show field error
    function showFieldError(field, message) {
        let errorDiv = field.parentElement.querySelector('.field-error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'field-error';
            errorDiv.style.cssText = `
                color: var(--error-color);
                font-size: 0.8rem;
                margin-top: 5px;
                display: block;
            `;
            field.parentElement.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
    }

    // Hide field error
    function hideFieldError(field) {
        const errorDiv = field.parentElement.querySelector('.field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    // Console log for debugging (remove in production)
    console.log('Police Routière Guinée - Login page initialized successfully');
});
