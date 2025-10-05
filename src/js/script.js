// DOM Elements
const modeToggle = document.getElementById('modeToggle');
const authBtn = document.getElementById('authBtn');
const authModal = document.getElementById('authModal');
const closeModal = document.getElementById('closeModal');
const loginToggle = document.getElementById('loginToggle');
const registerToggle = document.getElementById('registerToggle');
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const hamburger = document.querySelector('.hamburger');
const navMenu = document.querySelector('.nav-menu');

// Modal Elements
const confirmationModal = document.getElementById('confirmationModal');
const successModal = document.getElementById('successModal');
const errorModal = document.getElementById('errorModal');
const confirmationMessage = document.getElementById('confirmationMessage');
const successMessage = document.getElementById('successMessage');
const errorMessage = document.getElementById('errorMessage');
const cancelBtn = document.getElementById('cancelBtn');
const confirmBtn = document.getElementById('confirmBtn');
const okSuccessBtn = document.getElementById('okSuccessBtn');
const okErrorBtn = document.getElementById('okErrorBtn');

// Mode Toggle
function setTheme(themeName) {
    localStorage.setItem('theme', themeName);
    document.documentElement.setAttribute('data-theme', themeName);
}

function toggleTheme() {
    if (localStorage.getItem('theme') === 'dark') {
        setTheme('light');
        modeToggle.innerHTML = '<i class="fas fa-moon"></i>';
    } else {
        setTheme('dark');
        modeToggle.innerHTML = '<i class="fas fa-sun"></i>';
    }
}

// Set initial theme based on system preference or stored preference
let currentTheme = localStorage.getItem('theme');
if (currentTheme === 'dark') {
    setTheme('dark');
    modeToggle.innerHTML = '<i class="fas fa-sun"></i>';
} else if (currentTheme === 'light') {
    setTheme('light');
    modeToggle.innerHTML = '<i class="fas fa-moon"></i>';
} else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    setTheme('dark');
    modeToggle.innerHTML = '<i class="fas fa-sun"></i>';
} else {
    setTheme('light');
    modeToggle.innerHTML = '<i class="fas fa-moon"></i>';
}

// Event Listeners
modeToggle.addEventListener('click', toggleTheme);

// Authentication Modal handling will be done through the initializeAuthButton function

closeModal.addEventListener('click', () => {
    authModal.classList.remove('active');
    document.body.style.overflow = 'auto';
});

// Close modal when clicking outside
window.addEventListener('click', (e) => {
    if (e.target === authModal) {
        authModal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
});

// Modal functions
function showConfirmation(message, onConfirm) {
    confirmationMessage.textContent = message;
    confirmationModal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    const handleConfirm = () => {
        confirmationModal.classList.remove('active');
        document.body.style.overflow = 'auto';
        confirmBtn.removeEventListener('click', handleConfirm);
        cancelBtn.removeEventListener('click', handleClose);
        if (onConfirm) onConfirm();
    };
    
    const handleClose = () => {
        confirmationModal.classList.remove('active');
        document.body.style.overflow = 'auto';
        confirmBtn.removeEventListener('click', handleConfirm);
        cancelBtn.removeEventListener('click', handleClose);
    };
    
    confirmBtn.addEventListener('click', handleConfirm);
    cancelBtn.addEventListener('click', handleClose);
}

function showSuccess(message, onClose) {
    successMessage.textContent = message;
    successModal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    const handleClose = () => {
        successModal.classList.remove('active');
        document.body.style.overflow = 'auto';
        okSuccessBtn.removeEventListener('click', handleClose);
        if (onClose) onClose();
    };
    
    okSuccessBtn.addEventListener('click', handleClose);
}

function showError(message, onClose) {
    errorMessage.textContent = message;
    errorModal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    const handleClose = () => {
        errorModal.classList.remove('active');
        document.body.style.overflow = 'auto';
        okErrorBtn.removeEventListener('click', handleClose);
        if (onClose) onClose();
    };
    
    okErrorBtn.addEventListener('click', handleClose);
}

// Close modals when clicking outside
window.addEventListener('click', (e) => {
    if (e.target === confirmationModal) {
        confirmationModal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
    if (e.target === successModal) {
        successModal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
    if (e.target === errorModal) {
        errorModal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
});

// Toggle between login and register forms
loginToggle.addEventListener('click', () => {
    loginToggle.classList.add('active');
    registerToggle.classList.remove('active');
    loginForm.style.display = 'block';
    registerForm.style.display = 'none';
});

registerToggle.addEventListener('click', () => {
    registerToggle.classList.add('active');
    loginToggle.classList.remove('active');
    loginForm.style.display = 'none';
    registerForm.style.display = 'block';
});

// Initialize user state from localStorage
let currentUser = JSON.parse(localStorage.getItem('currentUser')) || null;

// Check user state on page load
document.addEventListener('DOMContentLoaded', () => {
    if (currentUser) {
        updateUIAfterLogin(currentUser);
    }
});

loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    
    try {
        const response = await fetch('api/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'login',
                email: email,
                password: password
            })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            showSuccess('Login berhasil! Selamat datang kembali.', () => {
                authModal.classList.remove('active');
                document.body.style.overflow = 'auto';
                
                // Store user info in localStorage and update UI
                currentUser = data.user;
                localStorage.setItem('currentUser', JSON.stringify(data.user));
                // If server provides a token, store it too
                if (data.token) {
                    localStorage.setItem('authToken', data.token);
                }
                updateUIAfterLogin(data.user);
            });
        } else {
            showError(data.message || 'Login gagal. Silakan coba lagi.');
        }
    } catch (error) {
        showError('Terjadi kesalahan. Silakan coba lagi nanti.');
        console.error('Login error:', error);
    }
});

registerForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const name = document.getElementById('registerName').value;
    const email = document.getElementById('registerEmail').value;
    const password = document.getElementById('registerPassword').value;
    const confirmPassword = document.getElementById('registerConfirmPassword').value;
    
    if (password !== confirmPassword) {
        alert('Password dan konfirmasi password tidak cocok');
        return;
    }
    
    try {
        const response = await fetch('api/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'register',
                name: name,
                email: email,
                password: password
            })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            showSuccess('Registrasi berhasil! Silakan login.', () => {
                // Switch to login form after successful registration
                registerToggle.classList.remove('active');
                loginToggle.classList.add('active');
                registerForm.style.display = 'none';
                loginForm.style.display = 'block';
            });
        } else {
            showError(data.message || 'Registrasi gagal. Silakan coba lagi.');
        }
    } catch (error) {
        showError('Terjadi kesalahan. Silakan coba lagi nanti.');
        console.error('Registration error:', error);
    }
});

// Update UI after login
function updateUIAfterLogin(user) {
    // Change auth button to show user initial
    const authBtn = document.getElementById('authBtn');
    const initial = user.name.charAt(0).toUpperCase();
    authBtn.innerHTML = `<span class="user-initial">${initial}</span>`;
    authBtn.classList.add('user-profile-btn'); // Add special class for logged-in state
    authBtn.onclick = function(e) {
        e.preventDefault();
        showUserMenu();
    };
    
    // Ensure currentUser is properly set with all fields
    currentUser = user;
}

// Initialize auth button state based on stored user
function initializeAuthButton() {
    const authBtn = document.getElementById('authBtn');
    const savedUser = localStorage.getItem('currentUser');
    
    if (savedUser) {
        const user = JSON.parse(savedUser);
        currentUser = user;
        authBtn.textContent = user.name;
        authBtn.onclick = function(e) {
            e.preventDefault();
            showUserMenu();
        };
    } else {
        authBtn.textContent = 'Sign In';
        authBtn.onclick = function(e) {
            e.preventDefault();
            document.getElementById('authModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        };
    }
}

// Check user state on page load
document.addEventListener('DOMContentLoaded', () => {
    initializeAuthButton();
});

// Show user menu
function showUserMenu() {
    const menu = document.createElement('div');
    menu.className = 'user-menu';
    menu.innerHTML = `
        <div class="user-menu-content">
            <div class="user-info">
                <h4>Selamat Datang, ${currentUser.name}</h4>
                <p>${currentUser.email}</p>
            </div>
            <button id="editProfile" class="btn-secondary">Edit Profile</button>
            <button id="viewVouchers" class="btn-secondary">Lihat Voucher Saya</button>
            <button id="logoutBtn" class="btn-secondary">Logout</button>
        </div>
    `;
    
    document.body.appendChild(menu);
    
    // Position the menu near the auth button (accounting for scroll position)
    const authBtn = document.getElementById('authBtn');
    const rect = authBtn.getBoundingClientRect();
    // Calculate position relative to viewport and add scroll offset
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
    
    menu.style.position = 'absolute';
    menu.style.top = (rect.bottom + scrollTop) + 'px';
    menu.style.right = (window.innerWidth - rect.right + scrollLeft) + 'px';
    menu.style.zIndex = '3000';
    
    // Add event listeners to menu items
    document.getElementById('logoutBtn').addEventListener('click', () => {
        document.body.removeChild(menu);
        logout();
    });
    
    document.getElementById('viewVouchers').addEventListener('click', () => {
        document.body.removeChild(menu);
        viewUserVouchers();
    });
    
    document.getElementById('editProfile').addEventListener('click', () => {
        document.body.removeChild(menu);
        showEditProfileModal();
    });
    
    // Close menu when clicking outside
    const closeMenu = (e) => {
        if (!menu.contains(e.target) && !authBtn.contains(e.target)) {
            document.body.removeChild(menu);
            document.removeEventListener('click', closeMenu);
        }
    };
    document.addEventListener('click', closeMenu);
    
    // Update position when scrolling
    const updateMenuPosition = () => {
        if (document.body.contains(menu)) { // Check if menu still exists
            const updatedRect = authBtn.getBoundingClientRect();
            const updatedScrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const updatedScrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
            
            menu.style.top = (updatedRect.bottom + updatedScrollTop) + 'px';
            menu.style.right = (window.innerWidth - updatedRect.right + updatedScrollLeft) + 'px';
        } else {
            // If menu is removed, stop listening
            document.removeEventListener('scroll', updateMenuPosition);
        }
    };
    
    // Add scroll event listener to update position
    document.addEventListener('scroll', updateMenuPosition);
}

// Logout function
function logout() {
    showConfirmation('Apakah Anda yakin ingin logout?', () => {
        currentUser = null;
        localStorage.removeItem('currentUser');
        localStorage.removeItem('authToken');
        initializeAuthButton();
        showSuccess('Anda telah logout.');
    });
}

// Initialize auth button state based on stored user
function initializeAuthButton() {
    const authBtn = document.getElementById('authBtn');
    const savedUser = localStorage.getItem('currentUser');
    
    if (savedUser) {
        try {
            const user = JSON.parse(savedUser);
            currentUser = user;
            const initial = user.name.charAt(0).toUpperCase();
            authBtn.innerHTML = `<span class="user-initial">${initial}</span>`;
            authBtn.classList.add('user-profile-btn'); // Add special class for logged-in state
            authBtn.onclick = function(e) {
                e.preventDefault();
                showUserMenu();
            };
        } catch (e) {
            // If parsing fails, clear the invalid data
            localStorage.removeItem('currentUser');
            authBtn.textContent = 'Sign In';
            authBtn.classList.remove('user-profile-btn');
            authBtn.onclick = function(e) {
                e.preventDefault();
                document.getElementById('authModal').classList.add('active');
                document.body.style.overflow = 'hidden';
            };
        }
    } else {
        authBtn.textContent = 'Sign In';
        authBtn.classList.remove('user-profile-btn'); // Remove special class when logged out
        authBtn.onclick = function(e) {
            e.preventDefault();
            document.getElementById('authModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        };
    }
}

// View user vouchers
async function viewUserVouchers() {
    // Check if user is still logged in (could have logged out in another tab)
    const savedUser = localStorage.getItem('currentUser');
    if (!savedUser) {
        showError('Silakan login terlebih dahulu.');
        currentUser = null;
        initializeAuthButton(); // Use the centralized function
        return;
    }
    
    // Always refresh currentUser from localStorage to ensure latest data
    currentUser = JSON.parse(savedUser);
    
    if (!currentUser || !currentUser.user_id) {
        showError('Silakan login terlebih dahulu.');
        currentUser = null;
        localStorage.removeItem('currentUser');
        localStorage.removeItem('authToken');
        initializeAuthButton();
        return;
    }
    
    try {
        const response = await fetch('api/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'get_user_vouchers',
                user_id: currentUser.user_id
            })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            showVoucherModal(data.data);
        } else {
            // Check if it's a session-related error
            if (data.message && (data.message.includes('User ID wajib diisi') || data.message.includes('Database connection failed'))) {
                // Session might be invalid, clear local storage
                localStorage.removeItem('currentUser');
                localStorage.removeItem('authToken');
                currentUser = null;
                initializeAuthButton();
                showError('Sesi anda telah habis. Silakan login kembali.');
                return;
            }
            showError(data.message || 'Gagal mengambil data voucher.');
        }
    } catch (error) {
        showError('Terjadi kesalahan saat mengambil data voucher. Silakan coba lagi.');
        console.error('Get vouchers error:', error);
    }
}

// Show voucher modal
function showVoucherModal(vouchers) {
    const voucherModal = document.createElement('div');
    voucherModal.className = 'modal active';
    voucherModal.innerHTML = `
        <div class="modal-content" style="max-width: 600px;">
            <div class="close-btn" id="closeVoucherModal">&times;</div>
            <div class="voucher-container">
                <h2>Voucher Saya</h2>
                ${vouchers.length > 0 ? 
                    '<div class="vouchers-list">' + 
                    vouchers.map(voucher => `
                        <div class="voucher-item">
                            <div class="voucher-header">
                                <h3>${voucher.package_name}</h3>
                                <span class="voucher-status ${voucher.order_status}">${voucher.order_status}</span>
                            </div>
                            <div class="voucher-details">
                                <p><strong>Kode Voucher:</strong> ${voucher.voucher_code || 'N/A'}</p>
                                <p><strong>Username:</strong> ${voucher.username || 'N/A'}</p>
                                <p><strong>Password:</strong> ${voucher.password || 'N/A'}</p>
                                <p><strong>Harga:</strong> Rp ${voucher.price.toLocaleString()}</p>
                                <p><strong>Kadaluarsa:</strong> ${voucher.expires_at || 'N/A'}</p>
                            </div>
                        </div>
                    `).join('') + 
                    '</div>' : 
                    '<p>Anda belum memiliki voucher. Pesan sekarang!</p>'
                }
            </div>
        </div>
    `;
    
    document.body.appendChild(voucherModal);
    
    document.getElementById('closeVoucherModal').addEventListener('click', () => {
        document.body.removeChild(voucherModal);
    });
    
    voucherModal.addEventListener('click', (e) => {
        if (e.target === voucherModal) {
            document.body.removeChild(voucherModal);
        }
    });
}

// Show edit profile modal
function showEditProfileModal() {
    // Close the user menu first
    const userMenu = document.querySelector('.user-menu');
    if (userMenu) {
        document.body.removeChild(userMenu);
    }
    
    const editProfileModal = document.createElement('div');
    editProfileModal.className = 'modal active';
    editProfileModal.innerHTML = `
        <div class="modal-content" style="max-width: 500px;">
            <div class="close-btn" id="closeEditProfileModal">&times;</div>
            <div class="edit-profile-container">
                <h2>Edit Profile</h2>
                <form id="editProfileForm">
                    <div class="input-group">
                        <label for="editName">Nama Lengkap</label>
                        <input type="text" id="editName" value="${currentUser.name || ''}" required>
                    </div>
                    <div class="input-group">
                        <label for="editEmail">Email</label>
                        <input type="email" id="editEmail" value="${currentUser.email || ''}" required>
                    </div>
                    <div class="input-group">
                        <label for="editPhone">Nomor Telepon</label>
                        <input type="tel" id="editPhone" value="${currentUser.phone || ''}">
                    </div>
                    <div class="input-group">
                        <label for="currentPassword">Password Saat Ini (untuk verifikasi)</label>
                        <input type="password" id="currentPassword" placeholder="Masukkan password saat ini">
                    </div>
                    <div class="input-group">
                        <label for="newPassword">Password Baru (kosongkan jika tidak ingin diganti)</label>
                        <input type="password" id="newPassword" placeholder="Kosongkan jika tidak ingin diganti">
                    </div>
                    <div class="input-group">
                        <label for="confirmNewPassword">Konfirmasi Password Baru</label>
                        <input type="password" id="confirmNewPassword" placeholder="Kosongkan jika tidak ingin diganti">
                    </div>
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    `;
    
    document.body.appendChild(editProfileModal);
    
    // Handle form submission
    const form = document.getElementById('editProfileForm');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const updatedName = document.getElementById('editName').value;
        const updatedEmail = document.getElementById('editEmail').value;
        const updatedPhone = document.getElementById('editPhone').value;
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmNewPassword = document.getElementById('confirmNewPassword').value;
        
        // Validate passwords if new password is provided
        if (newPassword && newPassword !== confirmNewPassword) {
            showError('Password baru dan konfirmasi password tidak cocok');
            return;
        }
        
        try {
            const response = await fetch('api/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update_profile',
                    user_id: currentUser.user_id,
                    name: updatedName,
                    email: updatedEmail,
                    phone: updatedPhone,
                    current_password: currentPassword,
                    new_password: newPassword
                })
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                // Update current user object with returned data
                if (data.user) {
                    currentUser = data.user;
                } else {
                    // Fallback to manual update
                    currentUser.name = updatedName;
                    currentUser.email = updatedEmail;
                    currentUser.phone = updatedPhone;
                }
                
                // Update localStorage
                localStorage.setItem('currentUser', JSON.stringify(currentUser));
                
                // Update the auth button text
                const authBtn = document.getElementById('authBtn');
                const initial = currentUser.name.charAt(0).toUpperCase();
                authBtn.innerHTML = `<span class="user-initial">${initial}</span>`;
                
                // Close the edit profile modal
                if (document.body.contains(editProfileModal)) {
                    document.body.removeChild(editProfileModal);
                }
                
                // Show success message
                showSuccess('Profile berhasil diperbarui!');
            } else {
                showError(data.message || 'Gagal memperbarui profile.');
            }
        } catch (error) {
            showError('Terjadi kesalahan saat memperbarui profile.');
            console.error('Update profile error:', error);
        }
    });
    
    // Close modal events
    document.getElementById('closeEditProfileModal').addEventListener('click', () => {
        document.body.removeChild(editProfileModal);
    });
    
    editProfileModal.addEventListener('click', (e) => {
        if (e.target === editProfileModal) {
            document.body.removeChild(editProfileModal);
        }
    });
}

// Mobile Navigation
hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    navMenu.classList.toggle('active');
});

// Close mobile menu when clicking on a link
document.querySelectorAll('.nav-menu a').forEach(link => {
    link.addEventListener('click', () => {
        hamburger.classList.remove('active');
        navMenu.classList.remove('active');
    });
});

// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        
        const targetId = this.getAttribute('href');
        const targetElement = document.querySelector(targetId);
        
        if (targetElement) {
            window.scrollTo({
                top: targetElement.offsetTop - 80,
                behavior: 'smooth'
            });
        }
    });
});

// Sticky navbar effect
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.style.padding = '0.5rem 0';
        navbar.style.boxShadow = '0 4px 20px var(--shadow)';
    } else {
        navbar.style.padding = '1rem 0';
        navbar.style.boxShadow = '0 2px 10px var(--shadow)';
    }
});

// Intersection Observer for animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animated');
        }
    });
}, observerOptions);

// Observe elements for animations
document.querySelectorAll('.feature-card, .pricing-card, .contact-item').forEach(el => {
    observer.observe(el);
});

// Add scroll animation class to elements
document.querySelectorAll('.feature-card, .pricing-card, .contact-item').forEach((el, index) => {
    el.style.animationDelay = `${index * 0.1}s`;
});

// Header animation on load
document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('.hero-title');
    const description = document.querySelector('.hero-description');
    const buttons = document.querySelector('.hero-buttons');
    
    // Add animations with delays
    setTimeout(() => {
        header.style.opacity = '1';
        header.style.transform = 'translateY(0)';
    }, 100);
    
    setTimeout(() => {
        description.style.opacity = '1';
        description.style.transform = 'translateY(0)';
    }, 300);
    
    setTimeout(() => {
        buttons.style.opacity = '1';
        buttons.style.transform = 'translateY(0)';
    }, 500);
});

// Add CSS for animated class
const style = document.createElement('style');
style.textContent = `
    .feature-card, .pricing-card, .contact-item {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.6s ease-out;
    }
    
    .feature-card.animated, .pricing-card.animated, .contact-item.animated {
        opacity: 1;
        transform: translateY(0);
    }
    
    .hero-title, .hero-description, .hero-buttons {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.8s ease-out;
    }
`;
document.head.appendChild(style);

// Contact form submission
const contactForm = document.querySelector('.contact-form');
if (contactForm) {
    contactForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const message = document.getElementById('message').value;
        
        try {
            const response = await fetch('api/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'contact',
                    name: name,
                    email: email,
                    message: message
                })
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                alert(data.message);
                contactForm.reset();
            } else {
                alert(data.message || 'Gagal mengirim pesan. Silakan coba lagi.');
            }
        } catch (error) {
            alert('Terjadi kesalahan. Silakan coba lagi nanti.');
            console.error('Contact form error:', error);
        }
    });
}

// Pricing card selection
// Removed card click listener to prevent accidental form opening
// Only the "Pilih Paket" button should open the checkout form

// Checkout functionality
const checkoutModal = document.getElementById('checkoutModal');
const closeCheckoutModal = document.getElementById('closeCheckoutModal');
const checkoutButtons = document.querySelectorAll('.btn-primary[data-package]');
const checkoutForm = document.getElementById('checkoutForm');

// Package data for checkout modal
const packages = {
    basic: {
        name: "Paket Basic",
        price: "Rp 5.000",
        features: [
            "Unlimited Data",
            "Durasi 6 Jam",
            "Berlaku 1 Hari",
            "High Speed",
            "Prioritas Tinggi",
            "Dukungan 24/7"
        ]
    },
    plus: {
        name: "Paket Plus",
        price: "Rp 10.000",
        features: [
            "Unlimited Data",
            "Durasi 12 Jam",
            "Berlaku 1 Hari",
            "High Speed",
            "Prioritas Tinggi",
            "Dukungan 24/7"
        ]
    },
    premium: {
        name: "Paket Premium",
        price: "Rp 20.000",
        features: [
            "Unlimited Data",
            "Durasi 1 Hari",
            "Berlaku 2 Hari",
            "High Speed",
            "Prioritas Tinggi",
            "Dukungan 24/7"
        ]
    },
    ultimate: {
        name: "Paket Ultimate",
        price: "Rp 30.000",
        features: [
            "Unlimited Data",
            "Durasi 2 Hari",
            "Berlaku 3 Hari",
            "High Speed",
            "Prioritas Tinggi",
            "Dukungan 24/7"
        ]
    },
    elite: {
        name: "Paket Elite",
        price: "Rp 40.000",
        features: [
            "Unlimited Data",
            "Durasi 4 Hari",
            "Berlaku 5 Hari",
            "High Speed",
            "Prioritas Tinggi",
            "Dukungan 24/7"
        ]
    },
    pro: {
        name: "Paket Pro",
        price: "Rp 50.000",
        features: [
            "Unlimited Data",
            "Durasi 6 Hari",
            "Berlaku 7 Hari",
            "High Speed",
            "Prioritas Tinggi",
            "Dukungan 24/7"
        ]
    }
};

// Open checkout modal with selected package
checkoutButtons.forEach(button => {
    button.addEventListener('click', (e) => {
        e.preventDefault();
        const packageType = button.getAttribute('data-package');
        const packageData = packages[packageType];
        
        if (packageData) {
            // Update modal content with selected package
            document.getElementById('selectedPackageName').textContent = packageData.name;
            document.getElementById('selectedPackagePrice').textContent = packageData.price;
            
            const featuresList = document.getElementById('selectedPackageFeatures');
            featuresList.innerHTML = '';
            
            packageData.features.forEach(feature => {
                const li = document.createElement('li');
                li.innerHTML = `<i class="fas fa-check"></i> ${feature}`;
                featuresList.appendChild(li);
            });
            
            // Store the selected package for form submission
            checkoutForm.setAttribute('data-selected-package', packageType);
            
            // Populate user information if logged in
            if (currentUser) {
                document.getElementById('customerName').value = currentUser.name || '';
                document.getElementById('customerEmail').value = currentUser.email || '';
                document.getElementById('customerPhone').value = currentUser.phone || '';
            } else {
                // Clear fields if not logged in
                document.getElementById('customerName').value = '';
                document.getElementById('customerEmail').value = '';
                document.getElementById('customerPhone').value = '';
            }
            
            // Set up payment method change listener to conditionally show/hide payment account field
            setupPaymentMethodHandler();
            
            // Show the modal
            checkoutModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    });
});

// Close checkout modal
closeCheckoutModal.addEventListener('click', () => {
    checkoutModal.classList.remove('active');
    document.body.style.overflow = 'auto';
});

// Function to handle payment method selection
function setupPaymentMethodHandler() {
    // Simple payment method handler - no additional fields needed
    const paymentMethodSelect = document.getElementById('paymentMethod');
    
    // Add event listener to payment method select if needed for future features
    paymentMethodSelect.addEventListener('change', function() {
        // Currently no special handling needed, but keeping this for extensibility
        console.log('Payment method changed to:', this.value);
    });
}



// Function to load Midtrans Snap with dynamic client key
async function loadMidtrans() {
    try {
        const response = await fetch('api/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'get_payment_config'
            })
        });
        
        const data = await response.json();
        
        if (data.status === 'success' && data.data.client_key) {
            const clientKey = data.data.client_key;
            const isProduction = data.data.is_production;
            
            // Create the script element with the client key
            const script = document.createElement('script');
            script.src = `https://app${isProduction ? '' : '.sandbox'}.midtrans.com/snap/snap.js`;
            script.setAttribute('data-client-key', clientKey);
            
            // Append to document head
            document.head.appendChild(script);
            
            // Store the client key globally for later use
            window.MIDTRANS_CLIENT_KEY = clientKey;
            
            console.log('Midtrans loaded successfully with client key:', clientKey.substring(0, 10) + '...');
        } else {
            console.error('Failed to get payment configuration:', data.message || 'Unknown error');
            // Use fallback for development
            window.MIDTRANS_FALLBACK = true;
        }
    } catch (error) {
        console.error('Error loading Midtrans:', error);
        // Use fallback for development
        window.MIDTRANS_FALLBACK = true;
    }
}

// Load Midtrans when page loads
document.addEventListener('DOMContentLoaded', loadMidtrans);

// Close modal when clicking outside
checkoutModal.addEventListener('click', (e) => {
    if (e.target === checkoutModal) {
        checkoutModal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
});

// Handle checkout form submission
checkoutForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const selectedPackage = checkoutForm.getAttribute('data-selected-package');
    const packageName = packages[selectedPackage].name;
    const packagePrice = packages[selectedPackage].price;
    
    const customerName = document.getElementById('customerName').value;
    const customerEmail = document.getElementById('customerEmail').value;
    const customerPhone = document.getElementById('customerPhone').value;
    const paymentMethod = document.getElementById('paymentMethod').value;
    
    // No payment account needed for modern payment methods
    let paymentAccount = '';
    
    // Show confirmation before proceeding with checkout
    showConfirmation(
        `Apakah Anda yakin ingin memesan ${packageName}? Total: ${packagePrice}`,
        async () => {
            try {
                const response = await fetch('api/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'checkout',
                        package: selectedPackage,
                        packageName: packageName,
                        packagePrice: packagePrice,
                        customerName: customerName,
                        customerEmail: customerEmail,
                        customerPhone: customerPhone,
                        paymentMethod: paymentMethod,
                        paymentAccount: paymentAccount
                    })
                });
                
                // Check if the response is ok before parsing JSON
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    // Show Midtrans payment interface
                    const { snap_token, order_id } = data.data;
                    
                    if (snap_token && snap_token.startsWith('mock') === false) {
                        // Show success message for order creation
                        showSuccess(`Pesanan ${packageName} berhasil dibuat. Silakan selesaikan pembayaran.`);
                        
                        // Initialize Midtrans Snap after a short delay
                        setTimeout(() => {
                            // Close checkout modal
                            checkoutModal.classList.remove('active');
                            document.body.style.overflow = 'auto';
                            checkoutForm.reset();
                            
                            // Check if we're in fallback mode (development)
                            if (window.MIDTRANS_FALLBACK) {
                                // Show success message for development/testing
                                showSuccess('Data order telah dibuat! (Development Mode - Pembayaran dianggap berhasil untuk testing)', () => {
                                    // Close checkout modal
                                    checkoutModal.classList.remove('active');
                                    document.body.style.overflow = 'auto';
                                    checkoutForm.reset();
                                    
                                    // Simulate successful payment after a delay
                                    setTimeout(() => {
                                        showSuccess('Pembayaran berhasil! Voucher Anda akan segera aktif. (Simulasi Development)');
                                    }, 1500);
                                });
                            } else if (window.snap) {
                                // Show Midtrans payment popup
                                window.snap.show({
                                    token: snap_token,
                                    onSuccess: function(result) {
                                        showSuccess('Pembayaran berhasil! Voucher Anda akan segera aktif.');
                                        console.log('Payment success:', result);
                                    },
                                    onPending: function(result) {
                                        showSuccess('Pembayaran sedang diproses. Kami akan memberi tahu Anda setelah pembayaran dikonfirmasi.');
                                        console.log('Payment pending:', result);
                                    },
                                    onError: function(result) {
                                        showError('Pembayaran gagal. Silakan coba lagi.');
                                        console.log('Payment error:', result);
                                    }
                                });
                            } else {
                                // Wait a bit to ensure Midtrans has loaded, then try again
                                setTimeout(() => {
                                    if (window.snap) {
                                        window.snap.show({
                                            token: snap_token,
                                            onSuccess: function(result) {
                                                showSuccess('Pembayaran berhasil! Voucher Anda akan segera aktif.');
                                                console.log('Payment success:', result);
                                            },
                                            onPending: function(result) {
                                                showSuccess('Pembayaran sedang diproses. Kami akan memberi tahu Anda setelah pembayaran dikonfirmasi.');
                                                console.log('Payment pending:', result);
                                            },
                                            onError: function(result) {
                                                showError('Pembayaran gagal. Silakan coba lagi.');
                                                console.log('Payment error:', result);
                                            }
                                        });
                                    } else if (window.MIDTRANS_FALLBACK) {
                                        // Fallback for development mode
                                        showSuccess('Data order telah dibuat! (Development Mode)', () => {
                                            checkoutModal.classList.remove('active');
                                            document.body.style.overflow = 'auto';
                                            checkoutForm.reset();
                                            
                                            setTimeout(() => {
                                                showSuccess('Pembayaran berhasil! Voucher Anda akan segera aktif. (Simulasi)');
                                            }, 1500);
                                        });
                                    } else {
                                        // If still not loaded, show a different message
                                        showSuccess('Data order telah dibuat. Silakan refresh halaman dan coba lagi jika pembayaran tidak muncul.');
                                        console.log('Midtrans not loaded after timeout:', data);
                                    }
                                }, 2000); // Wait 2 seconds for Midtrans to load
                            }
                        }, 1000); // Wait 1 second before showing payment popup
                    } else {
                        // Handle mock tokens (development/testing)
                        showSuccess(`Pesanan ${packageName} berhasil dibuat. (Mock payment system - Dev Mode)`);
                        checkoutModal.classList.remove('active');
                        document.body.style.overflow = 'auto';
                        checkoutForm.reset();
                        
                        console.log('Mock payment successful:', data);
                    }
                } else {
                    showError(data.message || 'Terjadi kesalahan saat proses checkout. Silakan coba lagi.');
                }
            } catch (error) {
                // Check if this is a network error vs a server error
                if (error.message.includes('HTTP error')) {
                    showError('Kesalahan server. Silakan coba lagi nanti.');
                } else {
                    showError('Terjadi kesalahan jaringan. Silakan coba lagi nanti.');
                }
                console.error('Checkout error:', error);
            }
        }
    );
});
