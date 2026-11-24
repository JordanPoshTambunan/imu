@extends('layouts.app')

@section('content')
<div class="main-card w-full max-w-3xl p-8 bg-orange-100 text-gray-800">
    <h2 class="text-2xl font-bold mb-6 text-orange-700">Notifikasi</h2>

    <div class="divide-y divide-orange-200 bg-white rounded-lg shadow-inner overflow-hidden">
        @forelse ($notifications as $notification)
            <div class="p-4 flex items-start gap-3">
                <div class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0 bg-gray-300 flex items-center justify-center">
                    @if ($notification->fromUser?->profile_picture)
                        <img src="{{ asset($notification->fromUser->profile_picture) }}" alt="Foto profil {{ $notification->fromUser->full_name }}" class="w-full h-full object-cover">
                    @else
                        <span class="font-bold text-gray-600">{{ strtoupper(substr($notification->fromUser?->full_name ?? 'U', 0, 1)) }}</span>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-sm">{{ $notification->title }}</p>
                    <p class="text-sm text-gray-600 mt-1">{{ $notification->message }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                    @if ($notification->type === 'match_created' && $notification->from_user_id)
                        <a href="{{ route('chat.personal', ['with' => $notification->from_user_id]) }}" class="inline-block mt-2 bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-full text-xs">
                            Mulai Chat
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <p class="p-6 text-center text-gray-500">Belum ada notifikasi.</p>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
