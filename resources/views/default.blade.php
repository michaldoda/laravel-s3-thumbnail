<img
        src="{{$path}}"
        alt="{{$alt}}"
        @if (isset($classNames) && $classNames)
        class="{{implode(' ', $classNames)}}"
        @endif
        @if(isset($width) && isset($height) && $width && $height)
        width="{{$width}}"
        height="{{$height}}"
        @endif
/>