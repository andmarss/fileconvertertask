@if (isset($archive))
    <li class="task-content">
        <div class="panel panel-default">
            <div class="panel-body">
                <p class="text-left">
                    {{$archive['date']}}
                </p>
                <p class="text-left">
                    <span><i class="fa fa-file-archive-o" aria-hidden="true"></i></span>&nbsp;&nbsp;&nbsp;<a href="{{route('download', ['path' => $archive['path']])}}" class="unlink text-primary">{{$archive['name']}}</a>
                </p>
            </div>
        </div>
    </li>
@endif