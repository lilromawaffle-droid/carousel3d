<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - 3D Showcase</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
  
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body, html {
      width: 100%; min-height: 100vh;
      background: radial-gradient(circle at 50% 50%, #1a1f33 0%, #090b10 100%);
      font-family: 'Inter', sans-serif;
      color: #ffffff;
      overflow-x: hidden;
    }

    /* Ambient Background Glows */
    .glow-bg {
      position: fixed; width: 500px; height: 500px; border-radius: 50%;
      filter: blur(120px); pointer-events: none; z-index: 0;
    }
    .glow-1 { top: -10%; left: -10%; background: rgba(109, 123, 255, 0.15); }
    .glow-2 { bottom: -10%; right: -10%; background: rgba(255, 94, 247, 0.1); }

    /* Header */
    header {
      padding: 20px 40px; display: flex; justify-content: space-between; align-items: center;
      position: relative; z-index: 10;
      border-bottom: 1px solid rgba(255,255,255,0.05);
      background: rgba(10, 12, 20, 0.6); backdrop-filter: blur(10px);
    }
    .brand {
      font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; letter-spacing: 0.05em;
      background: linear-gradient(90deg, #ffffff, #9ba5ff);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      display: flex; align-items: center; gap: 10px;
    }
    .brand-badge {
      font-size: 11px; padding: 4px 10px; border-radius: 20px;
      background: linear-gradient(135deg, rgba(255, 94, 247, 0.2), rgba(109, 123, 255, 0.2));
      border: 1px solid rgba(255, 255, 255, 0.15); color: #ffffff; letter-spacing: 0.02em;
      -webkit-text-fill-color: #fff;
    }

    .btn-back {
      background: rgba(255, 255, 255, 0.08); color: #fff; text-decoration: none;
      padding: 10px 20px; border-radius: 8px; font-weight: 500; font-size: 14px;
      border: 1px solid rgba(255,255,255,0.1); transition: all 0.3s ease;
    }
    .btn-back:hover { background: rgba(255, 255, 255, 0.15); }

    /* Layout */
    .dashboard-layout {
      display: flex;
      max-width: 1400px;
      margin: 0 auto;
      min-height: calc(100vh - 75px);
      position: relative; z-index: 10;
    }

    /* Sidebar */
    .sidebar {
      width: 250px;
      background: rgba(0,0,0,0.2);
      border-right: 1px solid rgba(255,255,255,0.05);
      padding: 30px 20px;
    }
    .sidebar-menu { list-style: none; }
    .sidebar-menu li { margin-bottom: 10px; }
    .sidebar-link {
      display: block; padding: 12px 16px; color: #a3a8b8;
      text-decoration: none; font-weight: 500; font-size: 14px; border-radius: 8px;
      transition: all 0.3s ease; cursor: pointer;
    }
    .sidebar-link:hover { color: #fff; background: rgba(255,255,255,0.05); }
    .sidebar-link.active {
      color: #fff; background: linear-gradient(90deg, rgba(109, 123, 255, 0.2), transparent);
      border-left: 3px solid #6d7bff;
    }

    /* Main Content */
    .main-content { flex: 1; padding: 40px; }
    
    .tab-content { display: none; animation: fadeIn 0.4s ease; }
    .tab-content.active { display: block; }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    /* Cards & Forms */
    .glass-card {
      background: rgba(20, 24, 40, 0.6); border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 16px; padding: 30px; backdrop-filter: blur(20px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.4); margin-bottom: 30px;
    }
    .card-title {
      font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700;
      margin-bottom: 24px; color: #ffffff; display: flex; align-items: center; gap: 10px;
    }
    .card-title::before {
      content: ''; display: block; width: 4px; height: 20px;
      background: linear-gradient(180deg, #6d7bff, #ff5ef7); border-radius: 4px;
    }
    .header-action { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .header-action .card-title { margin-bottom: 0; }

    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 13px; color: #a3a8b8; margin-bottom: 8px; font-weight: 500; }
    .form-control {
      width: 100%; background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255,255,255,0.1);
      border-radius: 8px; padding: 12px 16px; color: #fff; font-family: 'Inter', sans-serif;
      font-size: 14px; outline: none; transition: border-color 0.3s ease;
    }
    .form-control:focus { border-color: #6d7bff; }
    textarea.form-control { min-height: 100px; resize: vertical; }
    select.form-control { appearance: none; cursor: pointer; }

    .btn-submit {
      background: linear-gradient(135deg, #6d7bff, #535ff5); color: #fff; border: none;
      padding: 12px 24px; border-radius: 8px; font-family: 'Outfit', sans-serif; font-weight: 600;
      font-size: 14px; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease;
      box-shadow: 0 4px 15px rgba(109, 123, 255, 0.4);
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(109, 123, 255, 0.6); }
    .btn-secondary {
        background: rgba(255,255,255,0.1); color: #fff; border: none;
        padding: 12px 24px; border-radius: 8px; font-family: 'Outfit', sans-serif; font-weight: 600;
        font-size: 14px; cursor: pointer; transition: background 0.2s; margin-left: 10px;
    }
    .btn-secondary:hover { background: rgba(255,255,255,0.2); }

    /* Custom Specs Dynamic Inputs */
    .spec-row { display: grid; grid-template-columns: 1fr 2fr 40px; gap: 15px; margin-bottom: 10px; align-items: center; }
    .btn-remove-spec { 
        background: rgba(255, 71, 87, 0.2); color: #ff4757; border: 1px solid rgba(255,71,87,0.3);
        border-radius: 8px; width: 40px; height: 40px; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center;
    }

    /* Drag & Drop Upload Area */
    .upload-area {
        background: rgba(0,0,0,0.3); border: 2px dashed rgba(255,255,255,0.2);
        border-radius: 12px; padding: 40px 20px; text-align: center;
        transition: all 0.3s ease; cursor: pointer; position: relative;
    }
    .upload-area.dragover { border-color: #6d7bff; background: rgba(109, 123, 255, 0.1); }
    .upload-area p { color: #a3a8b8; font-size: 14px; margin-bottom: 10px; }
    .upload-area .highlight { color: #fff; font-weight: 600; }
    .upload-area input[type="file"] {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        opacity: 0; cursor: pointer;
    }
    .file-name-display { color: #7bed9f; font-weight: 600; margin-top: 10px; display: none; }

    /* Tables */
    .table-container { overflow-x: auto; margin-bottom: 20px; }
    table { width: 100%; text-align: left; border-collapse: collapse; min-width: 800px; }
    th { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); color: #a3a8b8; font-size: 13px; }
    td { padding: 12px; font-size: 14px; border-bottom: 1px solid rgba(255,255,255,0.05); }
    tr:hover td { background: rgba(255,255,255,0.02); }
    
    .action-btn { 
        text-decoration: none; font-size: 12px; font-weight: 600; padding: 6px 12px; 
        border-radius: 6px; display: inline-block; cursor: pointer; border: none;
    }
    .btn-edit { background: rgba(109, 123, 255, 0.15); color: #8292ff; border: 1px solid rgba(109, 123, 255, 0.3); }
    .btn-delete { background: rgba(255, 71, 87, 0.15); color: #ff7f50; border: 1px solid rgba(255, 71, 87, 0.3); margin-left: 5px; }

    /* Alerts */
    .alert {
      padding: 16px 20px; border-radius: 8px; margin-bottom: 30px;
      font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 12px;
    }
    .alert-success { background: rgba(46, 213, 115, 0.15); border: 1px solid rgba(46, 213, 115, 0.3); color: #7bed9f; }
    .alert-error { background: rgba(255, 71, 87, 0.15); border: 1px solid rgba(255, 71, 87, 0.3); color: #ff7f50; }

    /* Modals (Popups) */
    .modal-overlay {
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);
      z-index: 100; display: flex; justify-content: center; align-items: center;
      opacity: 0; pointer-events: none; transition: opacity 0.3s ease;
    }
    .modal-overlay.active { opacity: 1; pointer-events: auto; }
    
    .modal-content {
      background: rgba(15, 18, 30, 0.95);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px; padding: 30px;
      width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto;
      transform: translateY(20px); transition: transform 0.3s ease;
      position: relative;
    }
    .modal-overlay.active .modal-content { transform: translateY(0); }
    
    .modal-close {
      position: absolute; top: 20px; right: 20px;
      background: rgba(255,255,255,0.05); color: #fff;
      border: 1px solid rgba(255,255,255,0.1); border-radius: 50%;
      width: 32px; height: 32px; font-size: 14px; cursor: pointer;
      display: flex; justify-content: center; align-items: center;
      transition: all 0.2s; z-index: 10;
    }
    .modal-close:hover { background: rgba(255,255,255,0.15); }

    /* Custom scrollbar for modal */
    .modal-content::-webkit-scrollbar { width: 8px; }
    .modal-content::-webkit-scrollbar-track { background: rgba(0,0,0,0.2); border-radius: 4px; }
    .modal-content::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }
    .modal-content::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
  </style>
</head>
<body>
  <div class="glow-bg glow-1"></div>
  <div class="glow-bg glow-2"></div>

  <header>
    <div class="brand">
      <span>3D SHOWCASE</span> <span class="brand-badge">ADMIN PANEL</span>
    </div>
    <a href="?c=Home" class="btn-back">KEMBALI KE BERANDA</a>
  </header>

  <div class="dashboard-layout">
    
    <!-- Sidebar -->
    <aside class="sidebar">
      <ul class="sidebar-menu">
        <li><a class="sidebar-link active" onclick="switchTab('dashboard', this)">📋 Dashboard Utama</a></li>
        <li><a class="sidebar-link" onclick="switchTab('category', this)">📂 Kelola Kategori</a></li>
        <li><a class="sidebar-link" onclick="switchTab('item', this)">📦 Kelola Model 3D</a></li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      
      <?php if(!empty($message)): ?>
        <div class="alert <?= $status === 'success' ? 'alert-success' : 'alert-error' ?>">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>

      <!-- TAB: Dashboard -->
      <div id="tab-dashboard" class="tab-content active">
        <div class="glass-card">
          <h2 class="card-title">Selamat Datang di Admin Panel</h2>
          <p style="color: #a3a8b8; line-height: 1.6;">Silakan gunakan menu di sebelah kiri untuk mengelola data Kategori dan Model 3D Anda. Data yang tersimpan akan langsung terhubung ke tampilan Carousel 3D secara *real-time*.</p>
        </div>
      </div>

      <!-- TAB: Kategori -->
      <div id="tab-category" class="tab-content">
        <div class="glass-card">
          <div class="header-action">
            <h2 class="card-title">Daftar Kategori Tersimpan</h2>
            <button class="btn-submit" onclick="openCatModal()">+ Tambah Kategori</button>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>ID</th><th>Nama Kategori</th><th>Slug</th><th style="width: 150px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if(empty($categories)): ?>
                  <tr><td colspan="4" style="text-align: center; color: #a3a8b8;">Belum ada kategori tersimpan.</td></tr>
                <?php else: ?>
                  <?php foreach($categories as $cat): ?>
                  <tr>
                    <td><?= $cat['id'] ?></td>
                    <td style="color: #fff; font-weight: 500;"><?= htmlspecialchars($cat['name']) ?></td>
                    <td style="color: #8292ff;"><?= htmlspecialchars($cat['slug']) ?></td>
                    <td>
                      <button class="action-btn btn-edit" onclick="editCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name'])) ?>')">Edit</button>
                      <a class="action-btn btn-delete" href="?c=Admin&m=delete_category&id=<?= $cat['id'] ?>" onclick="return confirm('Yakin hapus kategori ini? Semua barang di dalamnya akan ikut terhapus!');">Hapus</a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB: Model 3D -->
      <div id="tab-item" class="tab-content">
        <div class="glass-card">
          <div class="header-action">
            <h2 class="card-title">Daftar Model 3D Tersimpan</h2>
            <button class="btn-submit" onclick="openItemModal()">+ Tambah Model 3D</button>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Nama Model</th><th>Kategori</th><th>Path / File</th><th style="width: 150px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if(empty($items)): ?>
                  <tr><td colspan="4" style="text-align: center; color: #a3a8b8;">Belum ada model tersimpan.</td></tr>
                <?php else: ?>
                  <?php foreach($items as $item): ?>
                  <tr>
                    <td style="color: #fff; font-weight: 500;"><?= htmlspecialchars($item['name']) ?></td>
                    <td style="color: #a3a8b8;"><?= htmlspecialchars($item['category_name']) ?></td>
                    <td style="color: #7bed9f;"><code><?= htmlspecialchars($item['path']) ?></code></td>
                    <td>
                      <button class="action-btn btn-edit" onclick="editItem(<?= htmlspecialchars(json_encode($item)) ?>)">Edit</button>
                      <a class="action-btn btn-delete" href="?c=Admin&m=delete_item&id=<?= $item['id'] ?>" onclick="return confirm('Yakin hapus item ini?');">Hapus</a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </main>
  </div>

  <!-- MODALS -->

  <!-- Modal Kategori -->
  <div class="modal-overlay" id="modal-category">
    <div class="modal-content" style="max-width: 500px;">
      <button class="modal-close" onclick="closeCatModal()">&#10005;</button>
      <h2 class="card-title" id="form-category-title">Tambah Kategori</h2>
      <form action="?c=Admin&m=save_category" method="POST" id="form-category">
        <input type="hidden" name="id" id="cat_id" value="">
        <div class="form-group">
          <label for="cat_name">Nama Kategori</label>
          <input type="text" id="cat_name" name="name" class="form-control" placeholder="Contoh: Lab Mesin" required>
        </div>
        <div style="margin-top: 30px;">
          <button type="submit" class="btn-submit">Simpan Kategori</button>
          <button type="button" class="btn-secondary" onclick="closeCatModal()">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Item 3D -->
  <div class="modal-overlay" id="modal-item">
    <div class="modal-content">
      <button class="modal-close" onclick="closeItemModal()">&#10005;</button>
      <h2 class="card-title" id="form-item-title">Tambah Model 3D</h2>
      <form action="?c=Admin&m=save_item" method="POST" enctype="multipart/form-data" id="form-item">
        <input type="hidden" name="id" id="item_id" value="">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
          <div class="form-group">
            <label for="category_id">Pilih Kategori</label>
            <select id="category_id" name="category_id" class="form-control" required>
              <option value="">-- Pilih Kategori --</option>
              <?php foreach($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="item_name">Nama Barang</label>
            <input type="text" id="item_name" name="name" class="form-control" placeholder="Cth: Mesin CNC" required>
          </div>
        </div>

        <div class="form-group">
          <label for="tag">Tag Singkat (Opsional, cth: Machinery)</label>
          <input type="text" id="tag" name="tag" class="form-control">
        </div>

        <div class="form-group">
          <label for="description">Deskripsi Panjang (Bisa dienter untuk baris baru)</label>
          <textarea id="description" name="description" class="form-control" placeholder="Penjelasan lengkap alat/barang..."></textarea>
        </div>

        <!-- Dynamic Specs -->
        <div class="form-group" style="background: rgba(0,0,0,0.2); padding: 20px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.05);">
          <label style="color: #fff; font-size: 15px; margin-bottom: 15px;">Keterangan Tambahan / Spesifikasi (Opsional)</label>
          <p style="font-size: 12px; color: #a3a8b8; margin-bottom: 15px;">Anda bisa menambahkan keterangan bebas seperti Harga, Dimensi, Bahan, dll.</p>
          
          <div id="specs-container">
            <!-- Specs rows go here -->
          </div>
          <button type="button" class="action-btn btn-edit" style="margin-top: 10px;" onclick="addSpecRow('', '')">+ Tambah Keterangan</button>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
          <div class="form-group">
            <label>File 3D (.fbx, .glb) (Max 2MB)</label>
            <div class="upload-area" id="upload-area">
                <p>Tarik & Letakkan (Drag & Drop) file ke sini, atau <span class="highlight">Klik untuk Memilih</span></p>
                <input type="file" id="file_3d" name="file_3d" accept=".fbx,.glb,.obj">
                <div class="file-name-display" id="file-name-display"></div>
            </div>
            <p style="font-size: 12px; color: #7bed9f; margin-top: 10px; line-height: 1.4;">💡 <b>Sangat Disarankan:</b> Gunakan file dengan format <b>.glb</b> karena ukurannya jauh lebih kecil, memuat lebih cepat, dan material warnanya langsung akurat di web.</p>
          </div>
          
          <div class="form-group" style="background: rgba(0,0,0,0.2); padding: 20px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.05);">
            <label>Warna Background (Opsional)</label>
            <div style="display: flex; align-items: center; gap: 15px; margin-top: 10px;">
              <input type="color" id="bg_color" name="bg_color" style="width: 50px; height: 50px; border: none; border-radius: 8px; cursor: pointer; background: transparent;" value="#1a1f33" disabled>
              <div style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="auto_color" name="auto_color" value="1" checked onchange="document.getElementById('bg_color').disabled = this.checked;">
                <label for="auto_color" style="margin-bottom: 0; cursor: pointer; font-size: 14px;">Warna Otomatis</label>
              </div>
            </div>
            <p style="font-size: 12px; color: #a3a8b8; margin-top: 10px;">Pilih warna manual, atau biarkan sistem mencari warna otomatis (disarankan).</p>
            <input type="hidden" name="existing_path" id="existing_path" value="">
            <p style="font-size: 12px; color: #a3a8b8; margin-top: 15px; font-weight: bold;" id="existing-path-info"></p>
          </div>
        </div>

        <div style="margin-top: 10px;">
          <button type="submit" class="btn-submit">Simpan Model 3D</button>
          <button type="button" class="btn-secondary" onclick="closeItemModal()">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Tab Navigation
    function switchTab(tabId, el) {
      document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
      document.getElementById('tab-' + tabId).classList.add('active');
      if (el) el.classList.add('active');
    }

    // Modal Category
    function openCatModal() { document.getElementById('modal-category').classList.add('active'); }
    function closeCatModal() { 
        document.getElementById('modal-category').classList.remove('active');
        resetCatForm();
    }
    function editCategory(id, name) {
        document.getElementById('form-category-title').innerText = "Edit Kategori";
        document.getElementById('cat_id').value = id;
        document.getElementById('cat_name').value = name;
        openCatModal();
    }
    function resetCatForm() {
        document.getElementById('form-category-title').innerText = "Tambah Kategori";
        document.getElementById('cat_id').value = '';
        document.getElementById('form-category').reset();
    }

    // Dynamic Specs Logic
    function addSpecRow(key, val) {
        const container = document.getElementById('specs-container');
        const row = document.createElement('div');
        row.className = 'spec-row';
        row.innerHTML = `
            <input type="text" name="spec_keys[]" class="form-control" placeholder="Cth: Material" value="${key}">
            <input type="text" name="spec_values[]" class="form-control" placeholder="Cth: Besi Baja" value="${val}">
            <button type="button" class="btn-remove-spec" onclick="this.parentElement.remove()">X</button>
        `;
        container.appendChild(row);
    }

    // Modal Item
    function openItemModal() { document.getElementById('modal-item').classList.add('active'); }
    function closeItemModal() {
        document.getElementById('modal-item').classList.remove('active');
        resetItemForm();
    }
    function editItem(itemData) {
        document.getElementById('form-item-title').innerText = "Edit Model 3D";
        document.getElementById('item_id').value = itemData.id;
        document.getElementById('category_id').value = itemData.category_id;
        document.getElementById('item_name').value = itemData.name;
        document.getElementById('tag').value = itemData.tag;
        document.getElementById('description').value = itemData.description;
        
        // Handle path
        document.getElementById('existing_path').value = itemData.path;
        document.getElementById('existing-path-info').innerText = "Path saat ini: " + itemData.path + " (Biarkan area upload kosong jika tidak diubah)";

        // Handle bg_color
        if (itemData.bg_color && itemData.bg_color.trim() !== '') {
            document.getElementById('bg_color').value = itemData.bg_color;
            document.getElementById('auto_color').checked = false;
            document.getElementById('bg_color').disabled = false;
        } else {
            document.getElementById('bg_color').value = '#1a1f33';
            document.getElementById('auto_color').checked = true;
            document.getElementById('bg_color').disabled = true;
        }
        
        // Handle specs
        document.getElementById('specs-container').innerHTML = '';
        if (itemData.custom_specs && itemData.custom_specs !== 'null') {
            try {
                const specs = JSON.parse(itemData.custom_specs);
                for(let key in specs) {
                    addSpecRow(key, specs[key]);
                }
            } catch(e) {}
        }
        
        openItemModal();
    }
    function resetItemForm() {
        document.getElementById('form-item-title').innerText = "Tambah Model 3D";
        document.getElementById('item_id').value = '';
        document.getElementById('form-item').reset();
        document.getElementById('existing_path').value = '';
        document.getElementById('existing-path-info').innerText = '';
        document.getElementById('specs-container').innerHTML = '';
        
        document.getElementById('bg_color').value = '#1a1f33';
        document.getElementById('auto_color').checked = true;
        document.getElementById('bg_color').disabled = true;
        
        // Reset drag & drop display
        document.getElementById('file-name-display').style.display = 'none';
        document.getElementById('file-name-display').innerText = '';
        // Init 1 empty spec row
        addSpecRow('', '');
    }

    // Init 1 empty spec row on load
    addSpecRow('', '');

    // Drag & Drop Upload Logic
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('file_3d');
    const fileNameDisplay = document.getElementById('file-name-display');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });
    function preventDefaults (e) { e.preventDefault(); e.stopPropagation(); }

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => uploadArea.classList.add('dragover'), false);
    });
    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => uploadArea.classList.remove('dragover'), false);
    });

    uploadArea.addEventListener('drop', handleDrop, false);
    function handleDrop(e) {
        let dt = e.dataTransfer;
        let files = dt.files;
        if (files.length > 0) {
            fileInput.files = files; // Assign files to input
            updateFileName();
        }
    }
    fileInput.addEventListener('change', updateFileName);
    function updateFileName() {
        if (fileInput.files.length > 0) {
            fileNameDisplay.innerText = "File terpilih: " + fileInput.files[0].name;
            fileNameDisplay.style.display = 'block';
        } else {
            fileNameDisplay.style.display = 'none';
        }
    }
  </script>
</body>
</html>