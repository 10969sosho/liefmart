<button type="{{ $type ?? 'button' }}" 
        class="btn btn-{{ $color ?? 'primary' }} {{ $class ?? '' }}"
        {{ $attributes }}>
    @if(isset($icon))
    <i class="{{ $icon }} {{ isset($slot) && !empty($slot) ? 'me-2' : '' }}"></i>
    @endif
    {{ $slot }}
</button> 