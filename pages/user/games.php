<?php
// User interactive VIP games lists and launches view page
require_once __DIR__ . '/../../includes/header-helper.php';
require_once __DIR__ . '/../../includes/vip-helper.php';
require_once __DIR__ . '/../../includes/settings-helper.php';

require_login();
$user_id = $_SESSION['user_id'];
$csrf_token = generate_csrf_token();
$vip = get_user_vip_details($user_id);
?>

<div class="space-y-4 fade-in">
     <!-- 1. Top visual guide header panel -->
     <div class="bg-teal-905 border border-teal-850 rounded-2xl p-4 text-white text-left relative overflow-hidden bg-gradient-to-r from-teal-900 to-teal-950">
          <div class="absolute -right-16 -bottom-16 w-32 h-32 rounded-full bg-teal-855 opacity-25"></div>
          <span class="block text-[9px] font-mono font-bold text-teal-300 tracking-wider uppercase">Hiburan & Keuntungan VIP</span>
          <h2 class="font-display font-bold text-base mt-0.5">VIP MINI-GAMES INTERAKTIF</h2>
          <p class="text-[10px] text-teal-200 mt-1 leading-relaxed">Nikmati permainan seru berhadiah saldo tunai cuma-cuma khusus anggota VIP aktif harian. Setiap tingkat tingkatan VIP memberikan hak jatah kuota permainan yang berbeda.</p>
     </div>

     <!-- Check system game master features togglers is off -->
     <?php if (!is_feature_enabled('game')): ?>
          <div class="bg-rose-50 border border-rose-200 rounded-xl p-6 text-center py-12">
               <svg class="w-10 h-10 text-rose-500 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
               </svg>
               <h4 class="font-bold text-xs text-[#12302F]">VIP Games Ditutup Sementara</h4>
               <p class="text-[11px] text-[#5B7774]">Administrator sedang menyesuaikan rasio kelipatan algoritma pembagian hadiah saldo game.</p>
          </div>
     <?php else: ?>

          <!-- 2. Presenting Interactive games loops lists cards -->
          <div class="space-y-3.5 text-left pb-1">
               
               <!-- GAME 1: GOSOK SALDO BERHADIAH -->
               <div class="bg-white rounded-2xl border border-teal-100 p-4 shadow-[0_4px_12px_rgba(15,118,110,0.01)] flex items-start gap-4 hover:border-[#0F766E] transition-all">
                    <div class="bg-amber-100 border border-amber-200 text-amber-850 w-11 h-11 rounded-xl flex items-center justify-center font-display font-bold text-sm shrink-0">
                         G1
                    </div>
                    <div class="space-y-1.5 flex-1">
                         <h3 class="font-display font-bold text-xs text-[#12302F]">Gosok Kartu Keberuntungan VIP</h3>
                         <p class="text-[10px] text-[#5B7774]">Gosok area bertinta perak dilayar untuk mendapatkan jackpot saldo hadiah kejutan s.d Rp 25.000!</p>
                         <button onclick="launchInteractiveGame('gosok')" class="h-8 px-3.5 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-[10px] font-bold transition-transform active:scale-95 shadow-sm inline-block">
                              Mainkan Game Gosok
                         </button>
                    </div>
               </div>

               <!-- GAME 2: PUZZLE BERWAKTU -->
               <div class="bg-white rounded-2xl border border-teal-100 p-4 shadow-[0_4px_12px_rgba(15,118,110,0.01)] flex items-start gap-4 hover:border-[#0F766E] transition-all">
                    <div class="bg-emerald-100 border border-emerald-200 text-emerald-850 w-11 h-11 rounded-xl flex items-center justify-center font-display font-bold text-sm shrink-0">
                         G2
                    </div>
                    <div class="space-y-1.5 flex-1">
                         <h3 class="font-display font-bold text-xs text-[#12302F]">Presisi Puzzle Kompresor Server</h3>
                         <p class="text-[10px] text-[#5B7774]">Hubungkan kabel rangkaian dalam waktu berdetik 15 detik untuk mendapatkan hadiah loyalitas.</p>
                         <button onclick="launchInteractiveGame('puzzle')" class="h-8 px-3.5 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-[10px] font-bold transition-transform active:scale-95 shadow-sm inline-block">
                              Mulai Hubungkan Kabel
                         </button>
                    </div>
               </div>

               <!-- GAME 3: HUJAN KOIN EMAS -->
               <div class="bg-white rounded-2xl border border-teal-100 p-4 shadow-[0_4px_12px_rgba(15,118,110,0.01)] flex items-start gap-4 hover:border-[#0F766E] transition-all">
                    <div class="bg-rose-100 border border-rose-200 text-rose-850 w-11 h-11 rounded-xl flex items-center justify-center font-display font-bold text-sm shrink-0">
                         G3
                    </div>
                    <div class="space-y-1.5 flex-1">
                         <h3 class="font-display font-bold text-xs text-[#12302F]">Kolektor Serbuk Koin Server</h3>
                         <p class="text-[10px] text-[#5B7774]">Klik serbuk koin emas yang berjatuhan dalam durasi 10 detik. Semakin banyak terkumpul, semakin tinggi bonus.</p>
                         <button onclick="launchInteractiveGame('hujan')" class="h-8 px-3.5 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-[10px] font-bold transition-transform active:scale-95 shadow-sm inline-block">
                              Tangkap Koin Emas
                         </button>
                    </div>
               </div>
          </div>
     <?php endif; ?>
</div>

<!-- Modal container wrapping HTML5 Canvas dynamic game loop games inside -->
<div id="game-playground-modal" class="fixed inset-0 bg-slate-900/95 backdrop-blur-sm z-50 flex flex-col items-center justify-center hidden p-4">
     <div class="bg-white rounded-2xl max-w-[430px] w-full p-5 text-center space-y-4 shadow-2xl relative">
          <!-- Close btn modal -->
          <button onclick="abortRunningPlayground()" class="absolute right-4.5 top-4 text-[#5B7774] focus:outline-none">
               <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
               </svg>
          </button>

          <h3 id="game-title-lbl" class="font-display font-bold text-sm text-[#0F766E] uppercase tracking-wider">Game Title</h3>
          
          <!-- Playground Canvas Block where interactivity takes place -->
          <div id="canvas-container" class="w-full h-64 bg-slate-100 border border-teal-100 rounded-xl overflow-hidden relative touch-none flex items-center justify-center shadow-inner">
               <canvas id="game-canvas" class="w-full h-full block bg-teal-50/10"></canvas>
               <!-- Floating indicator help text inside layout context -->
               <div id="game-live-score-badge" class="absolute top-2 left-2 bg-teal-900 text-white text-[9px] font-mono font-bold px-2 py-0.5 rounded shadow-sm">SCORE: 0</div>
               <div id="game-live-timer-badge" class="absolute top-2 right-2 bg-rose-600 text-white text-[9px] font-mono font-bold px-2 py-0.5 rounded shadow-sm">TIME: 0s</div>
               <div id="game-overlay-prompt-lbl" class="absolute inset-0 bg-teal-950/80 text-white p-6 flex flex-col justify-center items-center gap-2 select-none text-center">
                    <h4 class="font-bold text-sm" id="overlay-heading">Ketuk Layar Untuk Mulai</h4>
                    <p class="text-[10px] text-teal-200" id="overlay-desc">Mekanisme panduan bermain</p>
                    <button onclick="startGameEngineLoop()" class="h-8 px-4 bg-teal-600 hover:bg-teal-500 font-bold rounded-lg text-[10px] transition-transform active:scale-95 shadow mt-1">Mulai Bermain</button>
               </div>
          </div>

          <div class="text-[10px] text-[#5B7774]">
               Sisa Kuota Game Harian Anda (VIP): <strong class="text-teal-950"><?php echo $vip['daily_games_ quota']; ?> Kali</strong>
          </div>
     </div>
</div>

<script>
let currentActiveGameKey = '';
let gameRunning = false;
let gameTimer = 0;
let gameIntervalTimer = null;
let gameEventScore = 0;

const canvas = document.getElementById('game-canvas');
const ctx = canvas.getContext('2d');

let scaleRatio = 1;

function launchInteractiveGame(key) {
     currentActiveGameKey = key;
     gameEventScore = 0;
     
     // Configure viewport canvas size responsive
     canvas.width = canvas.offsetWidth;
     canvas.height = canvas.offsetHeight;
     
     const title = document.getElementById('game-title-lbl');
     const overlayH = document.getElementById('overlay-heading');
     const overlayD = document.getElementById('overlay-desc');
     
     if (key === 'gosok') {
          title.innerText = 'Gosok Kartu Berhadiah';
          overlayH.innerText = 'Gosok Area Abu-abu!';
          overlayD.innerText = 'Gunakan jari atau mouse Anda untuk menggosok minimal 75% permukaan layar pertambangan perak di atas untuk memenangkan bonus.';
          document.getElementById('game-live-timer-badge').style.display = 'none';
     } else if (key === 'puzzle') {
          title.innerText = 'Rangkaian Kabel Server';
          overlayH.innerText = 'Hubungkan Dalam Waktu Berdetik!';
          overlayD.innerText = 'Sambungkan kabel merah ke biru dengan menekan tombol jembatan penghubung yang bergeser tepat di tengah.';
          document.getElementById('game-live-timer-badge').style.display = 'block';
     } else {
          title.innerText = 'Kolektor Serbuk Emas';
          overlayH.innerText = 'Tangkap Serbuk Emas Server!';
          overlayD.innerText = 'Klik atau sentuh koin emas yang berjatuhan sebanyak mungkin sebelum durasi waktu 10 detik habis.';
          document.getElementById('game-live-timer-badge').style.display = 'block';
     }
     
     document.getElementById('game-live-score-badge').innerText = 'SCORE: 0';
     document.getElementById('game-overlay-prompt-lbl').classList.remove('hidden');
     document.getElementById('game-playground-modal').classList.remove('hidden');
}

function abortRunningPlayground() {
     gameRunning = false;
     clearInterval(gameIntervalTimer);
     document.getElementById('game-playground-modal').classList.add('hidden');
     ctx.clearRect(0,0, canvas.width, canvas.height);
}

function startGameEngineLoop() {
     document.getElementById('game-overlay-prompt-lbl').classList.add('hidden');
     gameRunning = true;
     
     if (currentActiveGameKey === 'gosok') {
          initGosokGameLogic();
     } else if (currentActiveGameKey === 'puzzle') {
          initPuzzleGameLogic();
     } else {
          initHujanGameLogic();
     }
}

// 1. GAME GOSOK MECHANICS
let scratchMask = [];
function initGosokGameLogic() {
     // Populate full scratch off perak mask pixels
     ctx.fillStyle = '#C0C0C0';
     ctx.fillRect(0,0, canvas.width, canvas.height);
     ctx.font = 'bold 16px sans-serif';
     ctx.fillStyle = '#666';
     ctx.textAlign = 'center';
     ctx.fillText('GOSOK DISINI', canvas.width/2, canvas.height/2);
     
     // Pre-render actual reward background behind masking of graphics pixel array size (Simulated simply)
     scratchMask = [];
     const totalPixels = canvas.width * canvas.height;
     for (let i = 0; i < 400; i++) scratchMask[i] = false; // sample grids scratch checklist
     
     // Bind interactions standard touch and mouse cursor coordinate trackers
     canvas.addEventListener('mousemove', scratchMouseTracks);
     canvas.addEventListener('touchmove', scratchTouchTracks);
}

function processScratchedPoint(x, y) {
     if (!gameRunning) return;
     ctx.globalCompositeOperation = 'destination-out';
     ctx.beginPath();
     ctx.arc(x, y, 22, 0, Math.PI * 2);
     ctx.fill();
     
     // Match checked cell
     const gridCol = Math.floor((x / canvas.width) * 20);
     const gridRow = Math.floor((y / canvas.height) * 20);
     const idx = gridRow * 20 + gridCol;
     if (idx >= 0 && idx < 400 && !scratchMask[idx]) {
          scratchMask[idx] = true;
          
          // count percentage
          const scratchedCount = scratchMask.filter(v => v === true).length;
          const ratio = Math.round((scratchedCount / 300) * 100);
          const score = Math.min(100, ratio);
          
          document.getElementById('game-live-score-badge').innerText = 'GOSOK: ' + score + '%';
          
          if (score >= 75) {
               gameRunning = false;
               canvas.removeEventListener('mousemove', scratchMouseTracks);
               canvas.removeEventListener('touchmove', scratchTouchTracks);
               finishGameCalculatedRewards(75);
          }
     }
}

function scratchMouseTracks(e) {
     const r = canvas.getBoundingClientRect();
     const x = e.clientX - r.left;
     const y = e.clientY - r.top;
     processScratchedPoint(x, y);
}
function scratchTouchTracks(e) {
     const r = canvas.getBoundingClientRect();
     const x = e.touches[0].clientX - r.left;
     const y = e.touches[0].clientY - r.top;
     processScratchedPoint(x, y);
}

// 2. TIMED PUZZLE MECHANICS (Cable Jumper)
let bridgeX = 0;
let bridgeSpeed = 5;
function initPuzzleGameLogic() {
     gameTimer = 15;
     document.getElementById('game-live-timer-badge').innerText = 'TIME: ' + gameTimer + 's';
     
     gameIntervalTimer = setInterval(() => {
          gameTimer--;
          document.getElementById('game-live-timer-badge').innerText = 'TIME: ' + gameTimer + 's';
          if (gameTimer <= 0) {
               clearInterval(gameIntervalTimer);
               gameRunning = false;
               finishGameCalculatedRewards(gameEventScore);
          }
     }, 1000);
     
     bridgeX = 0;
     bridgeSpeed = 4;
     canvas.addEventListener('click', selectPuzzleConnectorTap);
     runPuzzleRenderFrameLoop();
}

function selectPuzzleConnectorTap() {
     if (!gameRunning) return;
     const targetX = canvas.width / 2;
     const diff = Math.abs(bridgeX - targetX);
     
     if (diff < 15) { // highly matched
          gameEventScore += 10;
          bridgeSpeed += 1.5; // shift speeds
          document.getElementById('game-live-score-badge').innerText = 'SCORE: ' + gameEventScore;
          showNotification('Koneksi Sempurna! +10 Points', 'success');
     } else {
          showNotification('Meleset! Coba Lagi.', 'error');
     }
}

function runPuzzleRenderFrameLoop() {
     if (!gameRunning) return;
     ctx.clearRect(0,0, canvas.width, canvas.height);
     
     // Draw Red Cable left port
     ctx.fillStyle = '#DC2626';
     ctx.fillRect(0, canvas.height/2 - 8, 40, 16);
     
     // Draw Blue Cable right port
     ctx.fillStyle = '#0F766E';
     ctx.fillRect(canvas.width - 40, canvas.height/2 - 8, 40, 16);
     
     // Center aligned matching area indicator line target
     ctx.strokeStyle = '#D9EEEC';
     ctx.setLineDash([5, 5]);
     ctx.beginPath();
     ctx.moveTo(canvas.width/2, 0);
     ctx.lineTo(canvas.width/2, canvas.height);
     ctx.stroke();
     ctx.setLineDash([]);
     
     // Draw Jumper sliding box block
     bridgeX += bridgeSpeed;
     if (bridgeX > (canvas.width - 20) || bridgeX < 0) {
          bridgeSpeed = -bridgeSpeed;
     }
     
     ctx.fillStyle = '#EA580C';
     ctx.fillRect(bridgeX, canvas.height/2 - 12, 24, 24);
     
     requestAnimationFrame(runPuzzleRenderFrameLoop);
}

// 3. GOLD RAIN COLLECTOR MECHANICS
let coins = [];
function initHujanGameLogic() {
     gameTimer = 10;
     gameEventScore = 0;
     coins = [];
     document.getElementById('game-live-timer-badge').innerText = 'TIME: ' + gameTimer + 's';
     
     gameIntervalTimer = setInterval(() => {
          gameTimer--;
          document.getElementById('game-live-timer-badge').innerText = 'TIME: ' + gameTimer + 's';
          if (gameTimer <= 0) {
               clearInterval(gameIntervalTimer);
               gameRunning = false;
               finishGameCalculatedRewards(gameEventScore);
          }
     }, 1000);
     
     canvas.addEventListener('mousedown', clickHujanCoinsEvent);
     runHujanRenderFrameLoop();
}

function clickHujanCoinsEvent(e) {
     if (!gameRunning) return;
     const r = canvas.getBoundingClientRect();
     const x = e.clientX - r.left;
     const y = e.clientY - r.top;
     
     coins.forEach((c, idx) => {
          const dist = Math.sqrt((x - c.x)**2 + (y - c.y)**2);
          if (dist < 24) { // hit circle radius range
               gameEventScore += 5;
               document.getElementById('game-live-score-badge').innerText = 'SCORE: ' + gameEventScore;
               coins.splice(idx, 1); // remove clicked
          }
     });
}

function runHujanRenderFrameLoop() {
     if (!gameRunning) return;
     ctx.clearRect(0,0, canvas.width, canvas.height);
     
     // Spawns coins on random speed weights
     if (Math.random() < 0.08) {
          coins.push({
               x: Math.random() * (canvas.width - 32) + 16,
               y: -10,
               speed: Math.random() * 2 + 2,
               r: 14
          });
     }
     
     // Update and Draw Circle items
     coins.forEach((c, idx) => {
          c.y += c.speed;
          
          ctx.beginPath();
          ctx.fillStyle = '#EAB308';
          ctx.strokeStyle = '#CA8A04';
          ctx.lineWidth = 2;
          ctx.arc(c.x, c.y, c.r, 0, Math.PI * 2);
          ctx.fill();
          ctx.stroke();
          
          // remove fell items
          if (c.y > canvas.height) {
               coins.splice(idx, 1);
          }
     });
     
     requestAnimationFrame(runHujanRenderFrameLoop);
}

// REST ENDPOINTS FINALES SUBMITS CALCULATION REWARDS TO double entry ledger database
async function finishGameCalculatedRewards(score) {
     canvas.removeEventListener('mousemove', scratchMouseTracks);
     canvas.removeEventListener('touchmove', scratchTouchTracks);
     canvas.removeEventListener('click', selectPuzzleConnectorTap);
     canvas.removeEventListener('mousedown', clickHujanCoinsEvent);
     
     ctx.clearRect(0,0, canvas.width, canvas.height);
     
     // Post reward allocation
     const csrf_token = '<?php echo $csrf_token; ?>';
     try {
          const res = await fetch('/actions/user/index.php', {
               method: 'POST',
               headers: {'Content-Type': 'application/json'},
               body: JSON.stringify({
                    action: 'play_game',
                    csrf_token: csrf_token,
                    game_key: currentActiveGameKey,
                    score: score
               })
          });
          const d = await res.json();
          if (d.success) {
               // Show visually inside canvas context
               ctx.fillStyle = '#0F766E';
               ctx.font = 'bold 16px sans-serif';
               ctx.textAlign = 'center';
               ctx.fillText('GAME SELESAI!', canvas.width/2, canvas.height/2 - 20);
               
               ctx.fillStyle = '#12302F';
               ctx.font = '12px sans-serif';
               ctx.fillText(d.message || 'Hadiah Anda dikreditkan.', canvas.width/2, canvas.height/2 + 10);
               
               showNotification('Klaim game terverifikasi ledger!', 'success');
               setTimeout(() => { window.location.href = '/pages/user/home.php'; }, 2200);
          } else {
               ctx.fillStyle = '#DC2626';
               ctx.font = 'bold 14px sans-serif';
               ctx.textAlign = 'center';
               ctx.fillText('Klaim Gagal', canvas.width/2, canvas.height/2 - 10);
               ctx.fillStyle = '#666';
               ctx.font = '11px sans-serif';
               ctx.fillText(d.error || 'Terjadi gangguan klaim.', canvas.width/2, canvas.height/2 + 15);
          }
     } catch (e) {
          showNotification('VPS connection loss.', 'error');
     }
}
</script>

<?php
render_footer(true, 'home');
?>
