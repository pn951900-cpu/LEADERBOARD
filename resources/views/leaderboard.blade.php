<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng Xếp Hạng Tháng</title>
    <!-- Tailwind CSS đơn giản -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 p-6">

    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow p-6">
        
        <!-- 1. HEADER: Tiêu đề -->
        <header class="text-center border-b pb-4 mb-4">
            <h1 class="text-2xl font-bold uppercase text-blue-700">Bảng Xếp Hạng Tháng</h1>
        </header>

        <!-- 2. NAVBAR: Menu chọn tháng -->
        <nav class="mb-6">
            <ul id="monthMenu" class="flex flex-wrap justify-center gap-2 border-b pb-3">
                <!-- Các nút tháng được tạo tự động từ API -->
            </ul>
        </nav>

        <!-- 3. BẢNG XẾP HẠNG: Ngang 2, Dài 5 (10 người / trang) -->
        <section class="mb-6">
            <div id="leaderboardGrid" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <!-- 10 người chơi được chia đều 2 cột, mỗi cột 5 hàng -->
            </div>
            
            <div id="emptyMessage" class="hidden text-center py-8 text-gray-500">
                Chưa có dữ liệu xếp hạng trong tháng này.
            </div>
        </section>

        <!-- 4. HẠNG CỦA TÔI (Nằm ngay dưới Bảng xếp hạng) -->
        <section class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <img id="myAvatar" src="" alt="Avatar" class="w-12 h-12 rounded-full border bg-white object-cover">
                    <div>
                        <div class="font-bold text-gray-900" id="myName">Đang tải...</div>
                        <div class="text-xs text-gray-500" id="myTime">--</div>
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    <div class="text-center">
                        <span class="text-xs text-gray-500 block">Điểm số</span>
                        <span id="myScore" class="text-lg font-bold text-blue-600">0</span>
                    </div>
                    <div class="text-center">
                        <span class="text-xs text-gray-500 block">Thứ hạng của bạn</span>
                        <span id="myRank" class="text-2xl font-extrabold text-red-500">#--</span>
                    </div>
                    <button onclick="addBonusPoints(100)" id="btnBonus" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-md shadow transition active:scale-95 flex items-center gap-1">
                        +100 Điểm (Test)
                    </button>
                </div>
            </div>
        </section>

        <!-- 5. PHÂN TRANG (Nằm dưới cùng, dưới Hạng của tôi) -->
        <footer class="flex items-center justify-between border-t pt-4 text-sm text-gray-600">
            <div id="pageSummary">Đang tải trang...</div>
            <div id="paginationButtons" class="flex items-center gap-1">
                <!-- Nút phân trang tạo động -->
            </div>
        </footer>

    </div>

    <!-- Script xử lý dữ liệu -->
    <script>
        let currentSeasonId = null;
        let currentPage = 1;
        const perPage = 10; // Ngang 2 x Dài 5 = 10 người mỗi trang

        document.addEventListener('DOMContentLoaded', () => {
            loadLeaderboard();
        });

        async function loadLeaderboard() {
            try {
                let url = `/api/v1/leaderboard?page=${currentPage}&per_page=${perPage}`;
                if (currentSeasonId) {
                    url += `&season_id=${currentSeasonId}`;
                }

                const response = await fetch(url);
                const res = await response.json();

                if (res.success && res.data) {
                    renderNavbar(res.data.current_season, res.data.available_seasons);
                    renderGrid(res.data.leaderboard);
                    renderMyRank(res.data.my_rank);
                    renderPagination(res.data.pagination);
                }
            } catch (err) {
                console.error('Lỗi khi tải bảng xếp hạng:', err);
            }
        }

        // 1. Menu Navbar chọn tháng
        function renderNavbar(currentSeason, seasons) {
            if (!seasons) return;
            const menu = document.getElementById('monthMenu');

            menu.innerHTML = seasons.map(s => {
                const isActive = s.id === currentSeason.id;
                return `
                    <li>
                        <button onclick="changeSeason(${s.id})" 
                            class="px-4 py-2 rounded-md font-medium text-sm transition ${
                                isActive 
                                    ? 'bg-blue-600 text-white shadow' 
                                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                            }">
                            ${s.name}
                        </button>
                    </li>
                `;
            }).join('');
        }

        // 2. Bảng xếp hạng dạng 2 cột x 5 hàng (Tổng 10 item)
        function renderGrid(items) {
            const grid = document.getElementById('leaderboardGrid');
            const empty = document.getElementById('emptyMessage');
            grid.innerHTML = '';

            if (!items || items.length === 0) {
                empty.classList.remove('hidden');
                return;
            }
            empty.classList.add('hidden');

            items.forEach(item => {
                const isMe = item.is_current_user;
                const card = document.createElement('div');
                card.className = `flex items-center justify-between p-3 border rounded-lg ${
                    isMe ? 'bg-yellow-50 border-yellow-300' : 'bg-white hover:bg-gray-50 border-gray-200'
                }`;

                let rankStyle = 'bg-gray-100 text-gray-700';
                if (item.rank === 1) rankStyle = 'bg-yellow-400 text-white font-bold';
                else if (item.rank === 2) rankStyle = 'bg-gray-300 text-gray-800 font-bold';
                else if (item.rank === 3) rankStyle = 'bg-amber-600 text-white font-bold';

                card.innerHTML = `
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 flex items-center justify-center rounded-full text-xs ${rankStyle}">
                            ${item.rank}
                        </span>
                        <img src="${item.avatar}" alt="${item.name}" class="w-9 h-9 rounded-full border bg-gray-50">
                        <div>
                            <div class="font-semibold text-sm text-gray-800">
                                ${escapeHtml(item.name)} ${isMe ? '<span class="text-xs text-blue-600 font-bold">(Bạn)</span>' : ''}
                            </div>
                            <div class="text-[11px] text-gray-400">${item.achieved_at || ''}</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="font-bold text-blue-600">${item.score.toLocaleString('vi-VN')}</span>
                        <span class="text-xs text-gray-400">pts</span>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        // 3. Hạng của tôi
        function renderMyRank(myRank) {
            const myAvatar = document.getElementById('myAvatar');
            const myName = document.getElementById('myName');
            const myScore = document.getElementById('myScore');
            const myRankEl = document.getElementById('myRank');
            const myTime = document.getElementById('myTime');

            if (!myRank || !myRank.is_ranked) {
                myRankEl.textContent = 'Chưa xếp hạng';
                myScore.textContent = '0';
                myTime.textContent = 'Chưa có điểm trong tháng này';
                if (myRank?.user) {
                    myName.textContent = `${myRank.user.name} (Bạn)`;
                    myAvatar.src = myRank.user.avatar;
                }
                return;
            }

            myAvatar.src = myRank.user.avatar;
            myName.textContent = `${myRank.user.name} (Bạn)`;
            myScore.textContent = myRank.score.toLocaleString('vi-VN');
            myRankEl.textContent = `#${myRank.rank}`;
            myTime.textContent = myRank.achieved_at ? `Đạt lúc: ${myRank.achieved_at}` : '';
        }

        // 4. Phân trang
        function renderPagination(p) {
            const summary = document.getElementById('pageSummary');
            const container = document.getElementById('paginationButtons');

            const from = p.total_items === 0 ? 0 : (p.current_page - 1) * p.per_page + 1;
            const to = Math.min(p.current_page * p.per_page, p.total_items);

            summary.textContent = `Hiển thị ${from} - ${to} / ${p.total_items} người`;

            let html = '';

            // Nút Trước
            html += `
                <button onclick="goToPage(${p.current_page - 1})" ${!p.has_prev_page ? 'disabled' : ''} 
                    class="px-3 py-1 border rounded text-xs ${!p.has_prev_page ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-100'}">
                    Trước
                </button>
            `;

            // Các nút trang
            for (let i = 1; i <= p.total_pages; i++) {
                if (i === 1 || i === p.total_pages || (i >= p.current_page - 1 && i <= p.current_page + 1)) {
                    const isActive = i === p.current_page;
                    html += `
                        <button onclick="goToPage(${i})" 
                            class="w-7 h-7 border rounded text-xs font-semibold ${isActive ? 'bg-blue-600 text-white' : 'hover:bg-gray-100'}">
                            ${i}
                        </button>
                    `;
                } else if (i === p.current_page - 2 || i === p.current_page + 2) {
                    html += `<span class="px-1 text-gray-400">...</span>`;
                }
            }

            // Nút Sau
            html += `
                <button onclick="goToPage(${p.current_page + 1})" ${!p.has_next_page ? 'disabled' : ''} 
                    class="px-3 py-1 border rounded text-xs ${!p.has_next_page ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-100'}">
                    Sau
                </button>
            `;

            container.innerHTML = html;
        }

        function changeSeason(seasonId) {
            currentSeasonId = seasonId;
            currentPage = 1;
            loadLeaderboard();
        }

        function goToPage(page) {
            currentPage = page;
            loadLeaderboard();
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // Cập nhật điểm trong thời gian diễn ra chương trình
        async function addBonusPoints(points) {
            try {
                const res = await fetch('/api/v1/scores/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: 1, // Điểm của Bạn
                        season_id: currentSeasonId,
                        points: points
                    })
                });

                const data = await res.json();
                if (data.success) {
                    alert(data.message + '\nĐiểm mới: ' + data.data.new_score + ' pts');
                    loadLeaderboard(); // Tải lại bảng để thấy thứ hạng nhảy lên ngay lập tức!
                } else {
                    alert('Lỗi: ' + data.message);
                }
            } catch (err) {
                console.error('Lỗi khi cập nhật điểm:', err);
            }
        }
    </script>
</body>
</html>

