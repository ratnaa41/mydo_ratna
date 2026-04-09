<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MYDO · tasks · budget · notes</title>
   @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <!-- Notification Container -->
    <div id="notification-container"></div>

    <!-- Authentication Container -->
    <div class="container" id="auth-container">
        <div class="auth-container">
            <!-- Register Form -->
            <div id="register-form" class="auth-form">
                <div class="auth-logo">
                    <div class="mark">✓</div>
                    <span>MYDO</span>
                </div>
                <h3 style="margin-bottom: 28px; text-align: center; font-weight: 600;">create account</h3>
                <div class="form-group">
                    <label class="form-label">full name</label>
                    <input type="text" id="reg-name" class="form-control" placeholder="ratna ">
                </div>
                <div class="form-group">
                    <label class="form-label">email</label>
                    <input type="email" id="reg-email" class="form-control" placeholder="ratna@example.com ">
                </div>
                <div class="form-group">
                    <label class="form-label">password</label>
                    <input type="password" id="reg-password" class="form-control" placeholder="......">
                </div>
                <div class="form-group">
                    <label class="form-label">phone (optional)</label>
                    <input type="tel" id="reg-phone" class="form-control" placeholder="+880">
                </div>
                <button class="btn btn-primary" onclick="registerUser()">✓ create account</button>
                <div class="auth-switch">
                    already have an account? <a onclick="showLoginForm()">login</a>
                </div>
            </div>

            <!-- Login Form -->
            <div id="login-form" class="auth-form hidden">
                <div class="auth-logo">
                    <div class="mark">✓</div>
                    <span>MYDO</span>
                </div>
                <h3 style="margin-bottom: 28px; text-align: center; font-weight: 600;">welcome to MYDO</h3>
                <div class="form-group">
                    <label class="form-label">email</label>
                    <input type="email" id="login-email" class="form-control" placeholder="me@example.com">
                </div>
                <div class="form-group">
                    <label class="form-label">password</label>
                    <input type="password" id="login-password" class="form-control" placeholder="······">
                </div>
                <button class="btn btn-primary" onclick="loginUser()">✓ login</button>
                <div class="auth-switch">
                    don't have an account? <a onclick="showRegisterForm()">register</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Application Container -->
    <div id="app-container" class="container app-container">
        <!-- Header -->
        <header class="app-header">
            <div class="logo">
                <span class="logo-mark">✓</span> MYDO
            </div>
            <div class="user-info">
                <div class="user-avatar" id="user-avatar">U</div>
                <button class="logout-btn" onclick="logout()">✕ logout</button>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="app-nav">
            <button class="tab-btn active" data-tab="dashboard">dashboard</button>
            <button class="tab-btn" data-tab="tasks">tasks</button>
            <button class="tab-btn" data-tab="budget">budget</button>
            <button class="tab-btn" data-tab="notes">notes</button>
            <button class="tab-btn" data-tab="profile">profile</button>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Dashboard -->
            <div id="dashboard" class="tab-content active">
                <h2>dashboard</h2>
                <div class="page-subtitle">@mydo · overview</div>
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-value" id="total-tasks">0</div>
                        <div class="stat-label">active tasks</div>
                    </div>
                    <div class="stat-card income">
                        <div class="stat-value" id="total-income">0.00</div>
                        <div class="stat-label">income</div>
                    </div>
                    <div class="stat-card expense">
                        <div class="stat-value" id="total-expense">0.00</div>
                        <div class="stat-label">expense</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="total-notes">0</div>
                        <div class="stat-label">notes</div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">recent tasks</span>
                    </div>
                    <div id="recent-tasks" class="empty-state">
                        <span class="empty-icon">✓</span>
                        <p>no tasks yet</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">recent transactions</span>
                    </div>
                    <div id="recent-transactions" class="empty-state">
                        <span class="empty-icon">💰</span>
                        <p>no transactions</p>
                    </div>
                </div>
            </div>

            <!-- Tasks -->
            <div id="tasks" class="tab-content">
                <h2>tasks</h2>
                <div class="page-subtitle">✓organise</div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">add task</span>
                    </div>
                    <div class="task-form">
                        <input type="text" id="task-title" class="form-control" placeholder="task title">
                        <div class="form-row" style="display:flex; gap:12px">
                            <select id="task-priority" class="select">
                                <option value="low">low</option>
                                <option value="medium" selected>medium</option>
                                <option value="high">high</option>
                            </select>
                            <select id="task-category" class="select">
                                <option value="personal">personal</option>
                                <option value="work">work</option>
                                <option value="other">other</option>
                            </select>
                        </div>
                        <textarea id="task-notes" class="form-control" placeholder="notes (optional)"></textarea>
                        <button class="btn btn-primary" onclick="addTask()">✓ add task</button>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">all tasks</span>
                        <span id="active-tasks-count">0</span> active · <span id="completed-tasks-count">0</span> done
                    </div>
                    <div id="tasks-list"></div>
                </div>
            </div>

            <!-- Budget -->
            <div id="budget" class="tab-content">
                <h2>budget</h2>
                <div class="page-subtitle">income / expense</div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">add transaction</span>
                    </div>
                    <div class="transaction-type">
                        <button class="type-btn active" onclick="setType('income')">income</button>
                        <button class="type-btn" onclick="setType('expense')">expense</button>
                    </div>
                    <input type="number" id="transaction-amount" class="form-control" placeholder="amount" step="0.01" style="margin-bottom:12px">
                    <input type="text" id="transaction-title" class="form-control" placeholder="description" style="margin-bottom:12px">
                    <input type="text" id="transaction-category" class="form-control" placeholder="category (e.g. food)" style="margin-bottom:12px">
                    <input type="date" id="transaction-date" class="form-control" style="margin-bottom:12px">
                    <textarea id="transaction-notes" class="form-control" placeholder="notes" style="margin-bottom:16px"></textarea>
                    <button class="btn btn-primary" onclick="addTransaction()">✓ add</button>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">history</span> balance: $<span id="current-balance">0.00</span>
                    </div>
                    <div id="transactions-list"></div>
                </div>
            </div>

            <!-- Notes -->
            <div id="notes" class="tab-content">
                <h2>notes</h2>
                <div class="page-subtitle">✎ write down</div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">new note</span>
                    </div>
                    <input type="text" id="note-title" class="form-control" placeholder="title" style="margin-bottom:12px">
                    <textarea id="note-content" class="form-control" placeholder="content" rows="5" style="margin-bottom:16px"></textarea>
                    <button class="btn btn-primary" onclick="addNote()">✎ save note</button>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">all notes</span> <span id="total-notes-count">0 notes</span>
                    </div>
                    <div id="notes-list"></div>
                </div>
            </div>

            <!-- Profile -->
            <div id="profile" class="tab-content">
                <div class="profile-header">
                    <div class="profile-avatar" id="profile-avatar">U</div>
                    <div>
                        <h2 id="profile-name">name</h2>
                        <p id="profile-email">email</p>
                        <p id="profile-phone"></p>
                        <p style="color:#94a3b8; font-size:13px">joined <span id="join-date"></span></p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">update profile</span>
                    </div>
                    <input type="text" id="update-name" class="form-control" placeholder="name" style="margin-bottom:12px">
                    <input type="email" id="update-email" class="form-control" placeholder="email" style="margin-bottom:12px">
                    <input type="tel" id="update-phone" class="form-control" placeholder="phone" style="margin-bottom:12px">
                    <input type="password" id="update-password" class="form-control" placeholder="new password (leave blank)">
                    <button class="btn btn-primary" style="margin-top:16px" onclick="updateProfile()">update</button>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">stats</span>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; padding:8px">
                        <div><span id="profile-total-tasks">0</span> tasks</div>
                        <div><span id="profile-total-income">0</span> income</div>
                        <div><span id="profile-total-expense">0</span> expense</div>
                        <div><span id="profile-total-notes">0</span> notes</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="app-footer">
            <span>@mydo by ratna</span> · life || productivity
        </div>
    </div>

    <script src="index.js"></script>
</body>
</html>