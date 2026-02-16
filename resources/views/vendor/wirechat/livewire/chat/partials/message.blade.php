@use('Wirechat\Wirechat\Facades\Wirechat')


@php

   $isSameAsNext = ($message?->sendable_id === $nextMessage?->sendable_id) && ($message?->sendable_type === $nextMessage?->sendable_type);
   $isNotSameAsNext = !$isSameAsNext;
   $isSameAsPrevious = ($message?->sendable_id === $previousMessage?->sendable_id) && ($message?->sendable_type === $previousMessage?->sendable_type);
   $isNotSameAsPrevious = !$isSameAsPrevious;
@endphp

<div


{{-- We use style here to make it easy for dynamic and safe injection --}}
@style([
'background-color:var(--wc-brand-primary)' => $belongsToAuth==true
])

@class([
    'flex flex-wrap max-w-fit text-[15px] border border-gray-200/40 dark:border-none rounded-xl p-2.5 flex flex-col text-black bg-[#f6f6f8fb]',
    'text-white' => $belongsToAuth, // Background color for messages sent by the authenticated user
    'bg-[var(--wc-light-secondary)] dark:bg-[var(--wc-dark-secondary)] dark:text-white' => !$belongsToAuth,

    // Message styles based on position and ownership

    // RIGHT
    // First message on RIGHT
    'rounded-br-md rounded-tr-2xl' => ($isSameAsNext && $isNotSameAsPrevious && $belongsToAuth),

    // Middle message on RIGHT
    'rounded-r-md' => ($isSameAsPrevious && $belongsToAuth),

    // Standalone message RIGHT
    'rounded-br-xl rounded-r-xl' => ($isNotSameAsPrevious && $isNotSameAsNext && $belongsToAuth),

    // Last Message on RIGHT
    'rounded-br-2xl' => ($isNotSameAsNext && $belongsToAuth),

    // LEFT
    // First message on LEFT
    'rounded-bl-md rounded-tl-2xl' => ($isSameAsNext && $isNotSameAsPrevious && !$belongsToAuth),

    // Middle message on LEFT
    'rounded-l-md' => ($isSameAsPrevious && !$belongsToAuth),

    // Standalone message LEFT
    'rounded-bl-xl rounded-l-xl' => ($isNotSameAsPrevious && $isNotSameAsNext && !$belongsToAuth),

    // Last message on LEFT
    'rounded-bl-2xl' => ($isNotSameAsNext && !$belongsToAuth),
])
>
@if (!$belongsToAuth && $isGroup)
<div
    @class([
        'shrink-0 font-medium text-purple-500',
        // Hide avatar if the next message is from the same user
        'hidden' => $isSameAsPrevious
    ])>
    {{ $message?->sendable?->wirechat_name }}
</div>
@endif

<pre class="whitespace-pre-line tracking-normal break-all text-sm md:text-base dark:text-white lg:tracking-normal"
    style="font-family: inherit;">
    {{$message?->body}}
</pre>

{{-- Display the created time based on different conditions --}}
<div
@class(['text-[11px] ml-auto flex items-center gap-1',  'text-gray-700 dark:text-gray-300' => !$belongsToAuth,'text-gray-100' => $belongsToAuth])>
    <span>
    @php
        // If the message was created today, show only the time (e.g., 1:00 AM)
        echo $message?->created_at->format('H:i');
    @endphp
    </span>

    @if($belongsToAuth && isset($isGroup) && !$isGroup)
        @php
             $receiver = $this->receiverParticipant ?? null;
             $isRead = false;
             // Check if receiver exists and has read up to this message
             if($receiver && $receiver->conversation_read_at){
                 $isRead = $message->created_at->lessThanOrEqualTo($receiver->conversation_read_at);
             }
        @endphp

        @if($isRead)
            {{-- Read (Double Tick) --}}
            <span class="flex relative text-primary-200" title="Leído">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-3 h-3 -mr-1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-3 h-3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </span>
        @else
            {{-- Sent (Single Tick) --}}
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-3 h-3 text-gray-200" title="Enviado">
               <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
        @endif
    @endif
</div>

</div>
