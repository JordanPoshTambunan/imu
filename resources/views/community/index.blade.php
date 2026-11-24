@extends('layouts.app')

@section('content')
<div class="main-card w-full max-w-4xl h-[600px] flex flex-col bg-white rounded-lg shadow-xl overflow-hidden text-gray-800 p-0">

    {{-- TOP SECTION: Horizontal Scrollable Community List --}}
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <div class="flex space-x-4 overflow-x-auto pb-2 scrollbar-hide">
            @forelse ($communities as $community)
                {{-- Mengarahkan ke grup spesifik dan menandai yang aktif --}}
                <a href="{{ route('community', ['group' => $community->id]) }}" 
                   class="community-circle flex-shrink-0 text-center transition-all duration-200 hover:transform hover:scale-105 {{ ($selectedGroup && $selectedGroup->id === $community->id) ? 'border-2 border-blue-600 p-1 rounded-full' : '' }}">
                    <div class="w-16 h-16 rounded-full mx-auto mb-1 flex items-center justify-center text-2xl {{ ($selectedGroup && $selectedGroup->id === $community->id) ? 'bg-blue-500 text-white' : 'bg-gray-200' }}">
                        {{-- Ikon berdasarkan nama komunitas --}}
                        @switch($community->name)
                            @case('Pengumuman Umum')
                                📢
                                @break
                            @case('Info Beasiswa')
                                🎓
                                @break
                            @case('Lowongan Kerja')
                                💼
                                @break
                            @case('Event & Workshop')
                                🎪
                                @break
                            @case('PKM & Kompetisi')
                                🏆
                                @break
                            @case('Info Akademik')
                                📚
                                @break
                            @default
                                💬
                        @endswitch
                    </div>
                    <p class="text-xs font-semibold {{ ($selectedGroup && $selectedGroup->id === $community->id) ? 'text-blue-800' : 'text-gray-700' }}">
                        {{ Str::limit($community->name, 12) }}
                    </p>
                </a>
            @empty
                <div class="flex items-center justify-center w-full py-8">
                    <div class="text-center">
                        <div class="text-4xl mb-2">🏗️</div>
                        <p class="text-gray-500 text-sm">Tidak ada komunitas yang tersedia.</p>
                        <p class="text-gray-400 text-xs mt-1">Hubungi admin untuk membuat komunitas baru.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    {{-- MAIN SECTION: Community Chat/Post Feed (Vertical Scrollable) --}}
    <div class="flex flex-col flex-grow p-4 overflow-y-auto bg-gray-100" id="messages-container">
        @if($selectedGroup)
            <div class="flex items-center justify-between mb-4">
                <h4 class="font-bold text-xl text-gray-900">📋 {{ $selectedGroup->name }}</h4>
                <span class="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded-full">
                    {{ $groupMessages->count() }} {{ $groupMessages->count() === 1 ? 'post' : 'posts' }}
                </span>
            </div>

            {{-- Community Description --}}
            @if($selectedGroup->description)
                <div class="bg-blue-50 border-l-4 border-blue-400 p-3 mb-4 rounded">
                    <p class="text-sm text-blue-800">{{ $selectedGroup->description }}</p>
                </div>
            @endif

            @forelse ($groupMessages as $message)
                {{-- Postingan dari Community --}}
                <div class="community-post-card bg-white p-4 rounded-lg shadow-md mb-4 border-l-4 border-blue-400">
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 rounded-full bg-blue-500 mr-3 flex-shrink-0 overflow-hidden flex items-center justify-center">
                            {{-- Menggunakan profile_picture pengirim pesan, atau placeholder --}}
                            @if($message->sender->profile_picture)
                                <img src="{{ $message->sender->profile_picture }}" alt="User Avatar" class="w-full h-full object-cover">
                            @else
                                <span class="text-white font-bold text-sm">{{ strtoupper(substr($message->sender->full_name ?? 'A', 0, 1)) }}</span>
                            @endif
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <p class="font-semibold text-gray-800">{{ $message->sender->full_name }}</p>
                                <span class="bg-blue-200 text-blue-800 text-xs font-semibold px-2 py-0.5 rounded-full">
                                    {{ ucfirst($message->sender->role) }}
                                </span>
                                @if($message->sender->role === 'admin')
                                    <span class="text-blue-500 text-sm">✓</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500">{{ $message->created_at->format('d M Y, H:i') }} • {{ $message->created_at->diffForHumans() }}</p>
                        </div>
                        {{-- View community button --}}
                        <a href="{{ route('community', ['group' => $selectedGroup->id]) }}" class="text-sm text-blue-600 hover:text-blue-800 transition-colors">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                    <div class="prose prose-sm max-w-none">
                        <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">{{ $message->message_content }}</p>
                    </div>
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                        <div class="flex items-center space-x-4 text-gray-500 text-sm">
                            {{-- Interaction buttons (future features) --}}
                            <button class="flex items-center space-x-1 hover:text-blue-500 transition-colors">
                                <i class="fas fa-heart"></i>
                                <span>0</span>
                            </button>
                            <button class="flex items-center space-x-1 hover:text-blue-500 transition-colors">
                                <i class="fas fa-comment"></i>
                                <span>0</span>
                            </button>
                        </div>
                        <button class="text-gray-400 hover:text-blue-500 transition-colors text-sm">
                            <i class="fas fa-share-alt"></i>
                        </button>
                    </div>
                </div>
            @empty
                <div class="flex items-center justify-center flex-1">
                    <div class="text-center py-12">
                        <div class="text-6xl mb-4">📭</div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Belum ada postingan</h3>
                        <p class="text-gray-500 text-sm max-w-md mx-auto">
                            Komunitas <strong>{{ $selectedGroup->name }}</strong> belum memiliki postingan. 
                            @if($currentUser->id === $selectedGroup->creator_id || $currentUser->role === 'admin')
                                Buat postingan pertama di bawah!
                            @else
                                Tunggu admin untuk memposting informasi.
                            @endif
                        </p>
                    </div>
                </div>
            @endforelse
        @else
            {{-- No community selected --}}
            <div class="flex items-center justify-center flex-1">
                <div class="text-center">
                    <div class="text-6xl mb-4">🎯</div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Pilih Komunitas</h3>
                    <p class="text-gray-500 text-sm">Pilih salah satu komunitas di atas untuk melihat postingan terbaru.</p>
                </div>
            </div>
        @endif

        {{-- Area untuk "write your post here" --}}
        @if ($selectedGroup && ($currentUser->id === $selectedGroup->creator_id || $currentUser->role === 'admin'))
        <div class="write-post-area bg-white p-4 rounded-lg shadow-md mt-4 border-2 border-dashed border-blue-300">
            <div class="flex items-start space-x-3 mb-3">
                <div class="w-8 h-8 rounded-full bg-blue-500 flex-shrink-0 flex items-center justify-center">
                    @if($currentUser->profile_picture)
                        <img src="{{ $currentUser->profile_picture }}" alt="Your Avatar" class="w-full h-full object-cover rounded-full">
                    @else
                        <span class="text-white font-bold text-sm">{{ strtoupper(substr($currentUser->full_name ?? 'U', 0, 1)) }}</span>
                    @endif
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-700">Buat postingan sebagai <span class="text-blue-600">{{ ucfirst($currentUser->role) }}</span></p>
                    <p class="text-xs text-gray-500">Postingan akan terlihat oleh semua anggota komunitas {{ $selectedGroup->name }}</p>
                </div>
            </div>
            
            <textarea id="group-message-input" 
                      class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent resize-none transition-all" 
                      rows="4" 
                      placeholder="Tulis pengumuman atau informasi penting untuk komunitas..."
                      maxlength="5000"></textarea>
            
            {{-- Character Counter --}}
            <div id="char-counter" class="text-xs text-gray-500 text-right mt-1">0/5000</div>
            
            <div class="flex justify-between items-center mt-3">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-info-circle text-blue-500"></i>
                    <span class="text-xs text-gray-600">Posting ke:</span>
                    <select id="community-select" class="text-xs px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-1 focus:ring-blue-400">
                        @forelse ($communities as $communityOption)
                            @if ($communityOption->creator_id === $currentUser->id || $currentUser->role === 'admin')
                                <option value="{{ $communityOption->id }}" {{ ($selectedGroup && $selectedGroup->id === $communityOption->id) ? 'selected' : '' }}>
                                    {{ $communityOption->name }}
                                </option>
                            @endif
                        @empty
                            <option value="">Tidak ada komunitas tersedia</option>
                        @endforelse
                    </select>
                </div>
                <div class="flex items-center space-x-2">
                    <button id="publish-group-post-button" 
                            class="bg-blue-500 hover:bg-blue-600 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-6 py-2 rounded-lg font-medium transition-all duration-200 flex items-center space-x-2"
                            disabled>
                        <span id="publish-button-text">Publikasikan</span>
                        <i id="publish-button-icon" class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
        @elseif($selectedGroup)
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mt-4 text-center">
                <i class="fas fa-lock text-gray-400 text-lg mb-2"></i>
                <p class="text-gray-600 text-sm">Hanya admin dan pembuat komunitas yang dapat memposting di saluran ini.</p>
            </div>
        @endif

    </div> {{-- End of Community Chat/Post Feed --}}

</div> {{-- End of main-card container --}}

{{-- Loading Overlay --}}
<div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-4"></div>
        <p class="text-gray-700">Memposting...</p>
    </div>
</div>

{{-- Success/Error Toast Container --}}
<div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const groupMessageInput = document.getElementById('group-message-input');
        const publishGroupPostButton = document.getElementById('publish-group-post-button');
        const publishButtonText = document.getElementById('publish-button-text');
        const publishButtonIcon = document.getElementById('publish-button-icon');
        const communitySelect = document.getElementById('community-select');
        const messagesContainer = document.getElementById('messages-container');
        const loadingOverlay = document.getElementById('loading-overlay');
        const toastContainer = document.getElementById('toast-container');
        const charCounter = document.getElementById('char-counter');

        // Scroll ke bawah saat halaman dimuat
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Pastikan elemen input ada sebelum menambahkan event listener
        if (groupMessageInput && publishGroupPostButton && communitySelect) {
            
            // Enable/disable button based on input
            function updateButtonState() {
                const hasContent = groupMessageInput.value.trim().length > 0;
                const isValidLength = groupMessageInput.value.length <= 5000;
                const isEnabled = hasContent && isValidLength;
                
                publishGroupPostButton.disabled = !isEnabled;
                
                if (isEnabled) {
                    publishGroupPostButton.classList.remove('disabled:bg-gray-400', 'disabled:cursor-not-allowed');
                    publishGroupPostButton.classList.add('bg-blue-500', 'hover:bg-blue-600');
                } else {
                    publishGroupPostButton.classList.add('disabled:bg-gray-400', 'disabled:cursor-not-allowed');
                    publishGroupPostButton.classList.remove('bg-blue-500', 'hover:bg-blue-600');
                }
            }

            // Character counter update
            function updateCharCounter() {
                const currentLength = groupMessageInput.value.length;
                const maxLength = 5000;
                charCounter.textContent = `${currentLength}/${maxLength}`;
                
                if (currentLength > maxLength * 0.9) {
                    charCounter.className = 'text-xs text-orange-500 text-right mt-1';
                } else if (currentLength >= maxLength) {
                    charCounter.className = 'text-xs text-red-500 text-right mt-1';
                } else {
                    charCounter.className = 'text-xs text-gray-500 text-right mt-1';
                }
            }

            // Auto-resize textarea
            function autoResize() {
                groupMessageInput.style.height = 'auto';
                groupMessageInput.style.height = Math.min(groupMessageInput.scrollHeight, 120) + 'px';
            }

            // Initial state check
            updateButtonState();
            updateCharCounter();

            // Event listeners
            groupMessageInput.addEventListener('input', function() {
                updateButtonState();
                updateCharCounter();
                autoResize();
            });

            publishGroupPostButton.addEventListener('click', sendGroupMessage);
            
            groupMessageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                    e.preventDefault();
                    sendGroupMessage();
                }
            });

            async function sendGroupMessage() {
                const messageContent = groupMessageInput.value.trim();
                const groupId = communitySelect.value;

                if (messageContent === '' || !groupId) {
                    showToast('Pesan tidak boleh kosong dan pilih komunitas yang valid.', 'error');
                    return;
                }

                if (messageContent.length > 5000) {
                    showToast('Pesan terlalu panjang. Maksimal 5000 karakter.', 'error');
                    return;
                }

                // Show loading state
                setLoadingState(true);

                try {
                    const response = await fetch('{{ route('community.send_message') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            group_id: groupId,
                            message_content: messageContent
                        })
                    });

                    const data = await response.json();
                    
                    if (response.ok && data.status === 'success') {
                        console.log('Pesan grup berhasil dikirim:', data.message);
                        
                        // Clear input
                        groupMessageInput.value = '';
                        updateButtonState();
                        updateCharCounter();
                        autoResize();
                        
                        // Add message to UI
                        appendGroupMessageToUI(data.sent_message);
                        
                        // Scroll to bottom
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                        
                        // Show success toast
                        showToast('Postingan berhasil dipublikasikan!', 'success');
                        
                    } else {
                        console.error('Gagal mengirim pesan grup:', data.message || 'Terjadi kesalahan.');
                        showToast(data.message || 'Gagal memposting. Silakan coba lagi.', 'error');
                    }
                } catch (error) {
                    console.error('Error saat mengirim pesan grup:', error);
                    showToast('Terjadi kesalahan jaringan. Periksa koneksi internet Anda.', 'error');
                } finally {
                    setLoadingState(false);
                }
            }

            function setLoadingState(isLoading) {
                if (isLoading) {
                    loadingOverlay.classList.remove('hidden');
                    publishGroupPostButton.disabled = true;
                    publishButtonText.textContent = 'Memposting...';
                    publishButtonIcon.className = 'fas fa-spinner fa-spin';
                    groupMessageInput.disabled = true;
                } else {
                    loadingOverlay.classList.add('hidden');
                    publishGroupPostButton.disabled = groupMessageInput.value.trim().length === 0;
                    publishButtonText.textContent = 'Publikasikan';
                    publishButtonIcon.className = 'fas fa-paper-plane';
                    groupMessageInput.disabled = false;
                    updateButtonState();
                }
            }

            function appendGroupMessageToUI(message) {
                const postCard = document.createElement('div');
                postCard.className = 'community-post-card bg-white p-4 rounded-lg shadow-md mb-4 border-l-4 border-blue-400';

                const currentTime = new Date();
                const timeString = currentTime.toLocaleDateString('id-ID', { 
                    day: 'numeric', 
                    month: 'short', 
                    year: 'numeric' 
                }) + ', ' + currentTime.toLocaleTimeString('id-ID', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });

                postCard.innerHTML = `
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 rounded-full bg-blue-500 mr-3 flex-shrink-0 overflow-hidden flex items-center justify-center">
                            @if(Auth::user()->profile_picture)
                                <img src="{{ Auth::user()->profile_picture }}" alt="User Avatar" class="w-full h-full object-cover">
                            @else
                                <span class="text-white font-bold text-sm">{{ strtoupper(substr(Auth::user()->full_name ?? 'U', 0, 1)) }}</span>
                            @endif
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <p class="font-semibold text-gray-800">${escapeHtml(message.sender.full_name)}</p>
                                <span class="bg-blue-200 text-blue-800 text-xs font-semibold px-2 py-0.5 rounded-full">${message.sender.role.charAt(0).toUpperCase() + message.sender.role.slice(1)}</span>
                                ${message.sender.role === 'admin' ? '<span class="text-blue-500 text-sm">✓</span>' : ''}
                            </div>
                            <p class="text-xs text-gray-500">${timeString} • Baru saja</p>
                        </div>
                        <a href="{{ route('community', ['group' => $selectedGroup->id ?? 0]) }}" class="text-sm text-blue-600 hover:text-blue-800 transition-colors">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                    <div class="prose prose-sm max-w-none">
                        <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">${escapeHtml(message.message_content)}</p>
                    </div>
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                        <div class="flex items-center space-x-4 text-gray-500 text-sm">
                            <button class="flex items-center space-x-1 hover:text-blue-500 transition-colors">
                                <i class="fas fa-heart"></i>
                                <span>0</span>
                            </button>
                            <button class="flex items-center space-x-1 hover:text-blue-500 transition-colors">
                                <i class="fas fa-comment"></i>
                                <span>0</span>
                            </button>
                        </div>
                        <button class="text-gray-400 hover:text-blue-500 transition-colors text-sm">
                            <i class="fas fa-share-alt"></i>
                        </button>
                    </div>
                `;

                // Insert the new message at the end before the input area
                const inputArea = document.querySelector('.write-post-area');
                if (inputArea) {
                    messagesContainer.insertBefore(postCard, inputArea);
                } else {
                    messagesContainer.appendChild(postCard);
                }
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function showToast(message, type = 'info') {
                const toast = document.createElement('div');
                const typeClasses = {
                    'success': 'bg-green-500 text-white',
                    'error': 'bg-red-500 text-white',
                    'warning': 'bg-yellow-500 text-white',
                    'info': 'bg-blue-500 text-white'
                };

                const typeIcons = {
                    'success': 'fas fa-check-circle',
                    'error': 'fas fa-exclamation-circle',
                    'warning': 'fas fa-exclamation-triangle',
                    'info': 'fas fa-info-circle'
                };

                toast.className = `flex items-center space-x-3 ${typeClasses[type]} px-4 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 max-w-sm`;
                
                toast.innerHTML = `
                    <i class="${typeIcons[type]}"></i>
                    <span class="flex-1">${message}</span>
                    <button onclick="this.parentElement.remove()" class="text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                `;

                toastContainer.appendChild(toast);

                // Animate in
                setTimeout(() => {
                    toast.classList.remove('translate-x-full');
                }, 100);

                // Auto remove after 5 seconds
                setTimeout(() => {
                    toast.classList.add('translate-x-full');
                    setTimeout(() => {
                        if (toast.parentElement) {
                            toast.remove();
                        }
                    }, 300);
                }, 5000);
            }
        }

        // Auto-scroll to bottom on new messages (for real-time updates)
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // Check if added node is a message card
                    const hasMessageCard = Array.from(mutation.addedNodes).some(node => 
                        node.classList && node.classList.contains('community-post-card')
                    );
                    
                    if (hasMessageCard) {
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                }
            });
        });

        if (messagesContainer) {
            observer.observe(messagesContainer, { childList: true });
        }
    });
</script>
@endpush

@endsection