<?php require_once 'config.php'; if (!isLoggedIn()) { header('Location: index.php'); exit(); } ?>
<!-- index.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poslaju Tracking Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link rel="icon" href="images/sasia-logo.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        
        .main-content {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }
        
        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .tracking-item {
            transition: all 0.2s ease;
        }
        
        .tracking-item:hover {
            background: rgba(213, 23, 31, 0.05);
        }
        
        .action-btn {
            transition: all 0.2s ease;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .upload-container {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
        }
        
        .modal {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .tracking-events {
            display: none;
            margin-top: 15px;
        }

        .tracking-events.show {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #d5171f;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .loading-large {
            width: 24px;
            height: 24px;
            border-width: 3px;
        }

        .loading-pulse {
            animation: pulse 2s infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, #d5171f, #FF1111);
            transition: width 0.3s ease;
            z-index: 9999;
            opacity: 0;
        }

        .progress-bar.active {
            opacity: 1;
        }

        .timeout-warning {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-left: 4px solid #f59e0b;
        }

        .error-details {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            border-radius: 8px;
            padding: 12px;
            margin-top: 8px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-delivered {
            background: #dcfce7;
            color: #166534;
        }

        .status-transit {
            background: #fef3c7;
            color: #92400e;
        }

        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-error {
            background: #fee2e2;
            color: #dc2626;
        }

        .timeline-item {
            position: relative;
            padding-left: 2rem;
            padding-bottom: 1rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0.5rem;
            width: 0.75rem;
            height: 0.75rem;
            background: #d5171f;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #d5171f;
        }

        .timeline-item::after {
            content: '';
            position: absolute;
            left: 0.8125rem;
            top: 1.25rem;
            width: 2px;
            height: calc(100% - 0.75rem);
            background: #e5e7eb;
        }

        .timeline-item:last-child::after {
            display: none;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div id="progressBar" class="progress-bar"></div>

    <!-- Mobile Menu Button -->
    <div class="md:hidden fixed top-4 left-4 z-50">
        <button id="mobile-menu-btn" class="bg-slate-800 text-white p-3 rounded-xl shadow-lg">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="md:ml-64 min-h-screen">
        <!-- Header -->
        <header class="main-content bg-white shadow-sm border-b border-gray-200 px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Poslaju SLA Tracking System</h1>
                    <p class="text-gray-600 text-sm">Track multiple Poslaju packages with live updates</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900">Live Tracking</p>
                        <p class="text-xs text-gray-500" id="currentTime"></p>
                    </div>
                    <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-red-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white text-sm"></i>
                    </div>
                </div>
            </div>
        </header>

        <!-- Statistics Cards -->
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="card stat-card rounded-2xl p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-box text-blue-600 text-lg"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-2xl font-bold text-gray-900" id="totalPackages">0</p>
                            <p class="text-sm font-medium text-gray-600">Total Packages</p>
                        </div>
                    </div>
                </div>

                <div class="card stat-card rounded-2xl p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-clock text-yellow-600 text-lg"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-2xl font-bold text-gray-900" id="inTransitPackages">0</p>
                            <p class="text-sm font-medium text-gray-600">In Transit</p>
                        </div>
                    </div>
                </div>

                <div class="card stat-card rounded-2xl p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600 text-lg"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-2xl font-bold text-gray-900" id="deliveredPackages">0</p>
                            <p class="text-sm font-medium text-gray-600">Delivered</p>
                        </div>
                    </div>
                </div>

                <div class="card stat-card rounded-2xl p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-red-600 text-lg"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-2xl font-bold text-gray-900" id="errorPackages">0</p>
                            <p class="text-sm font-medium text-gray-600">Not Found</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Tracking Section -->
            <div class="upload-container card rounded-2xl p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Track New Package</h3>
                <form class="flex flex-col md:flex-row gap-4 items-end" id="trackingForm">
                    <div class="flex-1">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tracking Number</label>
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input
                                type="text"
                                id="trackingNumber"
                                placeholder="Enter tracking number (e.g. EB123456789MY)"
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                required
                            />
                        </div>
                    </div>
                    
                    <div class="w-full md:w-64">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Package Name (Optional)</label>
                        <input
                            type="text"
                            id="trackingName"
                            placeholder="e.g. Shopee Order #12345"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500"
                        />
                    </div>
                    
                    <div class="flex gap-2">
                        <button type="submit" class="action-btn bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-xl font-semibold" id="submitBtn">
                            <span id="submitText"><i class="fas fa-plus mr-2"></i>Track Now</span>
                            <div id="submitLoading" class="loading" style="display: none;"></div>
                        </button>
                        <button type="button" onclick="clearAll()" class="action-btn bg-gray-500 hover:bg-gray-600 text-white px-6 py-2.5 rounded-xl font-semibold">
                            <i class="fas fa-trash mr-2"></i>Clear All
                        </button>
                    </div>
                </form>
                
                <div class="mt-4 text-xs text-gray-500">
                    <p><strong>Tip:</strong> Enter a valid Poslaju tracking number to get real-time tracking updates</p>
                </div>
            </div>

            <!-- Tracking List -->
            <div class="card rounded-2xl overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-900">Live Tracking Results</h3>
                        <span class="text-sm text-gray-500" id="totalCount">No packages tracked</span>
                    </div>
                </div>
                
                <div id="trackingList" class="min-h-[200px]">
                    <div class="p-12 text-center" id="emptyState">
                        <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-500 mb-2">No tracking numbers added yet</h3>
                        <p class="text-gray-400">Add a tracking number above to get live updates</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for viewing tracking details -->
    <div id="detailModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="card bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-900" id="modalTitle">Package Details</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6" id="modalContent">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let trackingData = [];
        let currentTrackingId = 1;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateCurrentTime();
            setInterval(updateCurrentTime, 1000);
            setupEventListeners();
            loadSavedData();
        });

        function setupEventListeners() {
            document.getElementById('trackingForm').addEventListener('submit', handleTrackingSubmit);
            document.getElementById('mobile-menu-btn').addEventListener('click', toggleSidebar);
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                const sidebar = document.getElementById('sidebar');
                const mobileBtn = document.getElementById('mobile-menu-btn');
                
                if (window.innerWidth < 768 && 
                    !sidebar.contains(event.target) && 
                    !mobileBtn.contains(event.target) &&
                    sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                }
            });
        }

        function updateCurrentTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleString('en-MY', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        function showProgressBar() {
            const progressBar = document.getElementById('progressBar');
            progressBar.style.width = '0%';
            progressBar.classList.add('active');
            
            // Simulate progress
            let width = 0;
            const interval = setInterval(() => {
                width += Math.random() * 30;
                if (width >= 90) {
                    clearInterval(interval);
                    width = 90;
                }
                progressBar.style.width = width + '%';
            }, 200);
            
            return interval;
        }

        function hideProgressBar() {
            const progressBar = document.getElementById('progressBar');
            progressBar.style.width = '100%';
            setTimeout(() => {
                progressBar.classList.remove('active');
                progressBar.style.width = '0%';
            }, 300);
        }

        async function handleTrackingSubmit(e) {
            e.preventDefault();
            
            const trackingNumber = document.getElementById('trackingNumber').value.trim();
            const trackingName = document.getElementById('trackingName').value.trim() || `Package ${currentTrackingId}`;
            
            if (!trackingNumber) {
                showNotification('Please enter a tracking number', 'error');
                return;
            }

            // Check if already exists
            if (trackingData.some(item => item.trackingNumber === trackingNumber)) {
                showNotification('This tracking number is already being tracked', 'warning');
                return;
            }

            setSubmitLoading(true);
            const progressInterval = showProgressBar();
            
            try {
                const result = await fetchTrackingData(trackingNumber);
                clearInterval(progressInterval);
                hideProgressBar();
                
                const newItem = {
                    id: currentTrackingId++,
                    trackingNumber,
                    name: trackingName,
                    status: result.status === 1 ? 'success' : 'error',
                    data: result.data || [],
                    lastUpdate: new Date().toISOString(),
                    rawData: result
                };
                
                trackingData.unshift(newItem);
                updateTrackingList();
                updateStatistics();
                saveData();
                
                // Reset form
                document.getElementById('trackingForm').reset();
                
                showNotification(
                    result.status === 1 
                        ? `Successfully tracked ${trackingNumber}` 
                        : `Tracking number ${trackingNumber} not found`,
                    result.status === 1 ? 'success' : 'warning'
                );
                
            } catch (error) {
                clearInterval(progressInterval);
                hideProgressBar();
                console.error('Tracking error:', error);
                showNotification('Failed to fetch tracking data. Please try again.', 'error');
            } finally {
                setSubmitLoading(false);
            }
        }

        async function fetchTrackingData(trackingNumber) {
            const response = await fetch(`api.php?trackingNo=${encodeURIComponent(trackingNumber)}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                cache: 'no-cache'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            return await response.json();
        }

        function setSubmitLoading(loading) {
            const btn = document.getElementById('submitBtn');
            const text = document.getElementById('submitText');
            const loadingSpinner = document.getElementById('submitLoading');
            
            if (loading) {
                btn.disabled = true;
                text.style.display = 'none';
                loadingSpinner.style.display = 'inline-block';
                document.getElementById('apiStatus').innerHTML = `
                    <div class="w-2 h-2 bg-yellow-400 rounded-full mr-1 loading-pulse"></div>
                    Fetching Data...
                `;
                document.getElementById('apiStatus').className = 'inline-flex items-center px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800';
            } else {
                btn.disabled = false;
                text.style.display = 'inline';
                loadingSpinner.style.display = 'none';
                document.getElementById('apiStatus').innerHTML = `
                    <div class="w-2 h-2 bg-green-400 rounded-full mr-1"></div>
                    Ready to Track
                `;
                document.getElementById('apiStatus').className = 'inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800';
            }
        }

        function updateTrackingList() {
            const container = document.getElementById('trackingList');
            const emptyState = document.getElementById('emptyState');
            const totalCount = document.getElementById('totalCount');
            
            if (trackingData.length === 0) {
                emptyState.style.display = 'block';
                totalCount.textContent = 'No packages tracked';
                return;
            }
            
            emptyState.style.display = 'none';
            totalCount.textContent = `${trackingData.length} package${trackingData.length > 1 ? 's' : ''} tracked`;
            
            container.innerHTML = trackingData.map(item => createTrackingItem(item)).join('');
        }

        function createTrackingItem(item) {
            const statusInfo = getStatusInfo(item);
            const latestUpdate = item.data && item.data[0] ? item.data[0] : null;
            
            return `
                <div class="tracking-item border-b border-gray-100 p-6">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h4 class="font-semibold text-gray-900">${escapeHtml(item.name)}</h4>
                                <span class="status-badge ${statusInfo.class}">${statusInfo.text}</span>
                            </div>
                            <div class="text-sm text-gray-600 mb-1">
                                <i class="fas fa-hashtag mr-2"></i>${escapeHtml(item.trackingNumber)}
                            </div>
                            ${latestUpdate ? `
                                <div class="text-sm text-gray-600">
                                    <i class="fas fa-map-marker-alt mr-2"></i>${escapeHtml(latestUpdate.process)}
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    Last update: ${escapeHtml(latestUpdate.date_time)}
                                </div>
                            ` : ''}
                        </div>
                        
                        <div class="flex items-center gap-2">
                            ${item.status === 'success' ? `
                                <button onclick="viewDetails(${item.id})" class="action-btn bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                                    <i class="fas fa-eye mr-2"></i>View Details
                                </button>
                            ` : ''}
                            <button onclick="refreshItem(${item.id})" class="action-btn bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm">
                                <i class="fas fa-sync-alt mr-2"></i>Refresh
                            </button>
                            <button onclick="removeItem(${item.id})" class="action-btn bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">
                                <i class="fas fa-trash mr-2"></i>Remove
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        function getStatusInfo(item) {
            if (item.status === 'error') {
                return { class: 'status-error', text: 'Not Found' };
            }
            
            if (!item.data || item.data.length === 0) {
                return { class: 'status-error', text: 'No Data' };
            }
            
            const latestStatus = item.data[0].process.toLowerCase();
            
            if (latestStatus.includes('delivered') || latestStatus.includes('completed')) {
                return { class: 'status-delivered', text: 'Delivered' };
            } else if (latestStatus.includes('transit') || latestStatus.includes('transfer')) {
                return { class: 'status-transit', text: 'In Transit' };
            } else {
                return { class: 'status-processing', text: 'Processing' };
            }
        }

        function updateStatistics() {
            const total = trackingData.length;
            let delivered = 0;
            let inTransit = 0;
            let errors = 0;
            
            trackingData.forEach(item => {
                const statusInfo = getStatusInfo(item);
                if (statusInfo.class === 'status-delivered') delivered++;
                else if (statusInfo.class === 'status-transit') inTransit++;
                else if (statusInfo.class === 'status-error') errors++;
            });
            
            document.getElementById('totalPackages').textContent = total;
            document.getElementById('deliveredPackages').textContent = delivered;
            document.getElementById('inTransitPackages').textContent = inTransit;
            document.getElementById('errorPackages').textContent = errors;
        }

        async function refreshItem(itemId) {
            const item = trackingData.find(i => i.id === itemId);
            if (!item) return;
            
            showNotification('Refreshing tracking data...', 'info');
            
            try {
                const result = await fetchTrackingData(item.trackingNumber);
                
                // Update item data
                item.status = result.status === 1 ? 'success' : 'error';
                item.data = result.data || [];
                item.lastUpdate = new Date().toISOString();
                item.rawData = result;
                
                updateTrackingList();
                updateStatistics();
                saveData();
                
                showNotification(`${item.trackingNumber} updated successfully`, 'success');
                
            } catch (error) {
                console.error('Refresh error:', error);
                showNotification('Failed to refresh tracking data', 'error');
            }
        }

        function removeItem(itemId) {
            if (confirm('Are you sure you want to remove this tracking item?')) {
                trackingData = trackingData.filter(item => item.id !== itemId);
                updateTrackingList();
                updateStatistics();
                saveData();
                showNotification('Tracking item removed', 'info');
            }
        }

        function viewDetails(itemId) {
            const item = trackingData.find(i => i.id === itemId);
            if (!item || item.status !== 'success') return;
            
            const modal = document.getElementById('detailModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalContent = document.getElementById('modalContent');
            
            modalTitle.textContent = `${item.name} - ${item.trackingNumber}`;
            
            modalContent.innerHTML = `
                <div class="mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600">Tracking Number</div>
                            <div class="font-semibold text-gray-900">${escapeHtml(item.trackingNumber)}</div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600">Package Name</div>
                            <div class="font-semibold text-gray-900">${escapeHtml(item.name)}</div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600">Current Status</div>
                            <div class="font-semibold text-gray-900">${item.data[0] ? escapeHtml(item.data[0].process) : 'No data'}</div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600">Last Updated</div>
                            <div class="font-semibold text-gray-900">${new Date(item.lastUpdate).toLocaleString('en-MY')}</div>
                        </div>
                    </div>
                    
                    ${item.data && item.data.length > 0 ? `
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h4 class="font-semibold text-gray-900 mb-4">Tracking Timeline</h4>
                            <div class="space-y-0">
                                ${item.data.map((event, index) => `
                                    <div class="timeline-item ${index === item.data.length - 1 ? 'pb-0' : ''}">
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <div class="flex justify-between items-start mb-2">
                                                <div class="font-medium text-gray-900">${escapeHtml(event.process)}</div>
                                                <div class="text-xs text-gray-500 bg-white px-2 py-1 rounded">
                                                    ${escapeHtml(event.date_time)}
                                                </div>
                                            </div>
                                            <div class="text-sm text-gray-600">${escapeHtml(event.event)}</div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : `
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-exclamation-circle text-4xl mb-3"></i>
                            <div>No tracking events available</div>
                        </div>
                    `}
                </div>
                
                <div class="flex justify-end gap-3 border-t border-gray-200 pt-4">
                    <button onclick="refreshItem(${item.id}); closeModal();" class="action-btn bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                        <i class="fas fa-sync-alt mr-2"></i>Refresh Data
                    </button>
                    <button onclick="exportItemData(${item.id})" class="action-btn bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg">
                        <i class="fas fa-download mr-2"></i>Export
                    </button>
                    <button onclick="closeModal()" class="action-btn bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                        Close
                    </button>
                </div>
            `;
            
            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }

        function exportItemData(itemId) {
            const item = trackingData.find(i => i.id === itemId);
            if (!item) return;
            
            const exportData = {
                trackingNumber: item.trackingNumber,
                packageName: item.name,
                exportDate: new Date().toISOString(),
                status: item.status,
                lastUpdate: item.lastUpdate,
                trackingEvents: item.data || [],
                metadata: {
                    totalEvents: item.data ? item.data.length : 0,
                    currentStatus: item.data && item.data[0] ? item.data[0].process : 'No data'
                }
            };
            
            const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `tracking_${item.trackingNumber}_${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            showNotification('Tracking data exported successfully!', 'success');
        }

        function clearAll() {
            if (trackingData.length === 0) {
                showNotification('No tracking data to clear', 'info');
                return;
            }
            
            if (confirm(`Are you sure you want to remove all ${trackingData.length} tracking items?`)) {
                trackingData = [];
                updateTrackingList();
                updateStatistics();
                saveData();
                showNotification('All tracking data cleared', 'info');
            }
        }

        function saveData() {
            try {
                localStorage.setItem('poslaju_tracking_data', JSON.stringify({
                    data: trackingData,
                    lastSaved: new Date().toISOString(),
                    version: '2.0'
                }));
            } catch (error) {
                console.warn('Failed to save data to localStorage:', error);
            }
        }

        function loadSavedData() {
            try {
                const saved = localStorage.getItem('poslaju_tracking_data');
                if (saved) {
                    const parsed = JSON.parse(saved);
                    if (parsed.data && Array.isArray(parsed.data)) {
                        trackingData = parsed.data;
                        currentTrackingId = Math.max(...trackingData.map(item => item.id), 0) + 1;
                        updateTrackingList();
                        updateStatistics();
                    }
                }
            } catch (error) {
                console.warn('Failed to load saved data:', error);
            }
        }

        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform translate-x-0 transition-all duration-300`;
            
            const colors = {
                success: 'bg-green-100 border border-green-200 text-green-800',
                error: 'bg-red-100 border border-red-200 text-red-800',
                warning: 'bg-yellow-100 border border-yellow-200 text-yellow-800',
                info: 'bg-blue-100 border border-blue-200 text-blue-800'
            };
            
            const icons = {
                success: 'fas fa-check-circle',
                error: 'fas fa-times-circle',
                warning: 'fas fa-exclamation-triangle',
                info: 'fas fa-info-circle'
            };
            
            notification.className += ` ${colors[type] || colors.info}`;
            notification.innerHTML = `
                <div class="flex items-center space-x-2">
                    <i class="${icons[type] || icons.info}"></i>
                    <span class="text-sm font-medium">${escapeHtml(message)}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 5000);
        }

        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Global functions
        window.viewDetails = viewDetails;
        window.closeModal = closeModal;
        window.refreshItem = refreshItem;
        window.removeItem = removeItem;
        window.clearAll = clearAll;
        window.exportItemData = exportItemData;

        // Handle modal background click
        document.getElementById('detailModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Handle escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
