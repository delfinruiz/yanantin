<div class="p-8 max-h-[70vh] overflow-auto bg-gray-50 rounded-lg">
    <pre class="whitespace-pre-wrap text-sm">{{ \File::get(storage_path('app/public' . request()->get('path', ''))) }}</pre>
</div>