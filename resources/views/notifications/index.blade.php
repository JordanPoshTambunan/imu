@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
<div class="w-full max-w-3xl">
    <h1 class="page-title mb-4">Notifikasi</h1>

    <div class="card divide-y divide-gray-100 overflow-hidden">
        @forelse ($notifications as $notification)
            <div class="flex items-start gap-3 p-4">
                <x-avatar :user="$notification->fromUser" />
                <div class="min-w-0 flex-1">
                    <p class="break-words text-sm font-semibold text-navy-900">{{ $notification->title }}</p>
                    <p class="mt-0.5 text-sm text-gray-600">{{ $notification->message }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</p>
                    @if ($notification->type === 'match_created' && $notification->from_user_id)
                        <a href="{{ route('chat.personal', ['with' => $notification->from_user_id]) }}" class="mt-2 inline-flex items-center gap-1 rounded-full bg-navy-800 px-3 py-1 text-xs font-semibold text-white hover:bg-navy-700">
                            <i class="fas fa-comment"></i> Mulai chat
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-10 text-center text-gray-500">
                <i class="fas fa-bell-slash mb-2 text-3xl text-navy-100"></i>
                <p>Belum ada notifikasi.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
