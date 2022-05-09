<picture data-alt="{{$alt}}" @if (isset($classNames) && $classNames) class="{{implode(' ', $classNames)}}" @endif>
    @foreach($queries as $query)
        <source srcset="{{$query['path']}}" media="(max-width: {{$query['width']}}px)">
    @endforeach
    @if ($lazyLoading)
        <img data-src="{{$defaultPath}}" alt="{{$alt}}">
    @else
        <img src="{{$defaultPath}}" alt="{{$alt}}">
    @endif
</picture>
