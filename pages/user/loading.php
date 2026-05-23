<?php
// Secure User Dashboard Loading Animations Page simulation

require_once __DIR__ . '/../../includes/session.php';
require_login();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sistem Enkripsi Dimulai... | NOXARA</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        noxara: {
                            bg: '#EAF5F4',
                            surface: '#F3FAF9',
                            soft: '#D9EEEC',
                            primary: '#0F766E',
                            light: '#14B8A6',
                            accent: '#2DD4BF',
                            text: '#12302F',
                            muted: '#5B7774',
                            border: '#B7D6D2',
                            success: '#16A34A',
                            danger: '#DC2626',
                            warning: '#EA580C'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Space Grotesk', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace']
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #EAF5F4; color: #12302F; font-family: 'Inter', sans-serif; }
        .app-container { max-width: 480px; margin: 0 auto; background-color: #F3FAF9; min-height: 100vh; }
        .pulse-loader {
             border: 4px solid #D9EEEC;
             border-top: 4px solid #0F766E;
             border-radius: 50%;
             width: 44px;
             height: 44px;
             animation: spin 1s linear infinite;
        }
        @keyframes spin {
             0% { transform: rotate(0deg); }
             100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-slate-900 md:py-4">
    <div class="app-container shadow-2xl border-x border-teal-100 min-h-screen p-6 flex flex-col justify-center items-center">
         
         <div class="pulse-loader mb-4"></div>
         
         <h1 class="font-display font-bold text-base text-[#0F766E] tracking-tight text-center">Menghubungkan Saldo Ledger...</h1>
         <p class="text-xs text-[#5B7774] text-center mt-1 max-w-[280px]">Sedang mensinkronisasikan buku besar dan validasi kunci pembekuan rekening Anda...</p>

         <div class="w-full bg-teal-50 border border-teal-200 p-4 rounded-xl mt-6 space-y-1.5 text-left">
              <span class="block text-[9px] font-mono font-bold uppercase tracking-wider text-teal-800">Status Saluran Koneksi</span>
              <div class="flex justify-between items-center text-[10px] text-[#12302F] font-mono">
                   <span>Keamanan Server:</span>
                   <span class="text-emerald-700 font-bold">TERKUNCI SSL V2</span>
              </div>
              <div class="flex justify-between items-center text-[10px] text-[#12302F] font-mono">
                   <span>Database Shard:</span>
                   <span class="text-teal-700">noxara_Jaka22</span>
              </div>
              <div class="flex justify-between items-center text-[10px] text-[#12302F] font-mono">
                   <span>Rute Handshake:</span>
                   <span class="text-teal-700">OK (Session Valid)</span>
              </div>
         </div>
    </div>

    <script>
        // Smoothly proceed to user homepage after loader timer finishes
        setTimeout(() => {
             window.location.href = '/pages/user/home.php';
        }, 1500);
    </script>
</body>
</html>
