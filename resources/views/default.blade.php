<img
        @if ($lazyLoading)
        data-src="{{$path}}"
        @else
        src="{{$path}}"
        @endif
        alt="{{$alt}}"
        @if (isset($classNames) && $classNames)
        class="{{implode(' ', $classNames)}}"
        @endif
        @if(isset($width) && isset($height) && $width && $height)
        width="{{$width}}"
        height="{{$height}}"
        @endif
/>