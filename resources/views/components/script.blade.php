<script
    @if(!is_null($source))
    src="{{ $source }}"
    @endif
    {{ $attributes }}
>
    @isset($slot)
    {{ $slot }}
    @endisset
</script>