<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poslaju Bulk Tracking Import</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link rel="icon" href="images/sasia-logo.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <?php
    // Include PhpSpreadsheet for Excel handling
    require_once 'vendor/autoload.php';
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    ?>
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

        .drag-over {
            border-color: #d5171f !important;
            background-color: #fef2f2 !important;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-success {
            background: #dcfce7;
            color: #166534;
        }

        .status-error {
            background: #fee2e2;
            color: #dc2626;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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
<body class="min-h-screen">
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
                    <h1 class="text-2xl font-bold text-gray-900">Poslaju Bulk Tracking Import</h1>
                    <p class="text-gray-600 text-sm">Import and track multiple Poslaju packages at once</p>
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
                            <p class="text-2xl font-bold text-gray-900" id="totalCount">0</p>
                            <p class="text-sm font-medium text-gray-600">Total Packages</p>
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
                            <p class="text-2xl font-bold text-gray-900" id="successCount">0</p>
                            <p class="text-sm font-medium text-gray-600">Success</p>
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
                            <p class="text-2xl font-bold text-gray-900" id="errorCount">0</p>
                            <p class="text-sm font-medium text-gray-600">Errors</p>
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
                            <p class="text-2xl font-bold text-gray-900" id="pendingCount">0</p>
                            <p class="text-sm font-medium text-gray-600">Pending</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Import Section -->
            <div class="upload-container card rounded-2xl p-6 mb-6">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Import Tracking Numbers</h2>
                    <p class="text-gray-600">Upload CSV file or paste multiple tracking numbers</p>
                </div>

                <!-- Tab Navigation -->
                <div class="flex justify-center mb-8">
                    <div class="bg-gray-100 rounded-xl p-1">
                        <button id="excelTab" class="action-btn px-6 py-3 rounded-lg font-semibold transition-all bg-red-600 text-white" onclick="switchTab('excel')">
                            <i class="fas fa-file-excel mr-2"></i>Excel Upload
                        </button>
                        <button id="textTab" class="action-btn px-6 py-3 rounded-lg font-semibold transition-all text-gray-600 hover:text-gray-900" onclick="switchTab('text')">
                            <i class="fas fa-keyboard mr-2"></i>Text Input
                        </button>
                    </div>
                </div>

                <!-- Excel Upload Tab -->
                <div id="excelSection" class="space-y-6">
                    <div
                        id="dropZone"
                        class="border-2 border-dashed border-gray-300 rounded-xl p-12 text-center transition-all hover:border-red-400 hover:bg-red-50"
                        ondrop="handleDrop(event)"
                        ondragover="handleDragOver(event)"
                        ondragleave="handleDragLeave(event)"
                    >
                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Drag & Drop Excel File</h3>
                        <p class="text-gray-600 mb-4">Or click to browse and select file</p>
                        <input type="file" id="excelFile" accept=".xlsx,.xls" onchange="handleFileSelect(event)" class="hidden">
                        <button onclick="document.getElementById('excelFile').click()" class="action-btn bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold">
                            <i class="fas fa-folder-open mr-2"></i>Choose File
                        </button>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <h4 class="font-semibold text-blue-900 mb-2">Excel Format Instructions:</h4>
                        <ul class="text-sm text-blue-800 space-y-1">
                            <li>• First column (A) should contain tracking numbers</li>
                            <li>• Second column (B) should contain postcodes</li>
                            <li>• No header row required</li>
                            <li>• Example: A1: EE123456789MY, B1: 12345</li>
                        </ul>
                    </div>

                    <div class="text-center">
                        <button onclick="downloadTemplate()" class="action-btn bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold">
                            <i class="fas fa-download mr-2"></i>Download Excel Template
                        </button>
                    </div>
                </div>

                <!-- Text Input Tab -->
                <div id="textSection" class="hidden space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">Paste Tracking Numbers and Postcodes (tracking,postcode per line):</label>
                        <textarea
                            id="trackingNumbers"
                            rows="12"
                            placeholder="EE123456789MY,12345&#10;EE987654321MY,67890&#10;EE456789123MY,54321&#10;..."
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent font-mono text-sm"
                        ></textarea>
                    </div>

                    <div class="flex justify-center">
                        <button onclick="processTextInput()" class="action-btn bg-red-600 hover:bg-red-700 text-white px-8 py-3 rounded-xl font-semibold">
                            <i class="fas fa-play mr-2"></i>Process Numbers
                        </button>
                    </div>
                </div>
            </div>

            <!-- Processing Status -->
            <div id="processingSection" class="hidden card rounded-2xl p-8 mb-8 fade-in">
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Processing Tracking Numbers</h3>
                    <p class="text-gray-600">Please wait while we fetch tracking information...</p>
                </div>

                <div class="bg-gray-200 rounded-full h-3 mb-6">
                    <div id="progressBarInner" class="bg-red-600 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-center">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="text-2xl font-bold text-gray-900" id="totalCountProcess">0</div>
                        <div class="text-sm text-gray-600">Total</div>
                    </div>
                    <div class="bg-green-50 rounded-xl p-4">
                        <div class="text-2xl font-bold text-green-600" id="successCountProcess">0</div>
                        <div class="text-sm text-gray-600">Success</div>
                    </div>
                    <div class="bg-red-50 rounded-xl p-4">
                        <div class="text-2xl font-bold text-red-600" id="errorCountProcess">0</div>
                        <div class="text-sm text-gray-600">Errors</div>
                    </div>
                    <div class="bg-yellow-50 rounded-xl p-4">
                        <div class="text-2xl font-bold text-yellow-600" id="pendingCountProcess">0</div>
                        <div class="text-sm text-gray-600">Pending</div>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <button id="cancelBtn" onclick="cancelProcessing()" class="action-btn bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold">
                        <i class="fas fa-stop mr-2"></i>Cancel Processing
                    </button>
                </div>
            </div>

            <!-- Results Section -->
            <div id="resultsSection" class="hidden card rounded-2xl p-8 fade-in">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900">Tracking Results</h3>
                        <p class="text-gray-600">Bulk import completed successfully</p>
                    </div>
                    <div class="flex space-x-4">
                        <button onclick="exportResults()" class="action-btn bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl font-semibold">
                            <i class="fas fa-download mr-2"></i>Export CSV
                        </button>
                        <button onclick="startNewImport()" class="action-btn bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl font-semibold">
                            <i class="fas fa-plus mr-2"></i>New Import
                        </button>
                    </div>
                </div>

                <!-- Results Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tracking Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Postcode</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Picked Up</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Delivered</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="resultsTable" class="bg-white divide-y divide-gray-200">
                            <!-- Results will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Modal -->
    <div id="detailModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="card bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-900">Detailed Tracking Information</h3>
                    <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div class="p-6" id="detailContent">
                <!-- Detailed content will be populated here -->
            </div>
        </div>
    </div>

    <script>
        let trackingData = [];
        let processingCancelled = false;
        let currentTab = 'csv';

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateCurrentTime();
            setInterval(updateCurrentTime, 1000);
            setupEventListeners();
        });
        
        function updateCurrentTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleString('en-MY', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        function setupEventListeners() {
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

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        function switchTab(tab) {
            currentTab = tab;
            
            // Update tab buttons
            document.getElementById('csvTab').className = tab === 'csv' 
                ? 'action-btn px-6 py-3 rounded-lg font-semibold transition-all bg-red-600 text-white'
                : 'action-btn px-6 py-3 rounded-lg font-semibold transition-all text-gray-600 hover:text-gray-900';
            
            document.getElementById('textTab').className = tab === 'text' 
                ? 'action-btn px-6 py-3 rounded-lg font-semibold transition-all bg-red-600 text-white'
                : 'action-btn px-6 py-3 rounded-lg font-semibold transition-all text-gray-600 hover:text-gray-900';
            
            // Show/hide sections
            document.getElementById('csvSection').classList.toggle('hidden', tab !== 'csv');
            document.getElementById('textSection').classList.toggle('hidden', tab !== 'text');
        }

        function handleDragOver(event) {
            event.preventDefault();
            document.getElementById('dropZone').classList.add('drag-over');
        }

        function handleDragLeave(event) {
            event.preventDefault();
            document.getElementById('dropZone').classList.remove('drag-over');
        }

        function handleDrop(event) {
            event.preventDefault();
            document.getElementById('dropZone').classList.remove('drag-over');

            const files = event.dataTransfer.files;
            if (files.length > 0 && (files[0].type === 'text/csv' || files[0].name.endsWith('.xlsx') || files[0].name.endsWith('.xls'))) {
                handleFile(files[0]);
            } else {
                showNotification('Please drop a valid CSV or Excel file.', 'error');
            }
        }

        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                handleFile(file);
            }
        }

        function handleFile(file) {
            const fileName = file.name.toLowerCase();
            const isExcel = fileName.endsWith('.xlsx') || fileName.endsWith('.xls');

            if (isExcel) {
                // Handle Excel file
                const reader = new FileReader();
                reader.onload = function(e) {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const sheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[sheetName];
                    const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

                    const trackingNumbers = [];
                    let hasErrors = false;

                    jsonData.forEach((row, index) => {
                        if (row[0] && row[1]) {
                            trackingNumbers.push({
                                tracking: String(row[0]).trim(),
                                postcode: String(row[1]).trim()
                            });
                        } else if (row[0] || row[1]) {
                            hasErrors = true;
                        }
                    });

                    if (trackingNumbers.length > 0 && !hasErrors) {
                        processTrackingNumbers(trackingNumbers);
                    } else {
                        showNotification('Excel file must contain tracking number and postcode for each row.', 'error');
                    }
                };
                reader.readAsArrayBuffer(file);
            } else {
                // Handle CSV file
                const reader = new FileReader();
                reader.onload = function(e) {
                    const csv = e.target.result;
                    const lines = csv.split('\n').filter(line => line.trim());
                    const trackingNumbers = [];
                    let hasErrors = false;

                    lines.forEach(line => {
                        const columns = line.split(',').map(col => col.trim().replace(/"/g, ''));
                        if (columns[0] && columns[1]) {
                            trackingNumbers.push({
                                tracking: columns[0],
                                postcode: columns[1]
                            });
                        } else {
                            hasErrors = true;
                        }
                    });

                    if (trackingNumbers.length > 0 && !hasErrors) {
                        processTrackingNumbers(trackingNumbers);
                    } else {
                        showNotification('CSV file must contain tracking number and postcode for each row.', 'error');
                    }
                };
                reader.readAsText(file);
            }
        }

        function processTextInput() {
            const text = document.getElementById('trackingNumbers').value.trim();
            if (!text) {
                showNotification('Please enter tracking numbers and postcodes.', 'error');
                return;
            }

            const lines = text.split('\n').filter(line => line.trim());
            const trackingNumbers = [];
            let hasErrors = false;

            lines.forEach(line => {
                const parts = line.split(',').map(part => part.trim());
                if (parts.length === 2 && parts[0] && parts[1]) {
                    trackingNumbers.push({
                        tracking: parts[0],
                        postcode: parts[1]
                    });
                } else {
                    hasErrors = true;
                }
            });

            if (trackingNumbers.length > 0 && !hasErrors) {
                processTrackingNumbers(trackingNumbers);
            } else {
                showNotification('Each line must contain tracking number and postcode separated by comma.', 'error');
            }
        }

        async function processTrackingNumbers(numbers) {
            trackingData = numbers.map(item => ({
                tracking: item.tracking,
                postcode: item.postcode,
                status: 'pending',
                data: null,
                error: null
            }));

            processingCancelled = false;
            showProcessingSection();
            updateCounts();

            try {
                // Prepare bulk request data
                const bulkData = {
                    tracking: numbers.map(item => ({
                        tracking: item.tracking,
                        postcode: item.postcode
                    }))
                };

                // Send bulk request
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(bulkData)
                });

                const bulkResult = await response.json();

                if (response.ok && bulkResult.status === 1 && bulkResult.results) {
                    // Process bulk results (summary only, no detailed data to reduce response size)
                    bulkResult.results.forEach((result, index) => {
                        if (index < trackingData.length) {
                            const item = trackingData[index];
                            if (result.status === 1) {
                                item.status = 'success';
                                // Note: Detailed tracking data not included in bulk response to prevent large JSON
                                item.data = {
                                    tracking_number: result.tracking_no,
                                    status: 'success',
                                    message: 'Tracked successfully (bulk mode - details saved to database)',
                                    data: [] // Empty array since details not in response
                                };
                            } else {
                                item.status = 'error';
                                item.error = result.message || 'Tracking failed';
                            }
                        }
                    });
                } else {
                    // Bulk request failed, mark all as error
                    trackingData.forEach(item => {
                        item.status = 'error';
                        item.error = bulkResult.message || 'Bulk tracking failed';
                    });
                }
            } catch (error) {
                // Network error, mark all as error
                trackingData.forEach(item => {
                    item.status = 'error';
                    item.error = 'Network error: ' + error.message;
                });
            }

            updateCounts();
            updateProgressBar(100);

            if (!processingCancelled) {
                showResults();
            }
        }

        function showProcessingSection() {
            document.getElementById('processingSection').classList.remove('hidden');
            document.getElementById('resultsSection').classList.add('hidden');
        }

        function updateCounts() {
            const total = trackingData.length;
            const success = trackingData.filter(item => item.status === 'success').length;
            const error = trackingData.filter(item => item.status === 'error').length;
            const pending = trackingData.filter(item => item.status === 'pending' || item.status === 'processing').length;
            
            document.getElementById('totalCount').textContent = total;
            document.getElementById('successCount').textContent = success;
            document.getElementById('errorCount').textContent = error;
            document.getElementById('pendingCount').textContent = pending;
            
            document.getElementById('totalCountProcess').textContent = total;
            document.getElementById('successCountProcess').textContent = success;
            document.getElementById('errorCountProcess').textContent = error;
            document.getElementById('pendingCountProcess').textContent = pending;
        }

        function updateProgressBar(percentage) {
            document.getElementById('progressBarInner').style.width = percentage + '%';
        }

        function cancelProcessing() {
            processingCancelled = true;
            showResults();
            showNotification('Processing cancelled.', 'info');
        }

function parseTrackingDate(dateStr) {
            if (!dateStr) return null;
            try {
                // Format: "04 Sep 2025, 01:01:00 PM"
                const parts = dateStr.split(', ');
                if (parts.length !== 2) return null;
                
                const datePart = parts[0]; // "04 Sep 2025"
                const timePart = parts[1]; // "01:01:00 PM"
                
                // Convert to proper date format
                const [day, month, year] = datePart.split(' ');
                const monthMap = {
                    'Jan': '01', 'Feb': '02', 'Mar': '03', 'Apr': '04',
                    'May': '05', 'Jun': '06', 'Jul': '07', 'Aug': '08',
                    'Sep': '09', 'Oct': '10', 'Nov': '11', 'Dec': '12'
                };
                
                const monthNum = monthMap[month];
                if (!monthNum) return null;
                
                // Create ISO format: "2025-09-04T13:01:00"
                const isoDate = `${year}-${monthNum}-${day.padStart(2, '0')}T${convertTo24Hour(timePart)}`;
                return new Date(isoDate);
            } catch (e) {
                console.error('Error parsing date:', dateStr, e);
                return null;
            }
        }
        
        function convertTo24Hour(time12h) {
            const [time, modifier] = time12h.split(' ');
            let [hours, minutes, seconds] = time.split(':');
            
            if (hours === '12') {
                hours = '00';
            }
            
            if (modifier === 'PM') {
                hours = parseInt(hours, 10) + 12;
            }
            
            return `${String(hours).padStart(2, '0')}:${minutes}:${seconds}`;
        }

        function showResults() {
            document.getElementById('processingSection').classList.add('hidden');
            document.getElementById('resultsSection').classList.remove('hidden');

            // Save to dashboard
            const successfulTracking = trackingData.filter(item => item.status === 'success');
            if (successfulTracking.length > 0 && typeof window.saveTrackingData === 'function') {
                window.saveTrackingData(successfulTracking);
            } else if (successfulTracking.length > 0) {
                // Fallback if dashboard function not available
                const existingData = JSON.parse(localStorage.getItem('trackingHistory') || '[]');
                successfulTracking.forEach(item => {
                    const existingIndex = existingData.findIndex(existing => existing.tracking === item.tracking);
                    if (existingIndex >= 0) {
                        existingData[existingIndex] = {
                            ...item,
                            trackedDate: existingData[existingIndex].trackedDate || new Date().toISOString(),
                            lastRefresh: new Date().toISOString()
                        };
                    } else {
                        existingData.push({
                            ...item,
                            trackedDate: new Date().toISOString(),
                            lastRefresh: new Date().toISOString()
                        });
                    }
                });
                localStorage.setItem('trackingHistory', JSON.stringify(existingData));
            }

            const tableBody = document.getElementById('resultsTable');
            tableBody.innerHTML = '';

            trackingData.forEach((item, index) => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50 cursor-pointer';
                row.onclick = () => showDetailModal(index);

                const statusText = item.status === 'success' ?
                    (item.data.data && item.data.data[0] ? item.data.data[0].process : 'Success') :
                    (item.status === 'error' ? item.error : 'Processing');

                let pickedUpDate = '-';
                let deliveredDate = '-';
                if (item.status === 'success' && item.data.data) {
                    let earliestPickedUp = null;
                    let latestDelivered = null;
                    item.data.data.forEach(event => {
                        const process = event.process.toLowerCase();
                        const eventDate = parseTrackingDate(event.date_time);
                        if (process.includes('picked up') && eventDate) {
                            if (!earliestPickedUp || eventDate < parseTrackingDate(earliestPickedUp)) {
                                earliestPickedUp = event.date_time;
                            }
                        }
                        if (process.includes('delivery completed') && eventDate) {
                            if (!latestDelivered || eventDate > parseTrackingDate(latestDelivered)) {
                                latestDelivered = event.date_time;
                            }
                        }
                    });
                    pickedUpDate = earliestPickedUp || '-';
                    deliveredDate = latestDelivered || '-';
                }

                const statusClass = item.status === 'success' ? 'status-success' :
                                  item.status === 'error' ? 'status-error' :
                                  item.status === 'processing' ? 'status-processing' : 'status-pending';

                row.innerHTML = `
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">${escapeHtml(item.tracking)}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">${escapeHtml(item.postcode) || '-'}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="status-badge ${statusClass}">${statusText}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">${pickedUpDate}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">${deliveredDate}</td>
                    <td class="px-6 py-4 text-sm">
                        <button onclick="event.stopPropagation(); showDetailModal(${index})" class="text-red-600 hover:text-red-800 action-btn">
                            <i class="fas fa-eye mr-1"></i>View
                        </button>
                    </td>
                `;

                tableBody.appendChild(row);
            });

            // Show dashboard link if data was saved
            if (successfulTracking.length > 0) {
                const dashboardAlert = document.createElement('div');
                dashboardAlert.className = 'mt-6 bg-blue-50 border border-blue-200 rounded-xl p-4';
                dashboardAlert.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-info-circle text-blue-500"></i>
                            <p class="text-blue-700">Successfully tracked packages have been saved to your dashboard.</p>
                        </div>
                        <a href="index.php" class="action-btn bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl font-semibold">
                            <i class="fas fa-tachometer-alt mr-2"></i>View Dashboard
                        </a>
                    </div>
                `;
                document.getElementById('resultsSection').appendChild(dashboardAlert);
            }
        }

        function showDetailModal(index) {
            const item = trackingData[index];
            document.getElementById('detailModal').classList.remove('hidden');
            
            const content = document.getElementById('detailContent');
            
            if (item.status === 'success' && item.data) {
                content.innerHTML = generateDetailHTML(item.data);
            } else {
                content.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                        <h4 class="text-xl font-bold text-gray-900 mb-2">Tracking Failed</h4>
                        <p class="text-gray-600">${escapeHtml(item.error || 'No tracking information available')}</p>
                    </div>
                `;
            }
        }

        function closeDetailModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }

        function generateDetailHTML(data) {
            let html = `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <h4 class="font-semibold text-gray-900 mb-2">Tracking Number</h4>
                        <p class="text-lg font-bold text-red-600">${escapeHtml(data.tracking_number)}</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <h4 class="font-semibold text-gray-900 mb-2">Current Status</h4>
                        <p class="text-lg font-bold text-green-600">${data.data && data.data[0] ? escapeHtml(data.data[0].process) : 'N/A'}</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <h4 class="font-semibold text-gray-900 mb-2">Last Update</h4>
                        <p class="text-lg font-bold text-gray-900">${data.data && data.data[0] ? escapeHtml(data.data[0].date_time) : 'N/A'}</p>
                    </div>
                </div>
            `;
            
            if (data.data && data.data.length > 0) {
                html += `
                    <h4 class="text-xl font-bold text-gray-900 mb-4">Tracking Timeline</h4>
                    <div class="space-y-0">
                `;
                
                data.data.forEach((item, index) => {
                    const isFirst = index === 0;
                    html += `
                        <div class="timeline-item ${index === data.data.length - 1 ? 'pb-0' : ''}">
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="font-medium text-gray-900">${escapeHtml(item.process)}</div>
                                    <div class="text-xs text-gray-500 bg-white px-2 py-1 rounded">
                                        ${escapeHtml(item.date_time)}
                                    </div>
                                </div>
                                <div class="text-sm text-gray-600">${escapeHtml(item.event)}</div>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
            }
            
            return html;
        }

        function downloadTemplate() {
            const csvContent = "Tracking Number,Postcode\nEE123456789MY,12345\nEE987654321MY,67890";
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = url;
            a.download = 'tracking_template.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);

            showNotification('Template downloaded successfully!', 'success');
        }

        function exportResults() {
            if (trackingData.length === 0) {
                showNotification('No data to export.', 'error');
                return;
            }

            let csvContent = "Tracking Number,Postcode,Status,Current Process,Last Update,Start Picked Up,Delivery Complete,Error\n";

            trackingData.forEach(item => {
                const currentProcess = item.status === 'success' && item.data.data && item.data.data[0] ?
                    item.data.data[0].process : '';
                const lastUpdate = item.status === 'success' && item.data.data && item.data.data[0] ?
                    item.data.data[0].date_time : '';

                let pickedUpDate = '';
                let deliveredDate = '';
                if (item.status === 'success' && item.data.data) {
                    let earliestPickedUp = null;
                    let latestDelivered = null;
                    item.data.data.forEach(event => {
                        const process = event.process.toLowerCase();
                        if (process.includes('picked up')) {
                            if (!earliestPickedUp || new Date(event.date_time) < new Date(earliestPickedUp)) {
                                earliestPickedUp = event.date_time;
                            }
                        }
if (process.includes('delivery completed')) {
    if (!latestDelivered || new Date(event.date_time) > new Date(latestDelivered)) {
        latestDelivered = event.date_time;
    }
}
                    });
                    pickedUpDate = earliestPickedUp || '';
                    deliveredDate = latestDelivered || '';
                }

                csvContent += `"${item.tracking}","${item.postcode}","${item.status}","${currentProcess}","${lastUpdate}","${pickedUpDate}","${deliveredDate}","${item.error || ''}"\n`;
            });

            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = url;
            a.download = `tracking_results_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);

            showNotification('Results exported successfully!', 'success');
        }

        function startNewImport() {
            trackingData = [];
            document.getElementById('processingSection').classList.add('hidden');
            document.getElementById('resultsSection').classList.add('hidden');
            document.getElementById('csvFile').value = '';
            document.getElementById('trackingNumbers').value = '';
            updateCounts();
            showNotification('Ready for new import.', 'info');
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

        // Close modal when clicking outside
        document.getElementById('detailModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDetailModal();
            }
        });

        // Handle escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDetailModal();
            }
        });
    </script>
</body>
</html>