<ul class="loaded-archives">
    @if(\App\System\Session::has('archives'))
        @forelse(\App\System\Session::get('archives') as $archive)
            @import('includes/archive', ['archive' => $archive])
        @empty
            <li>
                <h4>Загруженых архивов еще нет</h4>
            </li>
        @endforelse
    @endif
</ul>
