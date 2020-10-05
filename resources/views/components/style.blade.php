@if(!is_null($source))
<link
    rel="stylesheet"
    href="{{ $source }}"
    {{ $attributes }}
>
@else
<style>
    @isset($slot)
    {{ $slot }}
    @endisset
</style>
@endif