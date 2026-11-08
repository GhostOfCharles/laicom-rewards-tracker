@props(['path'])

@php
    $file = \Illuminate\Support\Facades\Storage::disk('private')->path($path);
    $dimensions = is_file($file) ? @getimagesize($file) : false;
@endphp

<img width="{{ $dimensions[0] ?? 384 }}" height="{{ $dimensions[1] ?? 288 }}"
     {{ $attributes->merge(['decoding' => 'async', 'loading' => 'lazy', 'style' => 'width:auto;height:auto;']) }}>
