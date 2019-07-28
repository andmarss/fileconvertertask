<h3 class="text-center text-muted">
    Загруженные архивы
</h3>

<ul class="loaded-archives">
    @if(is_array(\App\System\Session::get('archives')))
        @forelse(\App\System\Session::get('archives') as $archive)
            @import('includes/archive', ['archive' => $archive])
        @empty
            <li>
                <h4>Загруженых архивов еще нет</h4>
            </li>
        @endforelse
    @else
        <li>
            <h4>Загруженых архивов еще нет</h4>
        </li>
    @endif
</ul>
