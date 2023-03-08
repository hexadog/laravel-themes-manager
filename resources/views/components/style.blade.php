@if(!is_null($source))
<link rel="stylesheet" href="{{ $source }}" {{ $attributes }}>
@else
<style>
{{ $slot }}
</style>
@endif