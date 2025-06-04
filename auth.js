// Check authentication status and update UI
function updateAuthUI() {
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || {};
    const navElement = document.getElementById('mainNav');
    
    if (token && user) {
        // User is logged in
        navElement.innerHTML = `
            <ul>
                <li><a href="dashboard.html"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="my-files.html"><i class="fas fa-folder"></i> My Files</a></li>
                <li><a href="account.html"><i class="fas fa-user-circle"></i> ${user.name || 'Account'}</a></li>
                <li><a href="#" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        `;
        
        // Add logout event
        document.getElementById('logoutBtn').addEventListener('click', logout);
    } else {
        // User is not logged in
        navElement.innerHTML = `
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="features.html">Features</a></li>
                <li><a href="login.html" class="btn btn-outline">Login</a></li>
                <li><a href="register.html" class="btn btn-primary">Create Account</a></li>
            </ul>
        `;
    }
}

// Logout function
function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = 'index.html';
}

// Check if user is authenticated on protected pages
function checkAuth() {
    const token = localStorage.getItem('token');
    if (!token && (window.location.pathname.includes('dashboard.html') || 
                   window.location.pathname.includes('my-files.html') ||
                   window.location.pathname.includes('account.html'))) {
        window.location.href = 'login.html';
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    updateAuthUI();
    checkAuth();
    
    // Mobile menu toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            const nav = document.getElementById('mainNav');
            nav.classList.toggle('active');
        });
    }
});

// Export functions for module usage
export { updateAuthUI, logout, checkAuth };
