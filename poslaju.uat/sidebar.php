<style>
    .sidebar {
        background: linear-gradient(180deg, #1e293b 0%, #334155 100%);
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
    }
    
    .nav-item {
        transition: all 0.3s ease;
    }
    
    .nav-item:hover {
        background: rgba(255, 255, 255, 0.1);
        padding-left: 1.5rem;
    }
    
    .nav-item.active {
        background: linear-gradient(90deg, #d5171f 0%, #FF1111 100%);
        border-right: 4px solid #fff;
    }
    
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        
        .sidebar.open {
            transform: translateX(0);
        }
    }
</style>

<!-- Sidebar -->
<div id="sidebar" class="sidebar fixed left-0 top-0 h-full w-64 z-40 md:translate-x-0">
    <div class="p-6 border-b border-slate-600">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-transparent">
                <img src="images/logo-putih-sasia.png">
            </div>
            <div>
                <h2 class="text-white font-bold text-lg">Poslaju</h2>
                <p class="text-slate-300 text-xs">SLA Tracking System</p>
            </div>
        </div>
    </div>
    
    <nav class="p-4">
        <ul class="space-y-2">
            <li>
                <a href="admin.php" class="nav-item active flex items-center space-x-3 text-white p-3 rounded-lg">
                    <i class="fas fa-dashboard w-5"></i>
                    <span>Tracking</span>
                </a>
            </li>
            <li>
                <a href="details.php" class="nav-item flex items-center space-x-3 text-slate-300 hover:text-white p-3 rounded-lg">
                    <i class="fa-solid fa-list-ul w-5"></i>
                    <span>List SLA</span>
                </a>
            </li>
            <li>
                <a href="importbulk.php" class="nav-item flex items-center space-x-3 text-slate-300 hover:text-white p-3 rounded-lg">
                    <i class="fas fa-history w-5"></i>
                    <span>SLA Bulk Upload</span>
                </a>
            </li>
            <li>
                <a href="help.php" class="nav-item flex items-center space-x-3 text-slate-300 hover:text-white p-3 rounded-lg">
                    <i class="fas fa-question-circle w-5"></i>
                    <span>Help & Support</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- Footer Info -->
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-slate-600">
        <div class="text-center">
            <div class="text-xs text-slate-400 mb-2">Integrated Tracking System</div>
            <div class="text-xs text-slate-500">Real-time Package Updates</div>
            <div class="mt-2">
                <a href="logout.php" class="block w-full bg-red-600 hover:bg-red-700 text-white text-center py-2 rounded-lg transition-colors duration-200">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const navItems = document.querySelectorAll('.nav-item');

    // Dapatkan nama file/page sekarang
    let currentPath = window.location.pathname.split("/").pop();  

    navItems.forEach(item => {
        let link = item.getAttribute("href");

        // Kalau link sama dengan page sekarang → tambah class active
        if (link === currentPath) {
            item.classList.add("active");
        } else {
            item.classList.remove("active");
        }
    });
});
</script>
