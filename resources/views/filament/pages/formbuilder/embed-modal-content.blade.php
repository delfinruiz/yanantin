<div class="space-y-4 text-sm">
    @if($id)
        <div>
            <div class="font-medium mb-1">Iframe</div>
            <pre class="p-3 bg-gray-100 rounded-lg overflow-x-auto">&lt;iframe src=&quot;{{ route('forms.embed', $id) }}&quot; width=&quot;100%&quot; style=&quot;border:0;overflow:hidden;&quot; scrolling=&quot;no&quot;&gt;&lt;/iframe&gt;</pre>
        </div>
        <div>
            <div class="font-medium mb-1">Script</div>
            <pre class="p-3 bg-gray-100 rounded-lg overflow-x-auto">&lt;div id=&quot;form-{{ $id }}&quot;&gt;&lt;/div&gt;
&lt;script&gt;(function(){var d=document,container=d.getElementById('form-{{ $id }}');if(!container)return;var f=d.createElement('iframe');f.src='{{ route('forms.embed', $id) }}';f.style.border='0';f.style.width='100%';f.style.overflow='hidden';f.setAttribute('scrolling','no');container.appendChild(f);function receive(e){if(!e.data||e.data.type!=='formbuilder:resize'||e.data.id!=='{{ $id }}')return;f.style.height=e.data.height+'px';}window.addEventListener('message',receive,false);}());&lt;/script&gt;</pre>
        </div>
    @else
        <div class="text-red-500">Error: ID no encontrado.</div>
    @endif
</div>
