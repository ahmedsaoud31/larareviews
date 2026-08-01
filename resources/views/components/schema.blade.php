@props([
    'schemaData' => [],
])

@if(!empty($schemaData))
<script type="application/ld+json">
{!! json_encode($schemaData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endif
