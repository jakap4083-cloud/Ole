<?php
// User Support Live Chat channels page layout
require_once __DIR__ . '/../../includes/header-helper.php';
require_once __DIR__ . '/../../includes/db.php';

require_login();
$user_id = $_SESSION['user_id'];
$csrf_token = generate_csrf_token();

// Fetch latest conversation message list threads
$db = get_db_connection();
$stmt = $db->prepare("SELECT * FROM support_messages WHERE user_id = ? ORDER BY id ASC LIMIT 50");
$stmt->execute([$user_id]);
$msgs = $stmt->fetchAll();
?>

<div class="space-y-4 fade-in flex flex-col min-h-[calc(100vh-140px)] justify-between">
     <!-- 1. Top channel title bar -->
     <div class="bg-teal-900 border border-teal-850 rounded-2xl p-4 text-white text-left relative overflow-hidden shrink-0">
          <div class="absolute -right-16 -bottom-16 w-32 h-32 rounded-full bg-teal-800 opacity-20"></div>
          <span class="block text-[9px] font-mono font-bold text-teal-300 tracking-wider uppercase">Dukungan Enkripsi Tercepat</span>
          <h2 class="font-display font-bold text-base mt-0.5">LAYANAN ADUAN MITRA NOXARA</h2>
          <p class="text-[10px] text-teal-200 mt-1 leading-relaxed">Hubungi admin resmi pengawasan server kami secara langsung atau kirimkan lampiran keluhan Anda di bawah ini secara instan 24/7 harian.</p>
     </div>

     <!-- 2. Screen viewport messages lists box -->
     <div class="bg-white rounded-2xl p-4 border border-teal-100 shadow-[0_4px_16px_rgba(15,118,110,0.01)] flex-1 flex flex-col justify-between overflow-hidden min-h-[220px]">
          <div class="overflow-y-auto space-y-3.5 max-h-[300px] flex-1 pb-3 pr-1" id="chat-scroller-view">
               
               <!-- Welcome support agent greetings text -->
               <div class="flex items-start gap-2 max-w-[85%] text-left">
                    <div class="w-7 h-7 rounded-lg bg-teal-100 border border-teal-200 text-[#0F766E] flex items-center justify-center font-bold text-xs shrink-0 select-none">
                         A
                    </div>
                    <div>
                         <div class="bg-teal-50 border border-teal-100 p-2.5 rounded-r-xl rounded-bl-xl text-xs text-[#12302F] leading-relaxed">
                              Halo Mitra! Selamat datang di layanan chat dukungan pusat. Silakan kirimkan format keluhan Anda terkait deposit, penarikan dana, kendala klaim sewa harian.
                         </div>
                         <span class="block text-[8px] text-[#5B7774] mt-1 font-mono">Official Noxara Virtual Support Agent</span>
                    </div>
               </div>

               <?php foreach ($msgs as $m): ?>
                    <?php if ($m['sender_type'] === 'user'): ?>
                         <!-- Sender type is current user -->
                         <div class="flex items-start gap-2 max-w-[85%] text-right ml-auto flex-row-reverse">
                              <div class="w-7 h-7 rounded-lg bg-teal-800 border border-teal-900 text-white flex items-center justify-center font-bold text-xs shrink-0 select-none">
                                   U
                              </div>
                              <div class="text-right">
                                   <div class="bg-teal-900 text-white p-2.5 rounded-l-xl rounded-br-xl text-xs leading-relaxed text-left">
                                        <?php echo sanitize_output($m['message_text']); ?>
                                        
                                        <!-- Render attachment picture if exists secure rename path -->
                                        <?php if (!empty($m['attachment_path'])): ?>
                                             <div class="mt-2 p-1 bg-white rounded-lg border border-teal-100 max-w-[150px]">
                                                  <img src="<?php echo sanitize_output($m['attachment_path']); ?>" alt="Attachment user screenshot proof" class="w-full h-auto rounded" referrerPolicy="no-referrer">
                                             </div>
                                        <?php endif; ?>
                                   </div>
                                   <span class="block text-[8px] text-[#5B7774] mt-1 font-mono"><?php echo date('H:i', strtotime($m['created_at'])); ?></span>
                              </div>
                         </div>
                    <?php else: ?>
                         <!-- Sender type is administrator -->
                         <div class="flex items-start gap-2 max-w-[85%] text-left">
                              <div class="w-7 h-7 rounded-lg bg-emerald-100 border border-emerald-250 text-emerald-800 flex items-center justify-center font-bold text-xs shrink-0 select-none">
                                   A
                              </div>
                              <div>
                                   <div class="bg-emerald-50 border border-emerald-100 p-2.5 rounded-r-xl rounded-bl-xl text-xs text-[#12302F] leading-relaxed">
                                        <?php echo sanitize_output($m['message_text']); ?>
                                   </div>
                                   <span class="block text-[8px] text-[#5B7774] mt-1 font-mono"><?php echo date('H:i', strtotime($m['created_at'])); ?></span>
                              </div>
                         </div>
                    <?php endif; ?>
               <?php endforeach; ?>

          </div>

          <!-- 3. Form input text and file uploads interactive inputs -->
          <form id="chat-input-form" enctype="multipart/form-data" class="border-t border-teal-50 pt-3 flex gap-2 items-center">
               <?php echo csrf_field(); ?>
               
               <!-- Hidden screenshot attachment input field native styling custom icon button click -->
               <div class="relative shrink-0">
                    <input id="chat-attachment-file" type="file" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="previewAttachmentFilename()">
                    <button type="button" class="w-10 h-10 border border-teal-200 bg-teal-50 hover:bg-teal-100 text-[#0F766E] rounded-xl flex items-center justify-center transition-transform active:scale-95">
                         <!-- Picture Attachment SVG -->
                         <svg class="w-5 h-5 text-teal-800" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                         </svg>
                    </button>
               </div>

               <!-- Message inputs box -->
               <div class="flex-1">
                    <input id="chat-msg-input" type="text" required class="w-full h-10 px-3.5 bg-teal-50/10 border border-teal-200 rounded-xl text-xs focus:outline-none focus:border-[#0F766E]" placeholder="Masukan tangisan aduan keluhan...">
               </div>

               <button type="submit" id="chat-send-btn" class="w-10 h-10 bg-[#0F766E] hover:bg-teal-850 text-white rounded-xl flex items-center justify-center transition-transform active:scale-95 shrink-0 shadow-md">
                    <!-- Send custom arrow SVG -->
                    <svg class="w-4.5 h-4.5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
               </button>
          </form>
          
          <!-- Attachment visual text indicator -->
          <div id="attachment-indicator-lbl" class="text-[9px] text-teal-900 italic hidden pt-1 text-left font-mono">Lampirkan file terdeteksi: NULL</div>
     </div>
</div>

<script>
// Auto scroll chats lists viewport to bottoms instantly
const scroller = document.getElementById('chat-scroller-view');
if (scroller) {
     scroller.scrollTop = scroller.scrollHeight;
}

function previewAttachmentFilename() {
     const fileInput = document.getElementById('chat-attachment-file');
     const indicator = document.getElementById('attachment-indicator-lbl');
     if (fileInput.files.length > 0) {
          indicator.innerText = "📎 Melampirkan File: " + fileInput.files[0].name;
          indicator.classList.remove('hidden');
     } else {
          indicator.classList.add('hidden');
     }
}

const chatForm = document.getElementById('chat-input-form');
if (chatForm) {
     chatForm.addEventListener('submit', async function(e) {
         e.preventDefault();
         
         const text = document.getElementById('chat-msg-input').value.trim();
         const fileInput = document.getElementById('chat-attachment-file');
         const csrf = document.querySelector('input[name="csrf_token"]').value;
         
         if (text.length === 0 && fileInput.files.length === 0) return;
         
         const fd = new FormData();
         fd.append('action', 'send_message');
         fd.append('csrf_token', csrf);
         fd.append('message_text', text);
         
         if (fileInput.files.length > 0) {
              fd.append('attachment', fileInput.files[0]);
         }
         
         const btn = document.getElementById('chat-send-btn');
         btn.disabled = true;
         
         try {
              const res = await fetch('/actions/user/index.php', {
                   method: 'POST',
                   body: fd
              });
              const r = await res.json();
              if (r.success) {
                   document.getElementById('chat-msg-input').value = '';
                   fileInput.value = '';
                   document.getElementById('attachment-indicator-lbl').classList.add('hidden');
                   
                   // Dynamic injection inside viewport rather than forced refresh
                   showNotification('Aduan terkirim ke moderator!', 'success');
                   setTimeout(() => { window.location.reload(); }, 800);
              } else {
                   showNotification(r.error || 'Gagal mengirim chat aduan.', 'error');
                   btn.disabled = false;
              }
         } catch (err) {
              showNotification('Connection Failure.', 'error');
              btn.disabled = false;
         }
     });
}
</script>

<?php
render_footer(true, 'chat');
?>
