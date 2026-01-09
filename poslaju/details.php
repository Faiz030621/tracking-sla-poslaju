<?php require_once 'config.php'; if (!isLoggedIn()) { header('Location: index.php'); exit(); } ?>
<!-- details.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SLA Details - Poslaju Tracking Dashboard</title>
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

        .table-row {
            transition: all 0.2s ease;
        }

        .table-row:hover {
            background: rgba(213, 23, 31, 0.05);
        }

        .pagination-btn {
            transition: all 0.2s ease;
        }

        .pagination-btn:hover {
            background: #d5171f;
            color: white;
        }

        .pagination-active {
            background: #d5171f;
            color: white;
        }

        .filter-select {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .status-on-time {
            background: #dcfce7;
            color: #166534;
        }

        .status-late {
            background: #fee2e2;
            color: #dc2626;
        }

        .status-null {
            background: #f3f4f6;
            color: #6b7280;
        }
    </style>
</head>

<body class="bg-gray-50">
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
                    <h1 class="text-2xl font-bold text-gray-900">SLA Details Dashboard</h1>
                    <p class="text-gray-600 text-sm">View all tracking records with SLA compliance</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900">Database Records</p>
                        <p class="text-xs text-gray-500" id="currentTime"></p>
                    </div>
                    <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-red-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-list text-white text-sm"></i>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="p-6">
        <?php
            // Database connection
            try {
                $pdo = new PDO("mysql:host={$db_config['host']};dbname={$db_config['database']};charset=utf8mb4", $db_config['username'], $db_config['password']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Get parameters
                $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
                $perPage = 100;
                $offset = ($page - 1) * $perPage;

                // Build query
                $whereClause = '';
                $params = [];

                if ($filter !== 'all') {
                    if ($filter === 'on_time') {
                        $whereClause = 'WHERE sla_compliance = :sla';
                        $params[':sla'] = 'On Time';
                    } elseif ($filter === 'late') {
                        $whereClause = 'WHERE sla_compliance = :sla';
                        $params[':sla'] = 'Late';
                    }
                }

                // Count total records
                $countQuery = "SELECT COUNT(*) as total FROM tracking $whereClause";
                $stmt = $pdo->prepare($countQuery);
                $stmt->execute($params);
                $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                $totalPages = ceil($totalRecords / $perPage);

                // Fetch records
                $query = "SELECT t.*, p.postcode FROM tracking t LEFT JOIN postcode p ON t.postcode_id = p.id $whereClause ORDER BY t.id DESC LIMIT $perPage OFFSET $offset";
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            } catch (PDOException $e) {
                $error = $e->getMessage();
                $records = [];
                $totalRecords = 0;
                $totalPages = 0;
            }
            ?>

            <!-- Filter and Stats -->
            <div class="card rounded-2xl p-6 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="flex items-center gap-4">
                        <h3 class="text-lg font-bold text-gray-900">Filter by SLA Status</h3>
                        <form method="GET" class="flex gap-2">
                            <select name="filter" class="filter-select px-4 py-2 rounded-xl text-sm" onchange="this.form.submit()">
                                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Records</option>
                                <option value="on_time" <?php echo $filter === 'on_time' ? 'selected' : ''; ?>>On Time</option>
                                <option value="late" <?php echo $filter === 'late' ? 'selected' : ''; ?>>Late</option>
                            </select>
                            <input type="hidden" name="page" value="1">
                        </form>
                    </div>
                    <div class="text-sm text-gray-600">
                        Showing <?php echo count($records); ?> of <?php echo $totalRecords; ?> records
                        <?php if ($filter !== 'all'): ?>
                            (filtered by <?php echo ucfirst(str_replace('_', ' ', $filter)); ?>)
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="card rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tracking No</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Postcode</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Zone</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">SLA Max Days</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Current Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Ship Date</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Delivered Date</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Est Delivered</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">SLA Compliance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (isset($error)): ?>
                                <tr>
                                    <td colspan="9" class="px-6 py-8 text-center text-red-600">
                                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                                        <div>Database Error: <?php echo htmlspecialchars($error); ?></div>
                                    </td>
                                </tr>
                            <?php elseif (empty($records)): ?>
                                <tr>
                                    <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                                        <i class="fas fa-inbox text-2xl mb-2"></i>
                                        <div>No records found</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($records as $record): ?>
                                    <tr class="table-row">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($record['tracking_no']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            <?php echo htmlspecialchars($record['postcode']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            <?php echo htmlspecialchars($record['zone'] ?? '-'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            <?php echo $record['sla_max_days'] ? htmlspecialchars($record['sla_max_days']) : '-'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            <?php echo htmlspecialchars($record['current_status'] ?? '-'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            <?php echo $record['ship_date'] ? htmlspecialchars($record['ship_date']) : '-'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            <?php echo $record['delivered_date'] ? htmlspecialchars($record['delivered_date']) : '-'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            <?php echo $record['est_deadline'] ? htmlspecialchars($record['est_deadline']) : '-'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($record['sla_compliance']): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $record['sla_compliance'] === 'On Time' ? 'status-on-time' : 'status-late'; ?>">
                                                    <?php echo htmlspecialchars($record['sla_compliance']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full status-null">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="mt-6 flex justify-center">
                    <nav class="flex items-center space-x-1">
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);

                        // Previous button
                        if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&filter=<?php echo urlencode($filter); ?>" class="pagination-btn px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&filter=<?php echo urlencode($filter); ?>"
                               class="pagination-btn px-4 py-2 rounded-lg border <?php echo $i === $page ? 'pagination-active' : 'border-gray-300 text-gray-700 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&filter=<?php echo urlencode($filter); ?>" class="pagination-btn px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Update current time
        function updateCurrentTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleString('en-MY', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateCurrentTime();
            setInterval(updateCurrentTime, 1000);

            // Mobile menu toggle
            document.getElementById('mobile-menu-btn').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('open');
            });

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
        });
    </script>
</body>
</html>
