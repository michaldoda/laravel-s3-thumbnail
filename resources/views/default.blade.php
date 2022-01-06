@if (isset($classNames) && $classNames)
    <img src="{{$path}}" alt="{{$alt}}" class="{{implode(' ', $classNames)}}">
@else
    <img src="{{$path}}" alt="{{$alt}}">
@endif
