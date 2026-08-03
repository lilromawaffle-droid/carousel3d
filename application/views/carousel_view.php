<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Interactive 3D Product Carousel</title>
  <!-- Modern Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
  
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    @property --target-bg-color {
      syntax: '<color>';
      inherits: true;
      initial-value: #1a1f33;
    }

    body, html {
      width: 100%;
      height: 100%;
      overflow: hidden;
      background: radial-gradient(circle at 50% 50%, var(--target-bg-color, #1a1f33) 0%, #090b10 100%);
      transition: --target-bg-color 0.7s ease;
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      color: #ffffff;
    }

    /* 3D Viewport Container */
    #canvas-container {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 1;
      cursor: grab;
    }
    #canvas-container:active {
      cursor: grabbing;
    }

    /* Ambient Background Glows */
    .glow-bg {
      position: absolute;
      width: 500px;
      height: 500px;
      border-radius: 50%;
      filter: blur(120px);
      pointer-events: none;
      z-index: 0;
    }
    .glow-1 {
      top: 10%;
      left: 20%;
      background: var(--target-bg-color);
      opacity: 0.25;
      transition: background 1.5s ease-in-out;
    }
    .glow-2 {
      bottom: 10%;
      right: 20%;
      background: var(--target-bg-color);
      opacity: 0.15;
      transition: background 1.5s ease-in-out;
    }

    /* Header / Navbar */
    header {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      padding: 24px 40px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      z-index: 10;
      pointer-events: none;
    }
    .brand {
      font-family: 'Outfit', sans-serif;
      font-size: 22px;
      font-weight: 800;
      letter-spacing: 0.05em;
      background: linear-gradient(90deg, #ffffff, #9ba5ff);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .brand-badge {
      font-size: 11px;
      padding: 4px 10px;
      border-radius: 20px;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.15);
      color: #cbd3ff;
      letter-spacing: 0.02em;
    }

    /* Category Navigation */
    .category-tabs {
      position: absolute;
      bottom: 110px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 12px;
      pointer-events: auto;
      z-index: 10;
      transition: opacity 0.3s ease;
    }
    .category-tabs.hidden {
      opacity: 0;
      pointer-events: none;
    }
    .cat-btn {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #a3a8b8;
      font-family: 'Inter', sans-serif;
      font-size: 13px;
      font-weight: 500;
      padding: 8px 16px;
      border-radius: 20px;
      cursor: pointer;
      transition: all 0.25s ease;
    }
    .cat-btn:hover {
      color: #ffffff;
      background: rgba(255, 255, 255, 0.08);
      border-color: rgba(255, 255, 255, 0.2);
    }
    .cat-btn.active {
      color: #ffffff;
      background: linear-gradient(135deg, #6d7bff, #535ff5);
      border-color: transparent;
      box-shadow: 0 4px 12px rgba(109, 123, 255, 0.3);
    }

    /* Guidance Info */
    #info {
      position: absolute;
      top: 90px;
      left: 40px;
      color: #eaeaea;
      font-size: 14px;
      opacity: 0.6;
      pointer-events: none;
      transition: opacity 0.3s ease;
      z-index: 10;
    }
    #info.hidden { opacity: 0; }

    /* Object Label */
    #object-label {
      position: absolute;
      bottom: 36px;
      left: 50%;
      transform: translateX(-50%);
      color: #eaeaea;
      font-size: 15px;
      letter-spacing: 0.04em;
      opacity: 0.75;
      pointer-events: none;
      transition: opacity 0.3s ease;
      z-index: 10;
    }
    #object-label.hidden { opacity: 0; }

    /* Navigation Arrows */
    .nav-arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 48px;
      height: 48px;
      border-radius: 50%;
      border: 1px solid rgba(255,255,255,0.15);
      background: rgba(255,255,255,0.04);
      color: #eaeaea;
      font-size: 20px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.2s ease, opacity 0.3s ease, transform 0.2s ease;
      z-index: 10;
    }
    .nav-arrow:hover {
      background: rgba(255,255,255,0.12);
    }
    .nav-arrow:active {
      transform: translateY(-50%) scale(0.92);
    }
    .nav-arrow.hidden {
      opacity: 0;
      pointer-events: none;
    }
    #arrow-left { left: 28px; }
    #arrow-right { right: 28px; }

    /* Dots Indicators */
    #dots {
      position: absolute;
      bottom: 68px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 8px;
      transition: opacity 0.3s ease;
      z-index: 10;
    }
    #dots.hidden {
      opacity: 0;
      pointer-events: none;
    }
    .dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: rgba(255,255,255,0.25);
      transition: background 0.25s ease, transform 0.25s ease;
      cursor: pointer;
    }
    .dot.active {
      background: #6d7bff;
      transform: scale(1.4);
    }

    /* Left Side Panel (Glass Card) */
    #side-panel {
      position: absolute;
      top: 50%;
      left: 40px;
      transform: translateY(-50%) translateX(-20px);
      width: 380px;
      max-width: 90vw;
      background: rgba(15, 17, 26, 0.55);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 24px;
      box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.1);
      box-sizing: border-box;
      padding: 32px;
      color: #ffffff;
      opacity: 0;
      transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s ease;
      pointer-events: none;
      z-index: 15;
    }
    #side-panel.visible {
      transform: translateY(-50%) translateX(0);
      opacity: 1;
      pointer-events: auto;
    }
    .close-btn {
      position: absolute;
      top: 20px;
      right: 20px;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.15);
      color: #ffffff;
      font-size: 14px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
      z-index: 20;
    }
    .close-btn:hover {
      background: rgba(255, 255, 255, 0.18);
    }
    .panel-tag {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      font-weight: 600;
      color: #8292ff;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      margin-bottom: 12px;
    }
    #side-panel h2 {
      font-family: 'Outfit', sans-serif;
      font-size: 28px;
      font-weight: 700;
      line-height: 1.25;
      margin-bottom: 16px;
      color: #ffffff;
    }
    #panel-desc {
      font-size: 15px; color: #a3a8b8; line-height: 1.6; margin-bottom: 30px;
      white-space: pre-wrap; /* This preserves new lines! */
    }

    /* Specs List */
    #side-panel .spec-list {
      margin-top: 24px;
      border-top: 1px solid rgba(255,255,255,0.08);
      padding-top: 20px;
    }
    #side-panel .spec-row {
      display: flex;
      justify-content: space-between;
      font-size: 13px;
      padding: 8px 0;
      color: #9a9da5;
    }
    #side-panel .spec-row span:last-child {
      color: #eaeaea;
    }

    /* Loading Screen */
    #loading-screen {
      position: absolute;
      inset: 0;
      background: #090b10;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 50;
      transition: opacity 0.5s ease;
    }
    #loading-screen.hidden {
      opacity: 0;
      pointer-events: none;
    }
    .progress-container {
      width: 280px;
      height: 4px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 2px;
      overflow: hidden;
      margin-bottom: 20px;
      position: relative;
    }
    .progress-bar {
      width: 0%;
      height: 100%;
      background: linear-gradient(90deg, #6d7bff, #ff5ef7);
      border-radius: 2px;
      transition: width 0.3s ease;
    }
    .loading-text {
      font-family: 'Outfit', sans-serif;
      font-size: 15px;
      color: #e0e4f5;
      letter-spacing: 0.05em;
    }

    /* Responsive */
    @media (max-width: 900px) {
      header {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
        padding: 16px 20px;
      }
      .category-tabs {
        bottom: 110px;
        width: calc(100% - 40px);
        left: 20px;
        transform: none;
        overflow-x: auto;
        padding: 4px 10px;
        white-space: nowrap;
        display: flex;
        flex-wrap: nowrap;
        justify-content: flex-start;
      }
      .cat-btn {
        flex-shrink: 0;
      }
      #side-panel {
        top: auto;
        bottom: 20px;
        left: 20px;
        width: calc(100% - 40px);
        max-width: 100vw;
        background: rgba(15, 17, 26, 0.65);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.3);
        transform: translateY(20px);
        transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s ease;
        padding: 24px;
      }
      .close-btn {
        top: 16px;
        right: 16px;
      }
      #side-panel.visible {
        transform: translateY(0);
      }
      #info {
        top: 80px;
        left: 20px;
      }
      #arrow-left { left: 15px; }
      #arrow-right { right: 15px; }
    }
  </style>
</head>
<body>

  <div class="glow-bg glow-1"></div>
  <div class="glow-bg glow-2"></div>

  <!-- Loading Screen -->
  <div id="loading-screen">
    <div class="progress-container">
      <div class="progress-bar" id="progress-bar-fill"></div>
    </div>
    <div class="loading-text" id="loading-text">MEMUAT MODEL 3D... 0%</div>
  </div>

  <!-- Header -->
  <header>
    <div class="brand">
      <span>3D SHOWCASE</span>
      <span class="brand-badge">INTERACTIVE VIEW</span>
    </div>
    <a href="?c=Admin" style="
      background: linear-gradient(135deg, rgba(255, 94, 247, 0.2), rgba(109, 123, 255, 0.2));
      border: 1px solid rgba(255,255,255,0.15);
      color: #fff;
      text-decoration: none;
      padding: 8px 16px;
      border-radius: 20px;
      font-family: 'Inter', sans-serif;
      font-size: 13px;
      font-weight: 600;
      pointer-events: auto;
      transition: all 0.3s ease;
    " onmouseover="this.style.background='linear-gradient(135deg, rgba(255, 94, 247, 0.4), rgba(109, 123, 255, 0.4))'" onmouseout="this.style.background='linear-gradient(135deg, rgba(255, 94, 247, 0.2), rgba(109, 123, 255, 0.2))'">
      ⚙️ Admin Panel
    </a>
  </header>

  <!-- Guidance Info -->
  <div id="info">Klik model tengah untuk zoom &middot; pakai panah atau klik objek samping untuk pindah</div>
  
  <!-- Navigation Arrows -->
  <button id="arrow-left" class="nav-arrow">&#8592;</button>
  <button id="arrow-right" class="nav-arrow">&#8594;</button>

  <!-- Category Tabs Selector (Moved below the object) -->
  <div class="category-tabs" id="category-tabs-container">
    <!-- Buttons will be generated by JS -->
  </div>

  <!-- Object Label -->
  <div id="object-label"></div>

  <!-- Dots Indicators -->
  <div id="dots"></div>

  <!-- Right Slide-Out Side Panel -->
  <div id="side-panel">
    <button id="close-panel-btn" class="close-btn" title="Tutup Detail">&#10005;</button>
    <div class="panel-tag">✨ <span id="item-tag">Featured Product</span></div>
    <h2 id="panel-title">-</h2>
    <p id="panel-desc">-</p>
    
    <!-- Specs List (Dynamic) -->
    <div class="spec-list" id="dynamic-specs-container">
      <!-- Specs will be injected here by JS -->
    </div>

    <!-- Buy Button -->
  </div>

  <!-- 3D Canvas -->
  <div id="canvas-container"></div>

  <!-- Three.js and Multi-Format Loaders -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/fflate@0.8.2/umd/index.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/FBXLoader.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/OBJLoader.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/environments/RoomEnvironment.js"></script>
  
  <!-- Inject PHP Data to JS -->
  <script>
    const serverCategories = <?= $categories_json ?>;
  </script>

  <!-- Main Logic -->
  <script src="js/carousel.js?v=<?= time() ?>" defer></script>
</body>
</html>