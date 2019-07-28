@if (isset($archive))
    <li class="task-content">
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="text-left">
                    <i class="fa fa-file-archive-o" aria-hidden="true"></i>
                </div>
                <p class="text-left">
                    <a href="{{route('download', ['path' => $archive['path'])}}" class="unlink text-primary">Скачать архив</a>
                </p>
            </div>
        </div>
    </li>
@endif