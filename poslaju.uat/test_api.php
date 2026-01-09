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
    <title>API Test Page</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body class="min-h-screen p-6">
    <div class="max-w-4xl mx-auto">
        <div class="card rounded-2xl p-8 mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">API Test Page</h1>
            <p class="text-gray-600 mb-8">Enter a tracking number and postcode to test the API response.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tracking Number</label>
                    <input type="text" id="trackingNumber" placeholder="EE123456789MY" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Postcode</label>
                    <input type="text" id="postcode" placeholder="12345" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent">
                </div>
            </div>

            <div class="flex gap-4 mb-8">
                <button onclick="testAPI()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold">
                    <i class="fas fa-play mr-2"></i>Test API
                </button>
                <button onclick="clearResults()" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-xl font-semibold">
                    <i class="fas fa-trash mr-2"></i>Clear
                </button>
            </div>

            <div id="loading" class="hidden text-center py-8">
                <div class="loading loading-large mb-4"></div>
                <p class="text-gray-600">Testing API...</p>
            </div>

            <div id="results" class="hidden">
                <h2 class="text-xl font-bold text-gray-900 mb-4">API Response</h2>
                <div class="bg-gray-100 rounded-xl p-4">
                    <pre id="responseData" class="text-sm text-gray-800"></pre>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function testAPI() {
            const tracking = document.getElementById('trackingNumber').value.trim();
            const postcode = document.getElementById('postcode').value.trim();

            if (!tracking || !postcode) {
                alert('Please enter both tracking number and postcode');
                return;
            }

            document.getElementById('loading').classList.remove('hidden');
            document.getElementById('results').classList.add('hidden');

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        tracking: [{
                            tracking: tracking,
                            postcode: postcode
                        }]
                    })
                });

                const data = await response.json();
                document.getElementById('responseData').textContent = JSON.stringify(data, null, 2);
                document.getElementById('results').classList.remove('hidden');
            } catch (error) {
                document.getElementById('responseData').textContent = 'Error: ' + error.message;
                document.getElementById('results').classList.remove('hidden');
            }

            document.getElementById('loading').classList.add('hidden');
        }

        function clearResults() {
            document.getElementById('trackingNumber').value = '';
            document.getElementById('postcode').value = '';
            document.getElementById('results').classList.add('hidden');
            document.getElementById('responseData').textContent = '';
        }
    </script>
</body>
</html>
