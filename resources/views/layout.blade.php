<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'JatraPoth - Smart Bus Booking System')</title>
    
    <!-- Meta SEO & Theme -->
    <meta name="description" content="JatraPoth - Fast, easy, and secure bus ticket booking across Bangladesh. Select seats, view ratings, and book tickets instantly.">
    <meta name="theme-color" content="#2563eb">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Stylesheets & Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-light: #eff6ff;
            --secondary: #64748b;
            --accent: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #1e293b 100%);
            --card-bg: rgba(255, 255, 255, 0.96);
            --card-border: rgba(226, 232, 240, 0.8);
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --touch-target-min: 48px;
            --border-radius-lg: 16px;
            --border-radius-md: 12px;
            --border-radius-sm: 8px;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 25px -5px rgba(0,0,0,0.12);
            --shadow-primary: 0 4px 14px rgba(37, 99, 235, 0.35);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            color: var(--text-dark);
            font-size: 1rem;
            line-height: 1.6;
            padding-bottom: 76px; /* Space for Mobile Bottom Nav */
        }

        @media (min-width: 769px) {
            body {
                padding-bottom: 0;
            }
        }

        /* Responsive Container */
        .app-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.25rem 1rem;
        }

        /* 48px Minimum Touch Target Rule */
        .btn, .nav-link, input, select, textarea, .form-control, .form-select, .seat-item, .touch-target {
            min-height: var(--touch-target-min);
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--border-radius-md);
            touch-action: manipulation;
        }

        .btn {
            font-weight: 600;
            padding: 0.75rem 1.25rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            gap: 0.5rem;
        }

        .btn:active {
            transform: scale(0.97);
        }

        .btn-primary-touch {
            background-color: var(--primary);
            color: #ffffff !important;
            border: none;
            box-shadow: var(--shadow-primary);
        }

        .btn-primary-touch:hover, .btn-primary-touch:focus {
            background-color: var(--primary-hover);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.45);
        }

        .btn-outline-touch {
            background: transparent;
            color: var(--primary) !important;
            border: 2px solid var(--primary);
        }

        .btn-outline-touch:hover {
            background: var(--primary-light);
        }

        /* Header Navigation */
        .main-header {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 1030;
            padding: 0.75rem 0;
        }

        .brand-logo {
            font-size: 1.4rem;
            font-weight: 800;
            color: #ffffff !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .brand-logo i {
            color: #60a5fa;
            font-size: 1.6rem;
        }

        /* Desktop Nav Items */
        .desktop-nav .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: var(--border-radius-sm);
            transition: all 0.2s ease;
        }

        .desktop-nav .nav-link:hover, .desktop-nav .nav-link.active {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.15);
        }

        /* Ergonomic Mobile Bottom Navigation Bar */
        .mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 68px;
            background: #ffffff;
            border-top: 1px solid var(--card-border);
            display: flex;
            justify-content: space-around;
            align-items: center;
            z-index: 1040;
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.08);
            padding: 0 0.5rem;
        }

        @media (min-width: 769px) {
            .mobile-bottom-nav {
                display: none !important;
            }
        }

        .bottom-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 500;
            gap: 2px;
            height: 100%;
            transition: all 0.2s ease;
        }

        .bottom-nav-item i {
            font-size: 1.35rem;
            min-height: 24px;
        }

        .bottom-nav-item.active {
            color: var(--primary);
            font-weight: 700;
        }

        /* Glassmorphic Cards */
        .glass-card {
            background: var(--card-bg);
            border-radius: var(--border-radius-lg);
            border: 1px solid var(--card-border);
            box-shadow: var(--shadow-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(10px);
        }

        /* AI Floating Assistant Button */
        .ai-fab-btn {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: white;
            border: none;
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
            z-index: 1045;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @media (min-width: 769px) {
            .ai-fab-btn {
                bottom: 25px;
                right: 25px;
            }
        }

        .ai-fab-btn:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 8px 25px rgba(124, 58, 237, 0.6);
        }

        /* AI Help Chat Panel Modal */
        .ai-chat-box {
            height: 340px;
            overflow-y: auto;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .chat-msg {
            margin-bottom: 0.85rem;
            display: flex;
            flex-direction: column;
        }

        .chat-msg.bot {
            align-items: flex-start;
        }

        .chat-msg.user {
            align-items: flex-end;
        }

        .msg-bubble {
            max-width: 85%;
            padding: 0.75rem 1rem;
            border-radius: 14px;
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .chat-msg.bot .msg-bubble {
            background: #ffffff;
            color: #0f172a;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }

        .chat-msg.user .msg-bubble {
            background: #2563eb;
            color: #ffffff;
            border-bottom-right-radius: 4px;
        }

        .qna-chip {
            display: inline-block;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 20px;
            padding: 0.35rem 0.75rem;
            font-size: 0.82rem;
            font-weight: 500;
            color: #334155;
            cursor: pointer;
            transition: all 0.2s ease;
            margin: 0.25rem 0.15rem;
            /* Native button reset — these are <button> so keyboard/Enter/Space work for free */
            font-family: inherit;
            line-height: 1.5;
            min-height: 38px;
            text-align: left;
        }

        .qna-chip:hover {
            background: #eff6ff;
            border-color: #2563eb;
            color: #2563eb;
        }

        .qna-chip:focus-visible {
            outline: 3px solid var(--primary);
            outline-offset: 2px;
            background: #eff6ff;
            border-color: #2563eb;
            color: #2563eb;
        }
    </style>
    @yield('styles')
</head>

<body>
    <!-- Top Header Navigation -->
    <header class="main-header">
        <div class="container d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ url('/') }}" class="brand-logo">
                    <i class="fas fa-bus"></i> JatraPoth
                </a>

                <!-- Real-Time Ticking Live Clock with Date & Seconds -->
                <div class="d-none d-lg-flex align-items-center gap-2 text-white-50 bg-dark bg-opacity-50 px-3 py-1 rounded-pill border border-white border-opacity-10 small">
                    <i class="far fa-clock text-warning"></i>
                    <span id="liveDateTimeClock" class="fw-semibold text-white"></span>
                </div>
            </div>

            <!-- Desktop Nav Links -->
            <nav class="desktop-nav d-none d-md-flex align-items-center gap-2">
                <a href="{{ url('/') }}" class="nav-link {{ request()->is('/') ? 'active' : '' }}">
                    <i class="fas fa-home me-1"></i> Home
                </a>
                <a href="{{ route('search_bus') }}" class="nav-link {{ request()->is('search*') ? 'active' : '' }}">
                    <i class="fas fa-search me-1"></i> Search Buses
                </a>
                @auth
                <a href="{{ route('seat.swap.list') }}" class="nav-link {{ request()->is('seat-swap*') ? 'active' : '' }}">
                    <i class="fas fa-exchange-alt me-1"></i> Seat Swaps
                </a>
                <a href="{{ route('purchase_history') }}" class="nav-link {{ request()->is('purchase*') ? 'active' : '' }}">
                    <i class="fas fa-ticket-alt me-1"></i> My Tickets
                </a>
                <a href="{{ route('my.contributions') }}" class="nav-link {{ request()->is('my-contributions*') ? 'active' : '' }}">
                    <i class="fas fa-camera me-1"></i> My Photos
                </a>
                <a href="{{ route('view_profile') }}" class="nav-link {{ request()->is('view_profile*') || request()->is('edit_profile*') ? 'active' : '' }}">
                    <i class="fas fa-user me-1"></i> Profile
                </a>
                <a href="{{ route('log_out') }}" class="btn btn-sm btn-outline-light ms-2">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
                @else
                <a href="{{ route('login') }}" class="nav-link">
                    <i class="fas fa-sign-in-alt me-1"></i> Sign In / Up
                </a>
                <a href="{{ route('admin_login.view') }}" class="btn btn-sm btn-warning ms-2">
                    <i class="fas fa-user-shield me-1"></i> Admin
                </a>
                @endauth
            </nav>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="app-container">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle fs-5 me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle fs-5 me-2"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @yield('content')
    </main>

    <!-- Mobile Bottom Navigation Bar -->
    <nav class="mobile-bottom-nav" aria-label="Mobile Bottom Navigation">
        <a href="{{ url('/') }}" class="bottom-nav-item {{ request()->is('/') ? 'active' : '' }}">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="{{ route('search_bus') }}" class="bottom-nav-item {{ request()->is('search*') ? 'active' : '' }}">
            <i class="fas fa-search"></i>
            <span>Search</span>
        </a>
        @auth
        <a href="{{ route('seat.swap.list') }}" class="bottom-nav-item {{ request()->is('seat-swap*') ? 'active' : '' }}">
            <i class="fas fa-exchange-alt"></i>
            <span>Swaps</span>
        </a>
        <a href="{{ route('purchase_history') }}" class="bottom-nav-item {{ request()->is('purchase*') ? 'active' : '' }}">
            <i class="fas fa-ticket-alt"></i>
            <span>Tickets</span>
        </a>
        <a href="{{ route('view_profile') }}" class="bottom-nav-item {{ request()->is('view_profile*') || request()->is('edit_profile*') ? 'active' : '' }}">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
        @else
        <a href="{{ route('login') }}" class="bottom-nav-item {{ request()->is('login*') ? 'active' : '' }}">
            <i class="fas fa-sign-in-alt"></i>
            <span>Sign In</span>
        </a>
        @endauth
    </nav>

    <!-- Floating AI Assistant Button (FAB) -->
    <button type="button" class="ai-fab-btn" onclick="openAiHelpModal()" title="AI Help & Support Panel" aria-label="AI Help Panel">
        <i class="fas fa-robot"></i>
    </button>

    <!-- AI Help Assistant & Support Panel Modal -->
    <div class="modal fade" id="aiHelpModal" tabindex="-1" aria-labelledby="aiHelpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #1e1b4b, #2563eb);">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-white text-primary p-2 rounded-circle fs-5 d-flex align-items-center justify-content-center" style="width:38px; height:38px;">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-white" id="aiHelpModalLabel">JatraPoth AI Smart Assistant</h5>
                            <small class="text-white-50">24/7 Intelligent Passenger QnA & Support</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-3">
                    <!-- Nav Tabs for QnA vs Direct Contacts -->
                    <ul class="nav nav-pills nav-justified mb-3" id="aiPanelTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold py-2" id="ai-chat-tab" data-bs-toggle="pill" data-bs-target="#ai-chat-pane" type="button" role="tab">
                                <i class="fas fa-comments me-1"></i> Instant AI QnA
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold py-2" id="ai-contacts-tab" data-bs-toggle="pill" data-bs-target="#ai-contacts-pane" type="button" role="tab">
                                <i class="fas fa-headset me-1"></i> Helpline & Contacts
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="aiPanelTabsContent">
                        <!-- AI Chat & QnA Pane -->
                        <div class="tab-pane fade show active" id="ai-chat-pane" role="tabpanel">
                            <!-- Popular Quick Question Chips -->
                            <div class="mb-2">
                                <small class="text-muted fw-bold d-block mb-1"><i class="fas fa-bolt text-warning me-1"></i> Tap for instant answers:</small>
                                <button type="button" class="qna-chip" onclick="askAiQuestion('How do I swap my seat with another passenger?')">🔁 Seat Swap Guide</button>
                                <button type="button" class="qna-chip" onclick="askAiQuestion('How do I download my PDF E-Ticket?')">📄 PDF Ticket Download</button>
                                <button type="button" class="qna-chip" onclick="askAiQuestion('What is the refund and cancellation policy?')">💸 Refund Policy</button>
                                <button type="button" class="qna-chip" onclick="askAiQuestion('How do seat ratings and reviews work?')">⭐ Seat Rating System</button>
                                <button type="button" class="qna-chip" onclick="askAiQuestion('What payment methods are supported?')">💳 Payment Methods</button>
                            </div>

                            <!-- Chat Messages Container -->
                            <div class="ai-chat-box mb-3" id="aiChatContainer">
                                <div class="chat-msg bot">
                                    <div class="msg-bubble">
                                        👋 Hello! I am your <strong>JatraPoth AI Assistant</strong>. How can I help you today? Select a quick question above or type your inquiry below!
                                    </div>
                                </div>
                            </div>

                            <!-- User Input Area -->
                            <div class="input-group">
                                <input type="text" id="aiUserInput" class="form-control" placeholder="Ask anything about booking, seats, refund..." onkeypress="if(event.key==='Enter') sendAiMessage()">
                                <button type="button" class="btn btn-primary-touch" onclick="sendAiMessage()">
                                    <i class="fas fa-paper-plane me-1"></i> Ask
                                </button>
                            </div>
                        </div>

                        <!-- Contacts & Support Pane -->
                        <div class="tab-pane fade" id="ai-contacts-pane" role="tabpanel">
                            <div class="p-3 bg-light rounded-3">
                                <h6 class="fw-bold text-primary mb-3"><i class="fas fa-phone-alt me-2"></i>Official Helpline & Support Channels</h6>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="p-3 bg-white border rounded-3 h-100">
                                            <div class="text-success fs-4 mb-1"><i class="fas fa-headset"></i></div>
                                            <div class="fw-bold text-dark">24/7 Passenger Call Center</div>
                                            <div class="text-muted small mb-2">Toll-free hotline for urgent travel queries</div>
                                            <a href="tel:+880199546531" class="btn btn-sm btn-outline-success w-100 fw-bold">
                                                <i class="fas fa-phone me-1"></i> Call +880 1995-46531
                                            </a>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="p-3 bg-white border rounded-3 h-100">
                                            <div class="text-success fs-4 mb-1"><i class="fab fa-whatsapp"></i></div>
                                            <div class="fw-bold text-dark">WhatsApp Instant Chat</div>
                                            <div class="text-muted small mb-2">Instant messaging support & ticket copies</div>
                                            <a href="https://wa.me/880199546531" target="_blank" class="btn btn-sm btn-success w-100 fw-bold">
                                                <i class="fab fa-whatsapp me-1"></i> Chat on WhatsApp
                                            </a>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="p-3 bg-white border rounded-3 h-100">
                                            <div class="text-primary fs-4 mb-1"><i class="fas fa-envelope"></i></div>
                                            <div class="fw-bold text-dark">Email Support</div>
                                            <div class="text-muted small mb-2">Send details, payment receipts & refunds</div>
                                            <a href="mailto:hafizursiam@gmail.com" class="btn btn-sm btn-outline-primary w-100 fw-bold">
                                                <i class="fas fa-envelope me-1"></i> hafizursiam@gmail.com
                                            </a>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="p-3 bg-white border rounded-3 h-100">
                                            <div class="text-danger fs-4 mb-1"><i class="fas fa-building"></i></div>
                                            <div class="fw-bold text-dark">Central Helpdesk</div>
                                            <div class="text-muted small mb-2">Counter 12, Mohakhali & Gabtoli Bus Terminals, Dhaka</div>
                                            <span class="badge bg-secondary">Open 6 AM - 11 PM</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live Real-Time Clock with Seconds
        function updateLiveClock() {
            const now = new Date();
            const options = { 
                weekday: 'short', 
                year: 'numeric', 
                month: 'short', 
                day: '2-digit',
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit',
                hour12: true 
            };
            const clockEl = document.getElementById('liveDateTimeClock');
            if (clockEl) {
                clockEl.innerText = now.toLocaleString('en-US', options);
            }
        }
        setInterval(updateLiveClock, 1000);
        updateLiveClock();

        // AI Knowledge Base QnA Dictionary
        const aiKnowledgeBase = {
            "swap": "🔄 <strong>Official Seat Swapping Guide:</strong><br>1. Go to <strong>My Tickets</strong> or <strong>Seat Swaps</strong> in the navigation.<br>2. Click <strong>Request Seat Swap</strong> on your confirmed ticket.<br>3. Choose your seat and select the passenger's seat you want.<br>4. Once the target passenger accepts, both tickets are automatically updated with new seats!",
            "pdf": "📄 <strong>PDF E-Ticket Download:</strong><br>1. Open <strong>My Tickets</strong> from the menu.<br>2. Click <strong>Download PDF</strong> on your ticket card.<br>3. Print or save the PDF boarding pass with QR code on your mobile device to present at boarding!",
            "refund": "💸 <strong>Refund & Cancellation Policy:</strong><br>1. Click <strong>Request Refund</strong> on your ticket in <strong>My Tickets</strong>.<br>2. Trips cancelled >24 hours before departure receive up to 90% refund.<br>3. Once requested, admin processes the refund directly to your payment account within 24 hours.",
            "rating": "⭐ <strong>Seat Rating & Reviews:</strong><br>1. You can rate seats after completing a trip via <strong>My Tickets -> Rate This Trip</strong>.<br>2. You can view passenger seat reviews before booking by clicking <strong>View Seat Ratings & Reviews</strong> on any bus search card!",
            "payment": "💳 <strong>Supported Payment Gateways:</strong><br>We support bKash, Nagad, Rocket, Visa, Mastercard, and SSLCommerz net banking for instant 100% secure ticket bookings."
        };

        function openAiHelpModal() {
            const modal = new bootstrap.Modal(document.getElementById('aiHelpModal'));
            modal.show();
        }

        function askAiQuestion(questionText) {
            appendUserMsg(questionText);
            processAiQuery(questionText);
        }

        function sendAiMessage() {
            const input = document.getElementById('aiUserInput');
            const text = input.value.trim();
            if (!text) return;
            appendUserMsg(text);
            input.value = '';
            processAiQuery(text);
        }

        function appendUserMsg(text) {
            const container = document.getElementById('aiChatContainer');
            container.innerHTML += `
                <div class="chat-msg user">
                    <div class="msg-bubble">${escapeHtml(text)}</div>
                </div>
            `;
            container.scrollTop = container.scrollHeight;
        }

        function processAiQuery(text) {
            const container = document.getElementById('aiChatContainer');
            const lower = text.toLowerCase();
            
            let response = "🤖 I can help with ticket booking, seat swapping, PDF e-tickets, refunds, and ratings. Please tap one of the quick question chips above or call our helpline at <strong>+880 1995-46531</strong>!";
            
            if (lower.includes('swap')) response = aiKnowledgeBase["swap"];
            else if (lower.includes('pdf') || lower.includes('download') || lower.includes('ticket')) response = aiKnowledgeBase["pdf"];
            else if (lower.includes('refund') || lower.includes('cancel')) response = aiKnowledgeBase["refund"];
            else if (lower.includes('rating') || lower.includes('review') || lower.includes('star')) response = aiKnowledgeBase["rating"];
            else if (lower.includes('payment') || lower.includes('bkash') || lower.includes('card')) response = aiKnowledgeBase["payment"];

            setTimeout(() => {
                container.innerHTML += `
                    <div class="chat-msg bot">
                        <div class="msg-bubble">${response}</div>
                    </div>
                `;
                container.scrollTop = container.scrollHeight;
            }, 400);
        }

        function escapeHtml(str) {
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        }

        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                if (bsAlert) bsAlert.close();
            });
        }, 5000);
    </script>
    @yield('scripts')
</body>

</html>
