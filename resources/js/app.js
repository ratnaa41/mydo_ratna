// ==================== API CONFIGURATION ====================
const API_URL = 'api.php';

// ==================== API HELPER ====================
async function apiCall(action, data = null) {
    const options = {
        method: data ? 'POST' : 'GET',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'include' // Important for session cookies
    };
    
    if (data) {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(`${API_URL}?action=${action}`, options);
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        showNotification('Connection error. Please check your server.', 'error');
        return { error: 'Connection failed' };
    }
}

// ==================== NOTIFICATION SYSTEM ====================
function showNotification(message, type = 'info') {
    const container = document.getElementById('notification-container');
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerText = message;
    container.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'fadeOut 0.25s forwards';
        setTimeout(() => notification.remove(), 300);
    }, 2800);
}

// ==================== AUTH FUNCTIONS ====================
window.showLoginForm = function() {
    document.getElementById('register-form').classList.add('hidden');
    document.getElementById('login-form').classList.remove('hidden');
};

window.showRegisterForm = function() {
    document.getElementById('login-form').classList.add('hidden');
    document.getElementById('register-form').classList.remove('hidden');
};

window.registerUser = async function() {
    const name = document.getElementById('reg-name').value.trim();
    const email = document.getElementById('reg-email').value.trim().toLowerCase();
    const password = document.getElementById('reg-password').value;
    const phone = document.getElementById('reg-phone').value.trim();
    
    if (!name || !email || !password) {
        return showNotification('name, email, password required', 'error');
    }
    if (password.length < 6) {
        return showNotification('password must be at least 6 characters', 'error');
    }
    
    const result = await apiCall('register', { name, email, password, phone });
    
    if (result.error) {
        showNotification(result.error, 'error');
    } else {
        showNotification('registered successfully! please login', 'success');
        showLoginForm();
    }
};

window.loginUser = async function() {
    const email = document.getElementById('login-email').value.trim().toLowerCase();
    const password = document.getElementById('login-password').value;
    
    const result = await apiCall('login', { email, password });
    
    if (result.error) {
        showNotification(result.error, 'error');
    } else {
        currentUser = result.user;
        document.getElementById('auth-container').style.display = 'none';
        document.getElementById('app-container').style.display = 'block';
        updateProfileDisplay();
        loadUserData();
        initTabs();
        showNotification(`✓ welcome ${currentUser.name}`, 'success');
    }
};

window.logout = async function() {
    await apiCall('logout');
    currentUser = null;
    document.getElementById('app-container').style.display = 'none';
    document.getElementById('auth-container').style.display = 'block';
    showLoginForm();
    showNotification('logged out successfully', 'info');
};

window.updateProfile = async function() {
    const name = document.getElementById('update-name').value.trim();
    const email = document.getElementById('update-email').value.trim().toLowerCase();
    const phone = document.getElementById('update-phone').value.trim();
    const password = document.getElementById('update-password').value;
    
    if (!name || !email) {
        return showNotification('name and email required', 'error');
    }
    
    const result = await apiCall('updateProfile', { name, email, phone, password });
    
    if (result.error) {
        showNotification(result.error, 'error');
    } else {
        currentUser.name = name;
        currentUser.email = email;
        currentUser.phone = phone;
        updateProfileDisplay();
        showNotification('profile updated successfully', 'success');
    }
};

// ==================== TASK FUNCTIONS ====================
let tasks = [];

async function loadTasks() {
    const result = await apiCall('getTasks');
    if (!result.error) {
        tasks = result;
        renderTasks();
    }
}

window.addTask = async function() {
    const title = document.getElementById('task-title').value.trim();
    if (!title) return showNotification('enter task title', 'error');
    
    const result = await apiCall('addTask', {
        title,
        priority: document.getElementById('task-priority').value,
        category: document.getElementById('task-category').value,
        notes: document.getElementById('task-notes').value.trim()
    });
    
    if (result.error) {
        showNotification(result.error, 'error');
    } else {
        await loadTasks();
        updateDashboard();
        document.getElementById('task-title').value = '';
        document.getElementById('task-notes').value = '';
        showNotification('task added', 'success');
    }
};

window.toggleTask = async function(id) {
    const task = tasks.find(t => t.id === id);
    if (task) {
        const result = await apiCall('toggleTask', { id, completed: !task.completed });
        if (!result.error) {
            await loadTasks();
            updateDashboard();
        }
    }
};

window.deleteTask = async function(id) {
    if (!confirm('delete this task?')) return;
    const result = await apiCall('deleteTask', { id });
    if (!result.error) {
        await loadTasks();
        updateDashboard();
        showNotification('task deleted', 'info');
    }
};

function renderTasks() {
    const container = document.getElementById('tasks-list');
    if (!tasks.length) {
        container.innerHTML = `<div class="empty-state"><span class="empty-icon">✓</span><p>no tasks</p></div>`;
        document.getElementById('active-tasks-count').innerText = '0';
        document.getElementById('completed-tasks-count').innerText = '0';
        return;
    }
    
    const active = tasks.filter(t => !t.completed).length;
    document.getElementById('active-tasks-count').innerText = active;
    document.getElementById('completed-tasks-count').innerText = tasks.length - active;
    
    let html = '';
    [...tasks].sort((a, b) => a.completed - b.completed).forEach(t => {
        html += `
            <div class="list-item">
                <div class="item-content">
                    <div class="checkbox ${t.completed ? 'checked' : ''}" onclick="toggleTask(${t.id})">
                        ${t.completed ? '✓' : ''}
                    </div>
                    <div>
                        <div class="item-title ${t.completed ? 'completed' : ''}">${escapeHtml(t.title)}</div>
                        <div class="item-meta">
                            <span class="priority-badge priority-${t.priority}">${t.priority}</span> ${t.category}
                        </div>
                        ${t.notes ? `<div style="color:#475569; font-size:13px;">${escapeHtml(t.notes)}</div>` : ''}
                    </div>
                </div>
                <div class="item-actions">
                    <button class="action-btn" onclick="deleteTask(${t.id})">✕</button>
                </div>
            </div>
        `;
    });
    container.innerHTML = html;
}

// ==================== TRANSACTION FUNCTIONS ====================
let transactions = [];
let activeType = 'income';

window.setType = function(type) {
    activeType = type;
    document.querySelectorAll('.type-btn').forEach(btn => btn.classList.remove('active'));
    if (type === 'income') {
        document.querySelectorAll('.type-btn')[0].classList.add('active');
    } else {
        document.querySelectorAll('.type-btn')[1].classList.add('active');
    }
};

async function loadTransactions() {
    const result = await apiCall('getTransactions');
    if (!result.error) {
        transactions = result;
        renderTransactions();
        updateBudgetStats();
    }
}

window.addTransaction = async function() {
    const amount = parseFloat(document.getElementById('transaction-amount').value);
    const title = document.getElementById('transaction-title').value.trim();
    const category = document.getElementById('transaction-category').value.trim() || 'uncategorized';
    const date = document.getElementById('transaction-date').value;
    const notes = document.getElementById('transaction-notes').value.trim();
    
    if (!title || !amount || amount <= 0 || !date) {
        return showNotification('fill amount, title, and date', 'error');
    }
    
    const result = await apiCall('addTransaction', {
        type: activeType,
        amount,
        title,
        category,
        date,
        notes
    });
    
    if (result.error) {
        showNotification(result.error, 'error');
    } else {
        await loadTransactions();
        updateDashboard();
        document.getElementById('transaction-amount').value = '';
        document.getElementById('transaction-title').value = '';
        document.getElementById('transaction-category').value = '';
        document.getElementById('transaction-notes').value = '';
        showNotification(`${activeType} added`, 'success');
    }
};

window.deleteTransaction = async function(id) {
    if (!confirm('delete transaction?')) return;
    const result = await apiCall('deleteTransaction', { id });
    if (!result.error) {
        await loadTransactions();
        updateDashboard();
        showNotification('transaction deleted', 'info');
    }
};

function renderTransactions() {
    const container = document.getElementById('transactions-list');
    if (!transactions.length) {
        container.innerHTML = `<div class="empty-state"><span class="empty-icon">💰</span><p>no transactions</p></div>`;
        return;
    }
    
    let html = '';
    [...transactions].sort((a, b) => new Date(b.transaction_date) - new Date(a.transaction_date)).forEach(t => {
        html += `
            <div class="list-item">
                <div class="item-content">
                    <div>
                        <div class="item-title">${escapeHtml(t.title)}</div>
                        <div class="item-meta">${escapeHtml(t.category)} · ${new Date(t.transaction_date).toLocaleDateString()}</div>
                        ${t.notes ? `<div style="color:#475569;">${escapeHtml(t.notes)}</div>` : ''}
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <span class="item-amount ${t.type === 'income' ? 'amount-income' : 'amount-expense'}">
                        ${t.type === 'income' ? '+' : '-'}$${parseFloat(t.amount).toFixed(2)}
                    </span>
                    <button class="action-btn" onclick="deleteTransaction(${t.id})">✕</button>
                </div>
            </div>
        `;
    });
    container.innerHTML = html;
}

function updateBudgetStats() {
    const inc = transactions.filter(t => t.type === 'income').reduce((sum, t) => sum + parseFloat(t.amount), 0);
    const exp = transactions.filter(t => t.type === 'expense').reduce((sum, t) => sum + parseFloat(t.amount), 0);
    document.getElementById('current-balance').innerText = (inc - exp).toFixed(2);
}

// ==================== NOTE FUNCTIONS ====================
let notes = [];

async function loadNotes() {
    const result = await apiCall('getNotes');
    if (!result.error) {
        notes = result;
        renderNotes();
    }
}

window.addNote = async function() {
    const title = document.getElementById('note-title').value.trim();
    const content = document.getElementById('note-content').value.trim();
    
    if (!title || !content) {
        return showNotification('title and content required', 'error');
    }
    
    const result = await apiCall('addNote', { title, content });
    
    if (result.error) {
        showNotification(result.error, 'error');
    } else {
        await loadNotes();
        updateDashboard();
        document.getElementById('note-title').value = '';
        document.getElementById('note-content').value = '';
        showNotification('note saved', 'success');
    }
};

window.deleteNote = async function(id) {
    if (!confirm('delete note?')) return;
    const result = await apiCall('deleteNote', { id });
    if (!result.error) {
        await loadNotes();
        updateDashboard();
        showNotification('note deleted', 'info');
    }
};

function renderNotes() {
    const container = document.getElementById('notes-list');
    if (!notes.length) {
        container.innerHTML = `<div class="empty-state"><span class="empty-icon">✎</span><p>no notes</p></div>`;
        document.getElementById('total-notes-count').innerText = '0 notes';
        return;
    }
    
    document.getElementById('total-notes-count').innerText = notes.length + (notes.length === 1 ? ' note' : ' notes');
    
    let html = '';
    notes.forEach(n => {
        html += `
            <div class="list-item">
                <div class="item-content">
                    <div>
                        <div class="item-title">${escapeHtml(n.title)}</div>
                        <div style="color:#334155; margin:4px 0">${escapeHtml(n.content)}</div>
                        <div class="item-meta">${new Date(n.created_at).toLocaleString()}</div>
                    </div>
                </div>
                <div class="item-actions">
                    <button class="action-btn" onclick="deleteNote(${n.id})">✕</button>
                </div>
            </div>
        `;
    });
    container.innerHTML = html;
}

// ==================== DASHBOARD FUNCTIONS ====================
let dashboardStats = null;

async function updateDashboard() {
    const result = await apiCall('getStats');
    if (!result.error) {
        dashboardStats = result;
        
        document.getElementById('total-tasks').innerText = result.activeTasks;
        document.getElementById('total-income').innerText = parseFloat(result.totalIncome || 0).toFixed(2);
        document.getElementById('total-expense').innerText = parseFloat(result.totalExpense || 0).toFixed(2);
        document.getElementById('total-notes').innerText = result.totalNotes;
        
        // Recent tasks
        const rt = document.getElementById('recent-tasks');
        if (result.recentTasks && result.recentTasks.length) {
            rt.innerHTML = result.recentTasks.map(t => `
                <div class="list-item">
                    <div class="item-content">
                        <div class="checkbox ${t.completed ? 'checked' : ''}">${t.completed ? '✓' : ''}</div>
                        <span class="item-title ${t.completed ? 'completed' : ''}">${escapeHtml(t.title)}</span>
                    </div>
                </div>
            `).join('');
        } else {
            rt.innerHTML = `<div class="empty-state"><span class="empty-icon">✓</span><p>no tasks</p></div>`;
        }
        
        // Recent transactions
        const rtx = document.getElementById('recent-transactions');
        if (result.recentTransactions && result.recentTransactions.length) {
            rtx.innerHTML = result.recentTransactions.map(t => `
                <div class="list-item">
                    <div class="item-content"><span>${escapeHtml(t.title)}</span></div>
                    <span class="item-amount ${t.type === 'income' ? 'amount-income' : 'amount-expense'}">
                        ${t.type === 'income' ? '+' : '-'}$${parseFloat(t.amount).toFixed(2)}
                    </span>
                </div>
            `).join('');
        } else {
            rtx.innerHTML = `<div class="empty-state"><span class="empty-icon">💰</span><p>no transactions</p></div>`;
        }
    }
}

// ==================== PROFILE STATS ====================
function updateProfileStats() {
    if (!dashboardStats) return;
    
    document.getElementById('profile-total-tasks').innerText = tasks.length;
    const inc = transactions.filter(t => t.type === 'income').reduce((s, t) => s + parseFloat(t.amount), 0);
    const exp = transactions.filter(t => t.type === 'expense').reduce((s, t) => s + parseFloat(t.amount), 0);
    document.getElementById('profile-total-income').innerText = inc.toFixed(2);
    document.getElementById('profile-total-expense').innerText = exp.toFixed(2);
    document.getElementById('profile-total-notes').innerText = notes.length;
}

// ==================== PROFILE DISPLAY ====================
let currentUser = null;

function updateProfileDisplay() {
    if (!currentUser) return;
    
    document.getElementById('user-avatar').innerText = currentUser.name.charAt(0).toUpperCase();
    document.getElementById('profile-avatar').innerText = currentUser.name.charAt(0).toUpperCase();
    document.getElementById('profile-name').innerText = currentUser.name;
    document.getElementById('profile-email').innerText = currentUser.email;
    document.getElementById('profile-phone').innerText = currentUser.phone || '—';
    document.getElementById('join-date').innerText = currentUser.joinDate || new Date().toLocaleDateString();
    
    document.getElementById('update-name').value = currentUser.name;
    document.getElementById('update-email').value = currentUser.email;
    document.getElementById('update-phone').value = currentUser.phone || '';
}

// ==================== TAB INITIALIZATION ====================
function initTabs() {
    document.querySelectorAll('.tab-btn').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
            
            if (tabId === 'dashboard') updateDashboard();
            else if (tabId === 'tasks') renderTasks();
            else if (tabId === 'budget') {
                renderTransactions();
                updateBudgetStats();
            }
            else if (tabId === 'notes') renderNotes();
            else if (tabId === 'profile') updateProfileStats();
        });
    });
}

// ==================== LOAD ALL DATA ====================
async function loadUserData() {
    await Promise.all([
        loadTasks(),
        loadTransactions(),
        loadNotes(),
        updateDashboard()
    ]);
    updateProfileStats();
}

// ==================== CHECK AUTHENTICATION ====================
async function checkAuth() {
    const result = await apiCall('checkAuth');
    if (result.authenticated) {
        currentUser = result.user;
        document.getElementById('auth-container').style.display = 'none';
        document.getElementById('app-container').style.display = 'block';
        updateProfileDisplay();
        await loadUserData();
        initTabs();
    } else {
        document.getElementById('auth-container').style.display = 'block';
        document.getElementById('app-container').style.display = 'none';
    }
}

// ==================== HELPER FUNCTION ====================
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('transaction-date')) {
        document.getElementById('transaction-date').valueAsDate = new Date();
    }
    checkAuth();
});
