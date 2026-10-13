@props(['product' => null, 'name' => null, 'size' => 48, 'class' => ''])

@php
    $r = app(\App\Services\ProductImageResolver::class)->resolve($product, $name);
@endphp

@if ($r['src'])
    <img src="{{ $r['src'] }}" alt="{{ $r['label'] }}" loading="lazy"
         class="{{ $class }}"
         style="width:{{ $size }}px;height:{{ $size }}px;object-fit:contain;"
         onerror="this.hidden=true;this.nextElementSibling.hidden=false;">
    <x-product-placeholder :label="$r['label']" :initials="$r['initials']" :size="$size" :class="$class" :hidden="true" />
@else
    <x-product-placeholder :label="$r['label']" :initials="$r['initials']" :size="$size" :class="$class" />
@endif
