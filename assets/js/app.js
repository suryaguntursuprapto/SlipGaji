// ============================================
// SLIP GAJI SYSTEM - APP.JS
// Spicy Lips x Bergamot Koffie
// ============================================

const App = {
    currentPage: 'dashboard',

    init() {
        if (!window._isLoggedIn) return; // Don't init app if not logged in
        this.bindNavigation();
        const hash = window.location.hash.replace('#', '') || 'dashboard';
        this.loadPage(hash, false);
    },

    // ============ AUTH ============
    async doLogin(e) {
        e.preventDefault();
        const btn = document.getElementById('loginBtn');
        const errDiv = document.getElementById('loginError');
        btn.disabled = true;
        btn.textContent = '⏳ Memproses...';
        errDiv.style.display = 'none';

        try {
            const r = await (await fetch('api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username: document.getElementById('login_username').value,
                    password: document.getElementById('login_password').value
                })
            })).json();

            if (r.success) {
                window.location.reload();
            } else {
                errDiv.textContent = r.message;
                errDiv.style.display = 'block';
                btn.disabled = false;
                btn.textContent = '🔐 Masuk';
            }
        } catch (err) {
            errDiv.textContent = 'Gagal terhubung ke server';
            errDiv.style.display = 'block';
            btn.disabled = false;
            btn.textContent = '🔐 Masuk';
        }
    },

    async doLogout() {
        if (!confirm('Yakin ingin logout?')) return;
        await fetch('api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'logout' })
        });
        window.location.reload();
    },

    togglePassword() {
        const input = document.getElementById('login_password');
        input.type = input.type === 'password' ? 'text' : 'password';
    },

    bindNavigation() {
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                this.loadPage(item.dataset.page);
            });
        });
        window.addEventListener('hashchange', () => {
            this.loadPage(window.location.hash.replace('#', '') || 'dashboard', false);
        });
    },

    loadPage(page, updateHash = true) {
        this.currentPage = page;
        // Highlight parent nav
        const baseNav = page.split('/')[0];
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        const nav = document.querySelector(`[data-page="${baseNav}"]`);
        if (nav) nav.classList.add('active');
        if (updateHash) window.location.hash = page;

        const c = document.getElementById('page-content');
        c.innerHTML = '<div class="page-loading"><div class="spinner"></div></div>';

        // Route
        if (page === 'dashboard') this.renderDashboard(c);
        else if (page === 'karyawan') this.renderKaryawan(c);
        else if (page === 'karyawan/tambah') this.renderKaryawanForm(c);
        else if (page.startsWith('karyawan/edit/')) this.renderKaryawanForm(c, page.split('/')[2]);
        else if (page === 'slip-gaji') this.renderSlipGaji(c);
        else if (page === 'slip-gaji/tambah') this.renderSlipForm(c);
        else if (page.startsWith('slip-gaji/edit/')) this.renderSlipForm(c, page.split('/')[2]);
        else if (page === 'kirim-email') this.renderKirimEmail(c);
        else if (page === 'pendapatan') this.renderKomponen(c, 'pendapatan');
        else if (page === 'pendapatan/tambah-kategori') this.renderKategoriForm(c, 'pendapatan');
        else if (page.startsWith('pendapatan/edit-kategori/')) this.renderKategoriForm(c, 'pendapatan', page.split('/')[2]);
        else if (page === 'pendapatan/tambah') this.renderKomponenForm(c, 'pendapatan');
        else if (page.startsWith('pendapatan/edit/')) this.renderKomponenForm(c, 'pendapatan', page.split('/')[2]);
        else if (page === 'potongan') this.renderKomponen(c, 'potongan');
        else if (page === 'potongan/tambah-kategori') this.renderKategoriForm(c, 'potongan');
        else if (page.startsWith('potongan/edit-kategori/')) this.renderKategoriForm(c, 'potongan', page.split('/')[2]);
        else if (page === 'potongan/tambah') this.renderKomponenForm(c, 'potongan');
        else if (page.startsWith('potongan/edit/')) this.renderKomponenForm(c, 'potongan', page.split('/')[2]);
        else if (page === 'pengaturan') this.renderPengaturan(c);
        else c.innerHTML = '<div class="page-header"><h1>404</h1><p>Halaman tidak ditemukan</p></div>';
    },

    // ============ DASHBOARD ============
    async renderDashboard(c) {
        try {
            const d = await (await fetch('api/dashboard.php')).json();
            c.innerHTML = `
            <div class="page-header"><h1>📊 Dashboard</h1><p>Ringkasan sistem slip gaji</p></div>
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon">👥</div><div class="stat-value">${d.total_karyawan}</div><div class="stat-label">Total Karyawan</div></div>
                <div class="stat-card"><div class="stat-icon">📄</div><div class="stat-value">${d.total_slip}</div><div class="stat-label">Total Slip Gaji</div></div>
                <div class="stat-card"><div class="stat-icon">✉️</div><div class="stat-value">${d.total_email_sent}</div><div class="stat-label">Email Terkirim</div></div>
                <div class="stat-card accent"><div class="stat-icon">📅</div><div class="stat-value">${this.getMonthName(d.bulan)} ${d.tahun}</div><div class="stat-label">Periode Saat Ini</div></div>
            </div>
            <div class="card"><div class="card-inner">
                <h3>📋 Slip Gaji Terbaru</h3>
                ${!d.recent_slips.length ? '<div class="empty-state"><p>Belum ada slip gaji</p></div>' :
                    `<div class="table-wrapper"><table><thead><tr><th>NIP</th><th>Nama</th><th>Periode</th><th>Gaji Bersih</th><th>Status</th></tr></thead><tbody>
                ${d.recent_slips.map(s => `<tr><td>${s.nik}</td><td>${s.nama}</td><td>${this.getMonthName(s.bulan)} ${s.tahun}</td>
                <td><strong class="text-success">${this.formatRupiah(s.gaji_bersih)}</strong></td>
                <td>${s.email_sent ? '<span class="badge badge-success">Terkirim</span>' : '<span class="badge badge-warning">Draft</span>'}</td></tr>`).join('')}
                </tbody></table></div>`}
            </div></div>`;
        } catch (e) { c.innerHTML = '<div class="card"><div class="card-inner"><p>Gagal memuat dashboard</p></div></div>'; }
    },

    // ============ KARYAWAN LIST ============
    async renderKaryawan(c) {
        c.innerHTML = `
        <div class="page-header-row">
            <div><h1>👥 Data Karyawan</h1><p>Kelola data karyawan perusahaan</p></div>
            <div class="header-actions">
                <input type="text" class="form-control form-select-sm" id="searchKaryawan" placeholder="🔍 Cari NIP, Nama, Jabatan..." oninput="App.filterTable('karyawanTable', this.value)">
                <button class="btn btn-primary" onclick="App.loadPage('karyawan/tambah')">➕ Tambah Karyawan</button>
            </div>
        </div>
        <div class="card"><div class="card-inner"><div id="karyawanTable"><div class="page-loading"><div class="spinner"></div></div></div></div></div>`;

        const list = await (await fetch('api/karyawan.php')).json();
        const t = document.getElementById('karyawanTable');
        if (!list.length) { t.innerHTML = '<div class="empty-state"><div class="empty-icon">👥</div><p>Belum ada data karyawan</p></div>'; return; }
        t.innerHTML = `<div class="table-wrapper"><table><thead><tr><th>NIP</th><th>Nama</th><th>Email</th><th>Jabatan</th><th>Bank</th><th style="width:100px">Aksi</th></tr></thead><tbody>
            ${list.map(k => `<tr><td><strong>${k.nik}</strong></td><td>${k.nama}</td><td>${k.email}</td><td>${k.jabatan || '-'}</td><td>${k.bank ? (k.bank + (k.no_rekening ? ' - ' + k.no_rekening : '')) : '-'}</td>
            <td><div class="action-btns"><button class="btn btn-sm btn-info" onclick="App.loadPage('karyawan/edit/${k.id}')">✏️</button>
            <button class="btn btn-sm btn-danger" onclick="App.deleteKaryawan(${k.id})">🗑️</button></div></td></tr>`).join('')}</tbody></table></div>`;
    },

    // ============ KARYAWAN FORM (Separate Page) ============
    async renderKaryawanForm(c, id = null) {
        let data = {};
        if (id) { data = await (await fetch(`api/karyawan.php?id=${id}`)).json(); }
        c.innerHTML = `
        <div class="page-header-row">
            <div><h1>${id ? '✏️ Edit Karyawan' : '➕ Tambah Karyawan'}</h1><p>Isi data karyawan di bawah ini</p></div>
            <button class="btn btn-outline" onclick="App.loadPage('karyawan')">← Kembali</button>
        </div>
        <div class="card"><div class="card-inner">
            <form id="karyawanForm" onsubmit="App.saveKaryawan(event)">
                <input type="hidden" id="karyawan_id" value="${data.id || ''}">
                <div class="form-grid-2">
                    <div class="form-group"><label>NIP</label><input type="text" class="form-control" id="karyawan_nik" value="${data.nik || ''}" required></div>
                    <div class="form-group"><label>Nama Lengkap</label><input type="text" class="form-control" id="karyawan_nama" value="${data.nama || ''}" required></div>
                    <div class="form-group"><label>Email</label><input type="email" class="form-control" id="karyawan_email" value="${data.email || ''}" required></div>
                    <div class="form-group"><label>Jabatan</label><input type="text" class="form-control" id="karyawan_jabatan" value="${data.jabatan || ''}"></div>
                    <div class="form-group"><label>Bank</label><input type="text" class="form-control" id="karyawan_bank" value="${data.bank || ''}"></div>
                    <div class="form-group"><label>No. Rekening</label><input type="text" class="form-control" id="karyawan_no_rekening" value="${data.no_rekening || ''}"></div>
                </div>
                <div class="form-actions"><button type="submit" class="btn btn-primary">💾 Simpan</button><button type="button" class="btn btn-outline" onclick="App.loadPage('karyawan')">Batal</button></div>
            </form>
        </div></div>`;
    },

    async saveKaryawan(e) {
        e.preventDefault();
        const id = document.getElementById('karyawan_id').value;
        const body = {};
        ['nik', 'nama', 'email', 'jabatan', 'no_rekening', 'bank'].forEach(f => body[f] = document.getElementById('karyawan_' + f).value);
        if (id) body.id = id;
        const r = await (await fetch('api/karyawan.php', { method: id ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })).json();
        this.showToast(r.message, r.success ? 'success' : 'error');
        if (r.success) this.loadPage('karyawan');
    },

    async deleteKaryawan(id) {
        if (!confirm('Yakin ingin menghapus karyawan ini?')) return;
        const r = await (await fetch('api/karyawan.php', { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) })).json();
        this.showToast(r.message, r.success ? 'success' : 'error');
        if (r.success) this.renderKaryawan(document.getElementById('page-content'));
    },

    // ============ KOMPONEN (PENDAPATAN / POTONGAN) LIST ============
    async renderKomponen(c, tipe) {
        const isP = tipe === 'pendapatan';
        c.innerHTML = `
        <div class="page-header"><h1>${isP ? '💰 Kelola Pendapatan' : '📉 Kelola Potongan'}</h1>
            <p>${isP ? 'Kelola kategori dan komponen pendapatan' : 'Kelola kategori dan komponen potongan'}</p></div>
        
        <div class="card">
            <div class="card-header-row"><h3>📂 Kategori</h3>
                <button class="btn btn-sm btn-primary" onclick="App.loadPage('${tipe}/tambah-kategori')">➕ Tambah</button></div>
            <div class="card-inner" id="kategoriList"><div class="page-loading"><div class="spinner"></div></div></div>
        </div>

        <div class="card">
            <div class="card-header-row"><h3>📋 Komponen ${isP ? 'Pendapatan' : 'Potongan'}</h3>
                <button class="btn btn-sm btn-primary" onclick="App.loadPage('${tipe}/tambah')">➕ Tambah</button></div>
            <div class="card-inner" id="komponenTable"><div class="page-loading"><div class="spinner"></div></div></div>
        </div>`;

        // Load kategori
        const cats = await (await fetch(`api/kategori_komponen.php?tipe=${tipe}`)).json();
        const kl = document.getElementById('kategoriList');
        if (!cats.length) { kl.innerHTML = '<div class="empty-state"><p>Belum ada kategori</p></div>'; }
        else {
            kl.innerHTML = `<div class="kategori-grid">${cats.map(k => `
                <div class="kategori-card">
                    <div class="kategori-icon">${k.icon}</div>
                    <div class="kategori-name">${k.nama}</div>
                    <div class="kategori-actions">
                        <button class="btn btn-xs btn-info" onclick="App.loadPage('${tipe}/edit-kategori/${k.id}')">✏️</button>
                        <button class="btn btn-xs btn-danger" onclick="App.deleteKategori(${k.id},'${tipe}')">🗑️</button>
                    </div>
                </div>`).join('')}</div>`;
        }

        // Load komponen
        const list = await (await fetch(`api/komponen_gaji.php?tipe=${tipe}`)).json();
        const kt = document.getElementById('komponenTable');
        if (!list.length) { kt.innerHTML = '<div class="empty-state"><p>Belum ada komponen</p></div>'; }
        else {
            kt.innerHTML = `<div class="table-wrapper"><table><thead><tr><th>No</th><th>Nama</th><th>Kategori</th><th>Status</th><th style="width:100px">Aksi</th></tr></thead><tbody>
                ${list.map((k, i) => `<tr><td>${i + 1}</td><td><strong>${k.nama}</strong></td>
                <td><span class="kategori-badge">${k.kategori_icon || '📋'} ${k.kategori_nama || 'Tanpa Kategori'}</span></td>
                <td>${k.aktif == 1 ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-warning">Nonaktif</span>'}</td>
                <td><div class="action-btns"><button class="btn btn-xs btn-info" onclick="App.loadPage('${tipe}/edit/${k.id}')">✏️</button>
                <button class="btn btn-xs btn-danger" onclick="App.deleteKomponen(${k.id},'${tipe}')">🗑️</button></div></td></tr>`).join('')}</tbody></table></div>`;
        }
    },

    // ============ KATEGORI FORM (Separate Page) ============
    async renderKategoriForm(c, tipe, id = null) {
        let data = {};
        if (id) {
            const all = await (await fetch(`api/kategori_komponen.php?tipe=${tipe}`)).json();
            data = all.find(k => k.id == id) || {};
        }
        c.innerHTML = `
        <div class="page-header-row">
            <div><h1>${id ? '✏️ Edit Kategori' : '➕ Tambah Kategori'}</h1><p>Kategori untuk ${tipe}</p></div>
            <button class="btn btn-outline" onclick="App.loadPage('${tipe}')">← Kembali</button>
        </div>
        <div class="card"><div class="card-inner">
            <form onsubmit="App.saveKategori(event,'${tipe}')">
                <input type="hidden" id="kategori_id" value="${data.id || ''}">
                <div class="form-grid-2">
                    <div class="form-group"><label>Nama Kategori</label><input type="text" class="form-control" id="kategori_nama" value="${data.nama || ''}" required placeholder="Contoh: Tunjangan Khusus"></div>
                    <div class="form-group"><label>Icon</label>
                        <select class="form-control" id="kategori_icon">
                            ${['📋', '🏦', '💰', '⏰', '🎯', '🏥', '📉', '🎓', '🏠', '🍽️', '🚗', '💼'].map(ic => `<option value="${ic}" ${data.icon === ic ? 'selected' : ''}>${ic}</option>`).join('')}
                        </select>
                    </div>
                </div>
                <div class="form-actions"><button type="submit" class="btn btn-primary">💾 Simpan</button><button type="button" class="btn btn-outline" onclick="App.loadPage('${tipe}')">Batal</button></div>
            </form>
        </div></div>`;
    },

    async saveKategori(e, tipe) {
        e.preventDefault();
        const id = document.getElementById('kategori_id').value;
        const body = { nama: document.getElementById('kategori_nama').value, tipe, icon: document.getElementById('kategori_icon').value };
        if (id) body.id = id;
        const r = await (await fetch('api/kategori_komponen.php', { method: id ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })).json();
        this.showToast(r.message, r.success ? 'success' : 'error');
        if (r.success) this.loadPage(tipe);
    },

    async deleteKategori(id, tipe) {
        if (!confirm('Yakin hapus kategori ini?')) return;
        const r = await (await fetch(`api/kategori_komponen.php?id=${id}`, { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) })).json();
        this.showToast(r.message, r.success ? 'success' : 'error');
        if (r.success) this.renderKomponen(document.getElementById('page-content'), tipe);
    },

    // ============ KOMPONEN FORM (Separate Page) ============
    async renderKomponenForm(c, tipe, id = null) {
        let data = {};
        if (id) { data = await (await fetch(`api/komponen_gaji.php?id=${id}`)).json(); }
        const cats = await (await fetch(`api/kategori_komponen.php?tipe=${tipe}`)).json();

        c.innerHTML = `
        <div class="page-header-row">
            <div><h1>${id ? '✏️ Edit Komponen' : '➕ Tambah Komponen'}</h1><p>Komponen ${tipe}</p></div>
            <button class="btn btn-outline" onclick="App.loadPage('${tipe}')">← Kembali</button>
        </div>
        <div class="card"><div class="card-inner">
            <form onsubmit="App.saveKomponen(event,'${tipe}')">
                <input type="hidden" id="komponen_id" value="${data.id || ''}">
                <div class="form-grid-2">
                    <div class="form-group"><label>Nama Komponen</label><input type="text" class="form-control" id="komponen_nama" value="${data.nama || ''}" required placeholder="Contoh: Tunj. Makan"></div>
                    <div class="form-group"><label>Kategori</label>
                        <select class="form-control" id="komponen_kategori_id">
                            <option value="">Tanpa Kategori</option>
                            ${cats.map(c => `<option value="${c.id}" ${data.kategori_id == c.id ? 'selected' : ''}>${c.icon} ${c.nama}</option>`).join('')}
                        </select>
                    </div>
                </div>
                <div class="form-actions"><button type="submit" class="btn btn-primary">💾 Simpan</button><button type="button" class="btn btn-outline" onclick="App.loadPage('${tipe}')">Batal</button></div>
            </form>
        </div></div>`;
    },

    async saveKomponen(e, tipe) {
        e.preventDefault();
        const id = document.getElementById('komponen_id').value;
        const body = { nama: document.getElementById('komponen_nama').value, tipe, kategori_id: document.getElementById('komponen_kategori_id').value || null };
        if (id) body.id = id;
        const r = await (await fetch('api/komponen_gaji.php', { method: id ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })).json();
        this.showToast(r.message, r.success ? 'success' : 'error');
        if (r.success) this.loadPage(tipe);
    },

    async deleteKomponen(id, tipe) {
        if (!confirm('Yakin hapus komponen ini?')) return;
        const r = await (await fetch(`api/komponen_gaji.php?id=${id}`, { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) })).json();
        this.showToast(r.message, r.success ? 'success' : 'error');
        if (r.success) this.renderKomponen(document.getElementById('page-content'), tipe);
    },

    // ============ SLIP GAJI LIST ============
    async renderSlipGaji(c) {
        const now = new Date();
        c.innerHTML = `
        <div class="page-header-row">
            <div><h1>📄 Slip Gaji</h1><p>Buat dan kelola slip gaji karyawan</p></div>
            <div class="header-actions">
                <input type="text" class="form-control form-select-sm" id="searchSlip" placeholder="🔍 Cari NIP, Nama..." oninput="App.filterTable('slipTable', this.value)">
                <select id="filterBulan" class="form-control form-select-sm" onchange="App.loadSlipTable()">
                    ${[...Array(12)].map((_, i) => `<option value="${i + 1}" ${i + 1 === now.getMonth() + 1 ? 'selected' : ''}>${this.getMonthName(i + 1)}</option>`).join('')}
                </select>
                <select id="filterTahun" class="form-control form-select-sm" onchange="App.loadSlipTable()">
                    ${[2024, 2025, 2026, 2027].map(y => `<option value="${y}" ${y === now.getFullYear() ? 'selected' : ''}>${y}</option>`).join('')}
                </select>
                <button class="btn btn-primary" onclick="App.loadPage('slip-gaji/tambah')">➕ Buat Slip Gaji</button>
            </div>
        </div>
        <div class="card"><div class="card-inner" id="slipTable"><div class="page-loading"><div class="spinner"></div></div></div></div>`;
        this.loadSlipTable();
    },

    async loadSlipTable() {
        const b = document.getElementById('filterBulan').value, y = document.getElementById('filterTahun').value;
        const list = await (await fetch(`api/slip_gaji.php?bulan=${b}&tahun=${y}`)).json();
        const t = document.getElementById('slipTable');
        if (!list.length) { t.innerHTML = '<div class="empty-state"><div class="empty-icon">📄</div><h3>Belum Ada Slip Gaji</h3><p>Klik "Buat Slip Gaji" untuk memulai</p></div>'; return; }
        t.innerHTML = `<div class="table-wrapper"><table><thead><tr><th>NIP</th><th>Nama</th><th>Jabatan</th><th>Total Pendapatan</th><th>Total Potongan</th><th>Gaji Bersih</th><th>Status</th><th style="width:120px">Aksi</th></tr></thead><tbody>
            ${list.map(s => `<tr>
                <td><strong>${s.nik}</strong></td><td>${s.nama}</td><td>${s.jabatan || '-'}</td>
                <td class="text-success">${this.formatRupiah(s.total_pendapatan)}</td>
                <td class="text-danger">${this.formatRupiah(s.total_potongan)}</td>
                <td><strong class="text-success">${this.formatRupiah(s.gaji_bersih)}</strong></td>
                <td>${s.email_sent ? '<span class="badge badge-success">Sent</span>' : '<span class="badge badge-warning">Draft</span>'}</td>
                <td><div class="action-btns">
                    <a href="generate_pdf.php?id=${s.id}" target="_blank" class="btn btn-xs btn-info" title="PDF">📥</a>
                    <button class="btn btn-xs btn-warning" onclick="App.loadPage('slip-gaji/edit/${s.id}')">✏️</button>
                    <button class="btn btn-xs btn-danger" onclick="App.deleteSlip(${s.id})">🗑️</button>
                </div></td></tr>`).join('')}</tbody></table></div>`;
    },

    // ============ SLIP GAJI FORM (Separate Page) ============
    async renderSlipForm(c, id = null) {
        const now = new Date();
        let editData = null;
        if (id) { editData = await (await fetch(`api/slip_gaji.php?id=${id}`)).json(); }
        const kList = await (await fetch('api/karyawan.php')).json();
        const komponen = await (await fetch('api/komponen_gaji.php')).json();
        const pendapatan = komponen.filter(k => k.tipe === 'pendapatan' && k.aktif == 1);
        const potongan = komponen.filter(k => k.tipe === 'potongan' && k.aktif == 1);
        const vals = {};
        if (editData?.details) editData.details.forEach(d => { vals[d.komponen_id] = d.jumlah; });

        const renderGroup = (items, label, icon) => {
            const groups = {};
            items.forEach(k => { const cat = k.kategori_nama || 'Lainnya'; if (!groups[cat]) groups[cat] = []; groups[cat].push(k); });
            let html = `<div class="komponen-section"><div class="komponen-section-title">${icon} ${label}</div>`;
            for (const [catName, catItems] of Object.entries(groups)) {
                html += `<div class="komponen-category"><div class="komponen-category-label">${catName}</div><div class="form-grid-3">`;
                catItems.forEach(k => { const fmtVal = App.formatNumberInput(vals[k.id] || 0); html += `<div class="form-group"><label>${k.nama}</label><input type="text" class="form-control komponen-input currency-input" data-komponen-id="${k.id}" data-tipe="${k.tipe}" value="${fmtVal}" inputmode="numeric" oninput="App.formatCurrencyInput(this)" onfocus="this.select()"></div>`; });
                html += '</div></div>';
            }
            return html + '</div>';
        };

        c.innerHTML = `
        <div class="page-header-row">
            <div><h1>${id ? '✏️ Edit Slip Gaji' : '➕ Buat Slip Gaji'}</h1><p>Isi data slip gaji karyawan</p></div>
            <button class="btn btn-outline" onclick="App.loadPage('slip-gaji')">← Kembali</button>
        </div>
        <div class="card"><div class="card-inner">
            <form id="slipForm" onsubmit="App.saveSlip(event)">
                <input type="hidden" id="slip_id" value="${editData?.id || ''}">
                <div class="form-grid-3">
                    <div class="form-group"><label>Karyawan</label>
                        <select class="form-control" id="slip_karyawan" required ${editData ? 'disabled' : ''}>
                            <option value="">Pilih Karyawan...</option>
                            ${kList.map(k => `<option value="${k.id}" ${editData?.karyawan_id == k.id ? 'selected' : ''}>[${k.nik}] ${k.nama}</option>`).join('')}
                        </select>
                    </div>
                    <div class="form-group"><label>Bulan</label>
                        <select class="form-control" id="slip_bulan" required>
                            ${[...Array(12)].map((_, i) => `<option value="${i + 1}" ${(editData ? editData.bulan : now.getMonth() + 1) == i + 1 ? 'selected' : ''}>${this.getMonthName(i + 1)}</option>`).join('')}
                        </select>
                    </div>
                    <div class="form-group"><label>Tahun</label><input type="number" class="form-control" id="slip_tahun" value="${editData?.tahun || now.getFullYear()}" required></div>
                </div>
                
                ${renderGroup(pendapatan, 'Pendapatan', '💰')}
                ${renderGroup(potongan, 'Potongan', '📉')}

                <div class="slip-summary">
                    <div class="summary-row"><span>Total Pendapatan</span><strong id="totalPendapatan" class="text-success">Rp 0</strong></div>
                    <div class="summary-row"><span>Total Potongan</span><strong id="totalPotongan" class="text-danger">Rp 0</strong></div>
                    <div class="summary-row summary-total"><span>Gaji Bersih (Take Home Pay)</span><strong id="gajiBersih">Rp 0</strong></div>
                </div>

                <div class="form-group" style="margin-top:16px"><label>Keterangan</label><textarea class="form-control" id="slip_keterangan" rows="2" placeholder="Opsional...">${editData?.keterangan || ''}</textarea></div>
                <div class="form-actions"><button type="submit" class="btn btn-primary">💾 Simpan</button><button type="button" class="btn btn-outline" onclick="App.loadPage('slip-gaji')">Batal</button></div>
            </form>
        </div></div>`;
        this.updateSlipTotals();
    },

    updateSlipTotals() {
        let p = 0, k = 0;
        document.querySelectorAll('.komponen-input').forEach(i => {
            const v = this.parseRawNumber(i.value);
            if (i.dataset.tipe === 'pendapatan') p += v; else k += v;
        });
        const el = id => document.getElementById(id);
        if (el('totalPendapatan')) el('totalPendapatan').textContent = this.formatRupiah(p);
        if (el('totalPotongan')) el('totalPotongan').textContent = this.formatRupiah(k);
        if (el('gajiBersih')) el('gajiBersih').textContent = this.formatRupiah(p - k);
    },

    async saveSlip(e) {
        e.preventDefault();
        const id = document.getElementById('slip_id').value;
        const details = [];
        document.querySelectorAll('.komponen-input').forEach(i => {
            details.push({ komponen_id: parseInt(i.dataset.komponenId), jumlah: this.parseRawNumber(i.value) });
        });
        const body = { karyawan_id: document.getElementById('slip_karyawan').value, bulan: document.getElementById('slip_bulan').value, tahun: document.getElementById('slip_tahun').value, keterangan: document.getElementById('slip_keterangan').value, details };
        if (id) body.id = id;
        const r = await (await fetch('api/slip_gaji.php', { method: id ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })).json();
        this.showToast(r.message, r.success ? 'success' : 'error');
        if (r.success) this.loadPage('slip-gaji');
    },

    async deleteSlip(id) {
        if (!confirm('Yakin hapus slip gaji ini?')) return;
        const r = await (await fetch('api/slip_gaji.php', { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) })).json();
        this.showToast(r.message, r.success ? 'success' : 'error');
        if (r.success) this.loadSlipTable();
    },

    // ============ KIRIM EMAIL ============
    async renderKirimEmail(c) {
        const now = new Date();
        c.innerHTML = `
        <div class="page-header-row">
            <div><h1>📧 Kirim Email</h1><p>Kirim slip gaji via email ke karyawan</p></div>
            <div class="header-actions">
                <input type="text" class="form-control form-select-sm" id="searchEmail" placeholder="🔍 Cari NIP, Nama..." oninput="App.filterTable('emailTable', this.value)">
                <select id="emailFilterBulan" class="form-control form-select-sm" onchange="App.loadEmailTable()">
                    ${[...Array(12)].map((_, i) => `<option value="${i + 1}" ${i + 1 === now.getMonth() + 1 ? 'selected' : ''}>${this.getMonthName(i + 1)}</option>`).join('')}
                </select>
                <select id="emailFilterTahun" class="form-control form-select-sm" onchange="App.loadEmailTable()">
                    ${[2024, 2025, 2026, 2027].map(y => `<option value="${y}" ${y === now.getFullYear() ? 'selected' : ''}>${y}</option>`).join('')}
                </select>
                <button class="btn btn-primary" onclick="App.sendBulkEmail()">📤 Kirim Terpilih (<span id="selectedCount">0</span>)</button>
            </div>
        </div>
        <div class="card"><div class="card-inner" id="emailTable"><div class="page-loading"><div class="spinner"></div></div></div></div>`;
        this.loadEmailTable();
    },

    async loadEmailTable() {
        const b = document.getElementById('emailFilterBulan').value, y = document.getElementById('emailFilterTahun').value;
        const list = await (await fetch(`api/slip_gaji.php?bulan=${b}&tahun=${y}`)).json();
        const t = document.getElementById('emailTable');
        if (!list.length) { t.innerHTML = '<div class="empty-state"><div class="empty-icon">📧</div><p>Belum ada slip gaji untuk periode ini</p></div>'; return; }
        t.innerHTML = `<div class="table-wrapper"><table><thead><tr><th style="width:40px"><input type="checkbox" onchange="App.toggleAllEmail(this)"></th><th>NIP</th><th>Nama</th><th>Gaji Bersih</th><th>Status</th></tr></thead><tbody>
            ${list.map(s => `<tr><td><input type="checkbox" class="email-check" value="${s.id}" onchange="App.updateSelectedCount()" ${s.email_sent ? 'disabled' : ''}></td>
            <td><strong>${s.nik}</strong></td><td>${s.nama}</td>
            <td><strong>${this.formatRupiah(s.gaji_bersih)}</strong></td>
            <td>${s.email_sent ? '<span class="badge badge-success">✅ Terkirim</span>' : '<span class="badge badge-warning">⏳ Belum</span>'}</td></tr>`).join('')}</tbody></table></div>`;
    },

    toggleAllEmail(cb) { document.querySelectorAll('.email-check:not(:disabled)').forEach(c => c.checked = cb.checked); this.updateSelectedCount(); },
    updateSelectedCount() { document.getElementById('selectedCount').textContent = document.querySelectorAll('.email-check:checked').length; },

    async sendBulkEmail() {
        const checked = document.querySelectorAll('.email-check:checked');
        if (!checked.length) { this.showToast('Pilih minimal 1 slip', 'error'); return; }
        if (!confirm(`Kirim email ke ${checked.length} karyawan?`)) return;
        for (const cb of checked) {
            try {
                const resp = await fetch('api/send_email.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ slip_id: cb.value }) });
                const text = await resp.text();
                try {
                    const r = JSON.parse(text);
                    this.showToast(r.message, r.success ? 'success' : 'error');
                } catch (e) {
                    console.error('Server response:', text);
                    this.showToast('Gagal mengirim email: server error', 'error');
                }
            } catch (e) {
                this.showToast('Gagal mengirim email: koneksi error', 'error');
            }
        }
        this.loadEmailTable();
    },

    // ============ PENGATURAN ============
    renderPengaturan(c) {
        c.innerHTML = `
        <div class="page-header"><h1>⚙️ Pengaturan</h1><p>Konfigurasi sistem</p></div>
        <div class="card"><div class="card-inner">
            <h3>🏢 Informasi Perusahaan</h3>
            <form onsubmit="App.saveSettings(event,'company')" style="margin-top:16px">
                <div class="form-grid-2">
                    <div class="form-group"><label>Nama Perusahaan</label><input type="text" class="form-control" id="set_nama_perusahaan"></div>
                    <div class="form-group"><label>Alamat</label><input type="text" class="form-control" id="set_alamat_perusahaan"></div>
                </div>
                <div class="form-actions"><button type="submit" class="btn btn-primary">💾 Simpan</button></div>
            </form>
        </div></div>
        <div class="card"><div class="card-inner">
            <h3>📧 Email SMTP</h3>
            <form onsubmit="App.saveSettings(event,'email')" style="margin-top:16px">
                <div class="form-grid-2">
                    <div class="form-group"><label>SMTP Host</label><input type="text" class="form-control" id="set_email_smtp_host"></div>
                    <div class="form-group"><label>SMTP Port</label><input type="number" class="form-control" id="set_email_smtp_port"></div>
                    <div class="form-group"><label>Username</label><input type="text" class="form-control" id="set_email_smtp_user"></div>
                    <div class="form-group"><label>Password</label><input type="password" class="form-control" id="set_email_smtp_pass"></div>
                    <div class="form-group"><label>Nama Pengirim</label><input type="text" class="form-control" id="set_email_from_name"></div>
                    <div class="form-group"><label>Jabatan Pengirim</label><input type="text" class="form-control" id="set_email_from_title"></div>
                </div>
                <div class="form-actions"><button type="submit" class="btn btn-primary">💾 Simpan</button>
                <button type="button" class="btn btn-info" onclick="App.testEmail()">🧪 Test Email</button></div>
            </form>
        </div></div>
        <div class="card"><div class="card-inner">
            <h3>🔒 Ubah Password</h3>
            <form onsubmit="App.changePassword(event)" style="margin-top:16px">
                <div class="form-grid-2">
                    <div class="form-group"><label>Password Lama</label><input type="password" class="form-control" id="old_password" required></div>
                    <div class="form-group"><label>Password Baru</label><input type="password" class="form-control" id="new_password" required></div>
                    <div class="form-group"><label>Konfirmasi Password Baru</label><input type="password" class="form-control" id="confirm_password" required></div>
                </div>
                <div class="form-actions"><button type="submit" class="btn btn-warning">🔑 Ubah Password</button></div>
            </form>
        </div></div>`;
        this.loadSettings();
    },

    async loadSettings() {
        const s = await (await fetch('api/pengaturan.php')).json();
        s.forEach(x => { const el = document.getElementById('set_' + x.setting_key); if (el) el.value = x.setting_value || ''; });
    },

    async saveSettings(e, type) {
        e.preventDefault();
        const keys = type === 'company' ? ['nama_perusahaan', 'alamat_perusahaan'] : ['email_smtp_host', 'email_smtp_port', 'email_smtp_user', 'email_smtp_pass', 'email_from_name', 'email_from_title'];
        const body = {}; keys.forEach(k => body[k] = document.getElementById('set_' + k).value);
        const r = await (await fetch('api/pengaturan.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })).json();
        this.showToast(r.message, r.success ? 'success' : 'error');
    },

    async testEmail() {
        const email = prompt('Masukkan email test:');
        if (!email) return;
        const r = await (await fetch('api/send_email.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ test: true, test_email: email }) })).json();
        this.showToast(r.message, r.success ? 'success' : 'error');
    },

    async changePassword(e) {
        e.preventDefault();
        const oldPass = document.getElementById('old_password').value;
        const newPass = document.getElementById('new_password').value;
        const confirmPass = document.getElementById('confirm_password').value;

        if (newPass !== confirmPass) {
            this.showPopup('❌', 'Gagal', 'Password baru dan konfirmasi tidak cocok', 'error');
            return;
        }
        if (newPass.length < 6) {
            this.showPopup('❌', 'Gagal', 'Password baru minimal 6 karakter', 'error');
            return;
        }

        const r = await (await fetch('api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'change_password', old_password: oldPass, new_password: newPass })
        })).json();

        if (r.success) {
            this.showPopup('✅', 'Berhasil!', r.message, 'success');
            document.getElementById('old_password').value = '';
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
        } else {
            this.showPopup('❌', 'Gagal!', r.message, 'error');
        }
    },

    showPopup(icon, title, message, type) {
        // Remove existing popup
        const existing = document.getElementById('appPopup');
        if (existing) existing.remove();

        const color = type === 'success' ? 'var(--accent-green)' : 'var(--accent-red)';
        const overlay = document.createElement('div');
        overlay.id = 'appPopup';
        overlay.className = 'popup-overlay';
        overlay.innerHTML = `
            <div class="popup-card">
                <div class="popup-icon" style="color:${color}">${icon}</div>
                <h3 class="popup-title">${title}</h3>
                <p class="popup-message">${message}</p>
                <button class="btn btn-primary popup-btn" onclick="document.getElementById('appPopup').remove()">OK</button>
            </div>`;
        document.body.appendChild(overlay);
        setTimeout(() => overlay.querySelector('.popup-card').classList.add('show'), 10);
        overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });
    },

    // ============ UTILITY ============
    showToast(msg, type = 'info') {
        const c = document.getElementById('toastContainer');
        const t = document.createElement('div');
        t.className = `toast toast-${type}`;
        t.textContent = msg;
        c.appendChild(t);
        setTimeout(() => t.classList.add('show'), 10);
        setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 3000);
    },

    formatRupiah(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); },
    getMonthName(m) { return ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'][m] || ''; },

    // Currency input formatting
    formatNumberInput(n) { return Number(n || 0).toLocaleString('id-ID'); },
    parseRawNumber(str) { return parseFloat(String(str).replace(/\./g, '').replace(/,/g, '.')) || 0; },
    formatCurrencyInput(el) {
        const pos = el.selectionStart;
        const oldLen = el.value.length;
        const raw = this.parseRawNumber(el.value);
        el.value = raw > 0 ? this.formatNumberInput(raw) : '';
        const newLen = el.value.length;
        const newPos = Math.max(0, pos + (newLen - oldLen));
        el.setSelectionRange(newPos, newPos);
        this.updateSlipTotals();
    },

    // Table search filter
    filterTable(containerId, query) {
        const container = document.getElementById(containerId);
        if (!container) return;
        const rows = container.querySelectorAll('tbody tr');
        const q = query.toLowerCase().trim();
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = q === '' || text.includes(q) ? '' : 'none';
        });
    }
};

document.addEventListener('DOMContentLoaded', () => App.init());
