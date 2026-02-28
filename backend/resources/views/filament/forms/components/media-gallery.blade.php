<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @php
        $files = $getState();
        if (is_string($files)) {
            $files = json_decode($files, true) ?? [$files];
        }
    @endphp

    @if($files && count($files) > 0)
        @foreach($files as $file)
            @php
                $url = Storage::disk('public')->url($file);
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            @endphp
            
            <div class="flex flex-col gap-2 p-2 border rounded-lg bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
                @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                    <a href="{{ $url }}" target="_blank" class="block">
                        <img src="{{ $url }}" class="rounded-lg shadow-sm w-full h-48 object-cover hover:opacity-75 transition-opacity">
                    </a>
                    <span class="text-xs text-center text-gray-500 italic">Image ({{ $extension }})</span>
                @elseif(in_array($extension, ['mp4', 'mov', 'avi', 'webm']))
                    <video src="{{ $url }}" controls class="rounded-lg shadow-sm w-full h-48 bg-black"></video>
                    <span class="text-xs text-center text-gray-500 italic">Video ({{ $extension }})</span>
                @elseif(in_array($extension, ['mp3', 'wav', 'm4a', 'aac']))
                    <div class="h-48 flex items-center justify-center bg-gray-200 dark:bg-gray-700 rounded-lg">
                        <x-heroicon-o-musical-note class="w-16 h-16 text-gray-400" />
                    </div>
                    <audio src="{{ $url }}" controls class="w-full"></audio>
                    <span class="text-xs text-center text-gray-500 italic">Audio ({{ $extension }})</span>
                @else
                    <div class="h-48 flex items-center justify-center bg-gray-200 dark:bg-gray-700 rounded-lg">
                        <x-heroicon-o-document class="w-16 h-16 text-gray-400" />
                    </div>
                    <a href="{{ $url }}" target="_blank" class="text-sm text-blue-600 hover:underline text-center truncate">
                        {{ basename($file) }}
                    </a>
                @endif
            </div>
        @endforeach
    @else
        <div class="col-span-full p-4 text-center text-gray-500 italic border rounded-lg">
            No media attachments found.
        </div>
    @endif
</div>
