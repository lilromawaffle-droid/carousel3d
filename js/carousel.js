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
   5. STUDIO LIGHTING
   ========================================================================= */
const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
scene.add(ambientLight);

const keyLight = new THREE.DirectionalLight(0xffffff, 1.2);
keyLight.position.set(5, 8, 5);
keyLight.castShadow = true;
keyLight.shadow.mapSize.width = 2048;
keyLight.shadow.mapSize.height = 2048;
keyLight.shadow.bias = -0.0001;
scene.add(keyLight);

const fillLight = new THREE.DirectionalLight(0x8292ff, 0.6);
fillLight.position.set(-5, -2, -3);
scene.add(fillLight);

const rimLight = new THREE.PointLight(0xff5ef7, 0.8, 15);
rimLight.position.set(0, 3, -5);
scene.add(rimLight);

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

      // Initial DB scale
      const s = prod.scale || 1;
      model.scale.set(s, s, s);
      if (prod.position && prod.position.length === 3) {
          model.position.set(prod.position[0], prod.position[1], prod.position[2]);
      }

      // Centering pivot point
      model.updateMatrixWorld(true);
      const box = new THREE.Box3().setFromObject(model);
      const center = box.getCenter(new THREE.Vector3());
      model.position.sub(center);

      // Auto-scale to normalize sizes
      const size = new THREE.Vector3();
      box.getSize(size);
      const maxDim = Math.max(size.x, size.y, size.z);
      if (maxDim > 0) {
        const targetVisualSize = 4.5; 
        const autoScaleFactor = (targetVisualSize / maxDim) * s;
        model.scale.set(autoScaleFactor, autoScaleFactor, autoScaleFactor);
      }

      // Shadow and material transparent setup
      model.traverse((child) => {
        if (child.isMesh) {
          child.castShadow = true;
          child.receiveShadow = true;
          if (child.material) {
            if (Array.isArray(child.material)) {
              child.material = child.material.map(m => {
                const cloned = m.clone();
                cloned.transparent = true;
                cloned.side = THREE.DoubleSide;
                return cloned;
              });
            } else {
              child.material = child.material.clone();
              child.material.transparent = true;
              child.material.side = THREE.DoubleSide;
            }
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

    // Auto-rotation & drag behavior
    if (isCenter) {
      // Rotasi yang halus berdasar target drag mouse (dengan damping 0.1)
      group.rotation.y += (targetRotationY - group.rotation.y) * 0.1;
      group.rotation.x += (targetRotationX - group.rotation.x) * 0.1;

      // Auto-rotate aktif jika tidak sedang dizoom & tidak sedang didrag
      if (!isZoomed && !isDragging) {
        targetRotationY += 0.003;
      }
    } else {
      // Putar perlahan objek samping secara Y saja
      group.rotation.y += 0.008 * Math.sign(diff || 1);
      // Kembalikan rotasi X ke 0 jika sebelumnya diubah ketika jadi objek tengah
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
