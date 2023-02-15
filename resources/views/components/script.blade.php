<script @if(!is_null($source)) src="{{ $source }}" @endif {{ $attributes }}>
{{ $slot }}
</script>