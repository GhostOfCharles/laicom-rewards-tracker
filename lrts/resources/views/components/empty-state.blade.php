@props(['title', 'description' => '', 'icon' => 'inbox'])
<div {{ $attributes->class(['lrts-empty-state']) }}><i class="bi bi-{{ $icon }}" aria-hidden="true"></i><strong>{{ $title }}</strong>@if($description)<p>{{ $description }}</p>@endif{{ $slot }}</div>
