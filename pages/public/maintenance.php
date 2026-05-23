<?php
// Maintenance notice display screen of NOXARA
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pemeliharaan Server | NOXARA</title>
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
    </style>
</head>
<body class="bg-slate-950 md:py-4">
    <div class="app-container shadow-2xl border-x border-teal-100 min-h-screen p-6 flex flex-col justify-center items-center text-center space-y-4">
         <div class="bg-amber-100 border border-amber-200 text-amber-900 p-4 rounded-full">
              <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                   <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
              </svg>
         </div>

         <div class="space-y-1">
              <h1 class="font-display font-bold text-xl text-[#0F766E] tracking-tight">PEMELIHARAAN SISTEM</h1>
              <p class="text-xs text-[#5B7774] leading-relaxed max-w-[280px]">Server database pusat NOXARA sedang dioptimalkan dalam peningkatan performa berkala harian. Platform akan kembali beroperasi normal secepatnya.</p>
         </div>

         <div class="w-full bg-teal-50 border border-teal-100/50 p-3 rounded-xl font-mono text-[10px] text-[#12302F] space-y-1">
              <div class="flex justify-between">
                   <span>Status Hardware:</span>
                   <span class="text-amber-700 font-bold">MIGRATING</span>
              </div>
              <div class="flex justify-between">
                   <span>Server VPS Log:</span>
                   <span class="text-teal-700 font-bold">OK (Nginx 1.2x)</span>
              </div>
         </div>
    </div>
</body>
</html>
