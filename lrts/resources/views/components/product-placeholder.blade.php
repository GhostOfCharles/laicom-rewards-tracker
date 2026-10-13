@props(['label' => 'Product', 'initials' => '?', 'size' => 48, 'class' => '', 'hidden' => false])

<svg class="lrts-product-placeholder {{ $class }}" viewBox="0 0 100 100" width="{{ $size }}" height="{{ $size }}"
     role="img" aria-label="{{ $label }}" @if($hidden) hidden @endif
     style="width:{{ $size }}px;height:{{ $size }}px;flex:0 0 {{ $size }}px;">
    <rect x="1" y="1" width="98" height="98" fill="#fff" stroke="#12284c" stroke-width="2" />
    <path d="M8 8h20v6H14v14H8z" fill="#ed1b24" />
    <text x="50" y="55" text-anchor="middle" dominant-baseline="middle" fill="#12284c"
          font-family="Segoe UI,Arial,sans-serif" font-size="{{ mb_strlen($initials) > 1 ? 34 : 46 }}" font-weight="800">{{ $initials }}</text>
</svg>
