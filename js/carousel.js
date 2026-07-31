/* =========================================================================
   3D CAROUSEL JAVASCRIPT LOGIC (js/carousel.js)
   ========================================================================= */

/* =========================================================================
   1. DATA KATEGORI DAN BARANG 3D
   ========================================================================= */
// Data is now loaded dynamically from PHP via 'serverCategories' global variable.
const categories = typeof serverCategories !== 'undefined' ? serverCategories : {};


/* =========================================================================
   2. STATE VARIABLES
   ========================================================================= */
const categoryKeys = Object.keys(categories);
let currentCategoryKey = categoryKeys.length > 0 ? categoryKeys[0] : null;
let products = currentCategoryKey ? categories[currentCategoryKey].items : [];
let currentProductIndex = 0;
let N = products.length;
let groups = new Array(N);
let loadedCount = 0;
let isZoomed = false;

/* =========================================================================
   2.1. GENERATE CATEGORY TABS DYNAMICALLY
   ========================================================================= */
const tabsContainer = document.getElementById('category-tabs-container');
if (tabsContainer && categoryKeys.length > 0) {
  tabsContainer.innerHTML = ''; // Clear fallback if any
  categoryKeys.forEach((key, index) => {
    const btn = document.createElement('button');
    btn.className = 'cat-btn' + (index === 0 ? ' active' : '');
    btn.dataset.cat = key;
    btn.textContent = categories[key].name;
    tabsContainer.appendChild(btn);
  });
}


/* =========================================================================
   3. SETUP THREE.JS SCENE, CAMERA & RENDERER
   ========================================================================= */
const container = document.getElementById('canvas-container');
const loadingScreen = document.getElementById('loading-screen');

const scene = new THREE.Scene();

const camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 1000);
camera.position.set(0, 0.0, 15.0); // Overview distance (centered vertically)

const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
renderer.shadowMap.enabled = true;
renderer.shadowMap.type = THREE.PCFSoftShadowMap;
renderer.outputEncoding = THREE.sRGBEncoding;

// 🟢 TONE MAPPING: Mencegah efek "putih surga" (over-exposed) pada material putih/terang!
renderer.toneMapping = THREE.ACESFilmicToneMapping;
renderer.toneMappingExposure = 0.8; // Turunkan exposure (kecerahan kamera) agar lebih natural

container.appendChild(renderer.domElement);

/* =========================================================================
   4. CUSTOM DRAG ROTATION FOR ACTIVE OBJECT
   ========================================================================= */
let isDragging = false;
let targetRotationY = 0;
let targetRotationX = 0;
let previousMousePosition = { x: 0, y: 0 };
let mouseDownPos = { x: 0, y: 0 };

container.addEventListener('pointerdown', (event) => {
  mouseDownPos.x = event.clientX;
  mouseDownPos.y = event.clientY;
  
  isDragging = true;
  previousMousePosition.x = event.clientX;
  previousMousePosition.y = event.clientY;
});

container.addEventListener('pointermove', (event) => {
  if (isDragging) {
    const deltaX = event.clientX - previousMousePosition.x;
    const deltaY = event.clientY - previousMousePosition.y;

    targetRotationY += deltaX * 0.008;
    targetRotationX += deltaY * 0.008;

    // Limit vertical rotation (X axis) to prevent flipping
    targetRotationX = Math.max(-1.0, Math.min(1.0, targetRotationX));

    previousMousePosition.x = event.clientX;
    previousMousePosition.y = event.clientY;
  }
});

window.addEventListener('pointerup', (event) => {
  if (isDragging) {
    isDragging = false;
  }
});

/* =========================================================================
   5. STUDIO LIGHTING (PENGATURAN CAHAYA)
   ========================================================================= */
// 1. Ambient Light (Cahaya dasar yang mengisi ke seluruh sudut ruangan)
// Karena kita sudah pakai RoomEnvironment, ambient light wajib diredupkan drastis!
const ambientLight = new THREE.AmbientLight(0xffffff, 0.2); // Turunkan ke 0.2
scene.add(ambientLight);

// 2. Key Light (Sorotan Lampu Utama)
// Posisi .set(X, Y, Z) -> X(Kiri/Kanan), Y(Bawah/Atas), Z(Belakang/Depan)
const keyLight = new THREE.DirectionalLight(0xffffff, 0.5); // Turunkan ke 0.5 (HDRI sudah cukup terang)
keyLight.position.set(3, 5, 8); // Dipindah lebih ke DEPAN (Z=8) dan lebih rendah (Y=5) agar menerangi "wajah" objek
keyLight.castShadow = true;
keyLight.shadow.mapSize.width = 2048;
keyLight.shadow.mapSize.height = 2048;
keyLight.shadow.bias = -0.0001;
scene.add(keyLight);

// 3. Fill Light (Lampu Pengisi dari sisi berlawanan agar bagian gelap tetap terlihat)
const fillLight = new THREE.DirectionalLight(0x8292ff, 0.3); // Turunkan ke 0.3
fillLight.position.set(-5, 2, 5); // Dipindah ke Kiri Depan agar menyeimbangkan bayangan dari Key Light
scene.add(fillLight);

// 4. Rim Light (Lampu hiasan belakang / Backlight warna pink)
const rimLight = new THREE.PointLight(0xff5ef7, 0.4, 15); // Turunkan ke 0.4
rimLight.position.set(0, 3, -5);
scene.add(rimLight);

// 5. HDRI / RoomEnvironment (Agar Logam/Kaca memantulkan lingkungan)
const pmremGenerator = new THREE.PMREMGenerator(renderer);
scene.environment = pmremGenerator.fromScene(new THREE.RoomEnvironment(), 0.04).texture;

/* =========================================================================
   6. LOADERS & LOAD PRODUCTS IN PARALLEL
   ========================================================================= */
const gltfLoader = new THREE.GLTFLoader();
const fbxLoader = new THREE.FBXLoader();
const objLoader = new THREE.OBJLoader();

// Track progress masing-masing model
const loadProgressArray = new Array(N).fill(0);
const progressBarFill = document.getElementById('progress-bar-fill');
const loadingText = document.getElementById('loading-text');

function updateOverallProgress() {
  const sum = loadProgressArray.reduce((acc, val) => acc + val, 0);
  const avg = Math.round(sum / N);
  progressBarFill.style.width = avg + '%';
  loadingText.textContent = `MEMUAT MODEL 3D... ${avg}%`;
}

function loadAllProducts() {
  loadingScreen.classList.remove('hidden');

  if (N === 0) {
    loadingScreen.classList.add('hidden');
    updateUI();
    return;
  }

  products.forEach((prod, i) => {
    const group = new THREE.Group();
    scene.add(group);
    groups[i] = group;
    group.userData.opacity = 1.0;

    const ext = prod.path.split('.').pop().toLowerCase();

    const checkAllLoaded = () => {
      loadProgressArray[i] = 100;
      updateOverallProgress();
      
      loadedCount++;
      if (loadedCount === N) {
        setTimeout(() => {
          loadingScreen.classList.add('hidden');
        }, 400); // Delay sedikit agar progress 100% sempat terlihat
        updateUI();
      }
    };

    const onProgress = (xhr) => {
      if (xhr.lengthComputable && xhr.total > 0) {
        const percent = (xhr.loaded / xhr.total) * 100;
        loadProgressArray[i] = Math.min(99, percent); // Batasi ke 99% sampai onload selesai
        updateOverallProgress();
      }
    };

    const onLoaded = (loadedData) => {
      const model = (ext === 'glb' || ext === 'gltf') ? loadedData.scene : loadedData;

      // Reset posisi dulu
      model.position.set(0, 0, 0);
      model.updateMatrixWorld(true);
      
      // Auto-Scale (Menyesuaikan skala ekspor FBX (cm) vs GLB (meter) agar tidak kebesaran/kekecilan)
      const box = new THREE.Box3().setFromObject(model);
      const size = box.getSize(new THREE.Vector3());
      const maxDim = Math.max(size.x, size.y, size.z);
      
      if (maxDim > 0) {
        const autoScale = (6.0 / maxDim) * (prod.scale || 1.0); // Target ukuran ~6.0 unit di layar
        model.scale.setScalar(autoScale);
      } else {
        model.scale.setScalar(prod.scale || 1.0);
      }

      // Centering pivot point (Auto-Center)
      model.updateMatrixWorld(true);
      const boxScaled = new THREE.Box3().setFromObject(model);
      const center = boxScaled.getCenter(new THREE.Vector3());
      model.position.sub(center);
      
      // Tambahkan offset manual jika didefinisikan di produk
      if (prod.position) {
         model.position.x += prod.position[0];
         model.position.y += prod.position[1];
         model.position.z += prod.position[2];
      }

      // Shadow and material transparent setup
      model.traverse((child) => {
        if (child.isMesh) {
          child.castShadow = true;
          child.receiveShadow = true;
          if (child.material) {
            let mats = Array.isArray(child.material) ? child.material : [child.material];
            mats.forEach((m, idx) => {
              const cloned = m.clone();
              cloned.transparent = true;
              cloned.side = THREE.DoubleSide;
              
              // Tweak untuk Kaca & Metal (Memperbaiki shading/FBX export)
              if (cloned.opacity < 0.9 || cloned.transmission > 0) {
                cloned.transparent = true;
                cloned.depthWrite = false; // Kaca jangan menutupi render di belakangnya
                cloned.roughness = 0.1;
              }
              // Jika metalness tinggi banget, turunkan sedikit agar cahaya lampu mempan
              if (cloned.metalness > 0.8) {
                cloned.metalness = 0.7;
                cloned.roughness = Math.max(cloned.roughness, 0.15);
              }
              
              mats[idx] = cloned;
            });
            child.material = Array.isArray(child.material) ? mats : mats[0];
          }
        }
      });

      group.add(model);
      
      // Initial hide scale to pop-in
      group.scale.set(0.1, 0.1, 0.1);
      checkAllLoaded();
    };

    const onError = (err) => {
      console.warn(`Model gagal dimuat dari path: ${prod.path}. Menampilkan placeholder...`, err);
      
      // Torus Knot Placeholder
      const geom = new THREE.TorusKnotGeometry(1.0, 0.3, 128, 32);
      const mat = new THREE.MeshStandardMaterial({
        color: i === 0 ? 0x6d7bff : (i === 1 ? 0xff5ef7 : 0x8292ff),
        roughness: 0.2,
        metalness: 0.8,
        transparent: true
      });
      const placeholder = new THREE.Mesh(geom, mat);
      placeholder.castShadow = true;
      group.add(placeholder);

      group.scale.set(0.1, 0.1, 0.1);
      checkAllLoaded();
    };

    if (ext === 'fbx') {
      fbxLoader.load(prod.path, onLoaded, onProgress, onError);
    } else if (ext === 'obj') {
      objLoader.load(prod.path, onLoaded, onProgress, onError);
    } else {
      gltfLoader.load(prod.path, onLoaded, onProgress, onError);
    }
  });
}

function setGroupOpacity(group, opacity) {
  if (!group) return;
  group.traverse((child) => {
    if (child.isMesh) {
      if (Array.isArray(child.material)) {
        child.material.forEach(m => { m.opacity = opacity; });
      } else {
        child.material.opacity = opacity;
      }
    }
  });
}

function circularDiff(i, current, n) {
  let diff = i - current;
  if (diff > n / 2) diff -= n;
  if (diff < -n / 2) diff += n;
  return diff;
}

/* =========================================================================
   7. SMOOTH ZOOM LOGIC
   ========================================================================= */
let targetZoomDistance = 15.0;

function triggerZoom(zoomIn) {
  isZoomed = zoomIn;
  targetZoomDistance = zoomIn ? 7.0 : 15.0;
  
  const sidePanel = document.getElementById('side-panel');
  const info = document.getElementById('info');
  const objectLabel = document.getElementById('object-label');
  const dots = document.getElementById('dots');
  const arrowLeft = document.getElementById('arrow-left');
  const arrowRight = document.getElementById('arrow-right');
  const categoryTabs = document.querySelector('.category-tabs');

  if (zoomIn) {
    sidePanel.classList.add('visible');
    info.classList.add('hidden');
    objectLabel.classList.add('hidden');
    dots.classList.add('hidden');
    arrowLeft.classList.add('hidden');
    arrowRight.classList.add('hidden');
    if (categoryTabs) categoryTabs.classList.add('hidden');
  } else {
    sidePanel.classList.remove('visible');
    info.classList.remove('hidden');
    objectLabel.classList.remove('hidden');
    if (categoryTabs) categoryTabs.classList.remove('hidden');
    
    // Hanya tampilkan tombol navigasi (arrows & dots) jika jumlah barang > 1
    if (N > 1) {
      dots.classList.remove('hidden');
      arrowLeft.classList.remove('hidden');
      arrowRight.classList.remove('hidden');
    } else {
      dots.classList.add('hidden');
      arrowLeft.classList.add('hidden');
      arrowRight.classList.add('hidden');
    }
  }
}

/* =========================================================================
   7.5 BACKGROUND DYNAMIC COLOR
   ========================================================================= */
const dominantColorsCache = {};
let needsColorUpdate = false;
let framesSinceActive = 0;

function updateAmbientColor() {
  const currentGroup = groups[currentProductIndex];
  if (!currentGroup || !currentGroup.visible) return;
  
  const prod = products[currentProductIndex];
  if (prod && prod.bg_color && prod.bg_color.trim() !== '') {
    applyBackgroundColor(new THREE.Color(prod.bg_color), true);
    return;
  }
  
  if (dominantColorsCache[currentProductIndex]) {
    applyBackgroundColor(dominantColorsCache[currentProductIndex]);
    return;
  }
  
  const size = 64; 
  const rt = new THREE.WebGLRenderTarget(size, size);
  
  const oldTarget = renderer.getRenderTarget();
  const oldClearColor = new THREE.Color();
  renderer.getClearColor(oldClearColor);
  const oldClearAlpha = renderer.getClearAlpha();
  
  renderer.setRenderTarget(rt);
  renderer.setClearColor(0x000000, 0);
  
  const oldVisibilities = groups.map(g => g ? g.visible : false);
  groups.forEach(g => { if (g) g.visible = false; });
  currentGroup.visible = true;

  // --- Matikan lampu warna-warni & environment agar warna objek ASLI terbaca ---
  const oldEnv = scene.environment;
  scene.environment = null;
  const lights = [];
  scene.traverse(child => {
    if (child.isLight) {
      lights.push({ light: child, intensity: child.intensity });
      child.intensity = 0; 
    }
  });
  const tempLight = new THREE.AmbientLight(0xffffff, 1.5);
  scene.add(tempLight);
  // -----------------------------------------------------------------------------

  renderer.render(scene, camera);
  
  const buffer = new Uint8Array(size * size * 4);
  renderer.readRenderTargetPixels(rt, 0, 0, size, size, buffer);
  
  // --- Kembalikan lampu seperti semula ---
  scene.remove(tempLight);
  lights.forEach(l => l.light.intensity = l.intensity);
  scene.environment = oldEnv;
  // ---------------------------------------
  
  groups.forEach((g, i) => { if (g) g.visible = oldVisibilities[i]; });
  renderer.setRenderTarget(oldTarget);
  renderer.setClearColor(oldClearColor, oldClearAlpha);
  rt.dispose();
  
  let r = 0, g = 0, b = 0, count = 0;
  for (let i = 0; i < buffer.length; i += 4) {
    if (buffer[i+3] > 10) { 
      r += buffer[i];
      g += buffer[i+1];
      b += buffer[i+2];
      count++;
    }
  }
  
  if (count > 0) {
    const c = new THREE.Color(`rgb(${Math.round(r/count)}, ${Math.round(g/count)}, ${Math.round(b/count)})`);
    const hsl = {};
    c.getHSL(hsl);
    
    // Jika objek warnanya terlalu abu-abu (netral) atau hitam/putih murni, 
    // kita bangkitkan warna unik dan cerah berdasarkan nama itemnya.
    if (hsl.s < 0.15 || hsl.l < 0.15 || hsl.l > 0.85) {
      const name = products[currentProductIndex].name || "Item";
      let hash = 0;
      for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
      }
      hsl.h = Math.abs(hash) % 360 / 360;
      hsl.s = 0.7; // Cukup cerah
      hsl.l = 0.5; // Tengah-tengah
    }
    
    const finalColor = new THREE.Color().setHSL(hsl.h, hsl.s, hsl.l);
    dominantColorsCache[currentProductIndex] = finalColor;
    applyBackgroundColor(finalColor);
  } else {
    applyBackgroundColor(new THREE.Color(0x1a1f33)); // fallback
  }
}

function applyBackgroundColor(color, isManual = false) {
  if (isManual) {
    document.documentElement.style.setProperty('--target-bg-color', '#' + color.getHexString());
    return;
  }
  
  const hsl = {};
  color.getHSL(hsl);
  hsl.s = Math.max(0.5, Math.min(0.9, hsl.s * 1.2)); // Boost saturation
  hsl.l = Math.max(0.10, Math.min(0.28, hsl.l)); // Jadikan cukup gelap untuk background
  const newColor = new THREE.Color().setHSL(hsl.h, hsl.s, hsl.l);
  
  document.documentElement.style.setProperty('--target-bg-color', '#' + newColor.getHexString());
}

/* =========================================================================
   8. UI UPDATE & CAROUSEL NAVIGATION
   ========================================================================= */
function goToIndex(newIndex) {
  currentProductIndex = ((newIndex % N) + N) % N;
  
  // Reset rotation variables for the new active product
  targetRotationX = 0;
  targetRotationY = products[currentProductIndex].rotationY || 0;
  
  // Instantly set rotation to avoid wild snapping
  if (groups[currentProductIndex]) {
    groups[currentProductIndex].rotation.set(0, targetRotationY, 0);
  }
  
  updateUI();
}

function updateUI() {
  if (N === 0) {
      document.getElementById('item-tag').textContent = 'Kosong';
      document.getElementById('panel-title').textContent = 'Kategori Kosong';
      document.getElementById('panel-desc').textContent = 'Belum ada model 3D di kategori ini.';
      
      const specsContainer = document.getElementById('dynamic-specs-container');
      if (specsContainer) {
          specsContainer.innerHTML = '';
      }
      document.getElementById('object-label').textContent = 'Belum ada model';
      document.getElementById('dots').innerHTML = '';
      return;
  }

  const prod = products[currentProductIndex];
  document.getElementById('item-tag').textContent = prod.tag || '-';
  document.getElementById('panel-title').textContent = prod.name || '-';
  document.getElementById('panel-desc').textContent = prod.desc || '-';

  // Render Dynamic Specs
  const specsContainer = document.getElementById('dynamic-specs-container');
  if (specsContainer) {
      specsContainer.innerHTML = '';
      if (prod.custom_specs && Object.keys(prod.custom_specs).length > 0) {
          for (let key in prod.custom_specs) {
              const val = prod.custom_specs[key];
              const row = document.createElement('div');
              row.className = 'spec-row';
              row.innerHTML = `<span>${key}</span><span>${val}</span>`;
              specsContainer.appendChild(row);
          }
      } else {
          specsContainer.innerHTML = '<div class="spec-row" style="color: #a3a8b8; font-size: 12px; font-style: italic;">Tidak ada spesifikasi tambahan.</div>';
      }
  }

  document.getElementById('object-label').textContent = `${prod.name} (${currentProductIndex + 1}/${N})`;

  // Update dots
  document.querySelectorAll('.dot').forEach((dot, i) => {
    dot.classList.toggle('active', i === currentProductIndex);
  });

  triggerZoom(false);
  
  needsColorUpdate = true;
  framesSinceActive = 0;
}

const dotsContainer = document.getElementById('dots');

function recreateDots() {
  dotsContainer.innerHTML = '';
  products.forEach((prod, i) => {
    const dot = document.createElement('div');
    dot.className = `dot ${i === currentProductIndex ? 'active' : ''}`;
    dot.addEventListener('click', (e) => {
      e.stopPropagation();
      if (!isZoomed) {
        goToIndex(i);
      }
    });
    dotsContainer.appendChild(dot);
  });
}

// Generate Dots Indicators on startup
recreateDots();

/* =========================================================================
   9. SWITCH CATEGORY LOGIC
   ========================================================================= */
function switchCategory(catKey) {
  if (isZoomed) {
    triggerZoom(false); // Zoom out first
  }

  // Hapus model lama dari scene
  groups.forEach(group => {
    if (group) {
      scene.remove(group);
    }
  });
  groups.fill(null);
  
  // Clear dominant color cache for new category
  for (let key in dominantColorsCache) delete dominantColorsCache[key];

  // Ganti list produk dan kategori aktif
  currentCategoryKey = catKey;
  products = categories[catKey].items;
  N = products.length;
  groups = new Array(N);
  loadedCount = 0;
  currentProductIndex = 0;

  // Re-create progress array untuk loading bar
  loadProgressArray.length = N;
  loadProgressArray.fill(0);
  progressBarFill.style.width = '0%';

  // Buat ulang indikator dots
  recreateDots();

  // Reset rotasi
  targetRotationX = 0;
  targetRotationY = products.length > 0 ? (products[0].rotationY || 0) : 0;

  // Muat produk baru
  loadAllProducts();

  // Update button active state
  document.querySelectorAll('.cat-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.cat === catKey);
  });
}

// Category Tabs Event Listeners
document.querySelectorAll('.cat-btn').forEach(btn => {
  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    const catKey = btn.dataset.cat;
    if (catKey !== currentCategoryKey) {
      switchCategory(catKey);
    }
  });
});

// Arrow Buttons Event Listeners
const arrowLeft = document.getElementById('arrow-left');
const arrowRight = document.getElementById('arrow-right');

arrowLeft.addEventListener('click', (e) => {
  e.stopPropagation();
  if (!isZoomed) goToIndex(currentProductIndex - 1);
});

arrowRight.addEventListener('click', (e) => {
  e.stopPropagation();
  if (!isZoomed) goToIndex(currentProductIndex + 1);
});

// Close Panel Button Listener
const closePanelBtn = document.getElementById('close-panel-btn');
closePanelBtn.addEventListener('click', (e) => {
  e.stopPropagation();
  triggerZoom(false);
});

// Start loading models
loadAllProducts();

/* =========================================================================
   10. KLIK INTERAKSI (RAYCASTER + DRAG CHECK)
   ========================================================================= */
const raycaster = new THREE.Raycaster();
const mouse = new THREE.Vector2();

container.addEventListener('pointerup', (event) => {
  // Hitung jarak pergeseran kursor mouse dari awal klik
  const dist = Math.hypot(event.clientX - mouseDownPos.x, event.clientY - mouseDownPos.y);
  // Jika geser lebih dari 5 pixel, berarti sedang drag/orbit kamera, bukan klik inspeksi
  if (dist > 5) return;

  mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
  mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;

  raycaster.setFromCamera(mouse, camera);

  const allMeshes = [];
  groups.forEach((g) => {
    if (g && g.visible) {
      g.traverse((child) => { if (child.isMesh) allMeshes.push(child); });
    }
  });

  const intersects = raycaster.intersectObjects(allMeshes, true);
  if (intersects.length > 0) {
    const hitMesh = intersects[0].object;
    const hitIndex = groups.findIndex((g) => {
      if (!g) return false;
      let found = false;
      g.traverse((child) => { if (child === hitMesh) found = true; });
      return found;
    });

    if (hitIndex !== -1) {
      if (hitIndex === currentProductIndex) {
        // Klik model tengah -> Zoom-In & buka panel detail
        triggerZoom(true);
      } else {
        // Klik model samping -> Pindah ke model tersebut
        goToIndex(hitIndex);
      }
    }
  } else {
    // Klik di background kosong -> Zoom-Out
    triggerZoom(false);
  }
});

/* =========================================================================
   11. ANIMATION LOOP
   ========================================================================= */
function animate() {
  requestAnimationFrame(animate);
  
  if (needsColorUpdate) {
    framesSinceActive++;
    if (framesSinceActive > 5) {
      updateAmbientColor();
      needsColorUpdate = false;
    }
  }
  
  // Zoom transition (smooth lerp distance)
  const currentDist = camera.position.length();
  if (Math.abs(currentDist - targetZoomDistance) > 0.02) {
    const newDist = THREE.MathUtils.lerp(currentDist, targetZoomDistance, 0.08);
    camera.position.normalize().multiplyScalar(newDist);
  }

  // Update positions, scaling and opacity of carousel items
  groups.forEach((group, i) => {
    if (!group) return;
    const diff = circularDiff(i, currentProductIndex, N);
    const isCenter = i === currentProductIndex;
    const dist = Math.abs(diff);

    const spacing = 6.5; // Jarak antar objek diperlebar lebih jauh
    const targetX = diff * spacing;
    
    // Scale and opacity interpolation (objek pinggir terlihat sedikit memudar)
    const targetScale = isCenter ? 1.0 : 0.55;
    const targetOpacity = isCenter ? 1.0 : 0.35;

    // Position x interpolation
    group.position.x += (targetX - group.position.x) * 0.09;
    
    // Scale interpolation
    const newScale = group.scale.x + (targetScale - group.scale.x) * 0.09;
    group.scale.setScalar(newScale || 1);

    // Opacity interpolation
    group.userData.opacity += (targetOpacity - group.userData.opacity) * 0.09;
    setGroupOpacity(group, group.userData.opacity);

    // Objek samping selalu terlihat (dist <= 1), objek jauh disembunyikan
    group.visible = dist <= 1;

    // Rotasi Drag Behavior (TIDAK ADA AUTO-ROTATE LAGI!)
    if (isCenter) {
      // Rotasi yang halus berdasar target drag mouse (dengan damping 0.1)
      group.rotation.y += (targetRotationY - group.rotation.y) * 0.1;
      group.rotation.x += (targetRotationX - group.rotation.x) * 0.1;
    } else {
      // Kembalikan rotasi X dan Y ke 0 (menghadap depan) saat jadi objek samping
      group.rotation.y += (0 - group.rotation.y) * 0.1;
      group.rotation.x += (0 - group.rotation.x) * 0.1;
    }
  });

  renderer.render(scene, camera);
}
animate();

/* =========================================================================
   12. RESPONSIVE RESIZE
   ========================================================================= */
window.addEventListener('resize', () => {
  camera.aspect = window.innerWidth / window.innerHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(window.innerWidth, window.innerHeight);
});
