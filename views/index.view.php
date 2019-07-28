@extend('layer/index')

@section('content')
    <div class="container">
        @import('form/index')

        @import('includes/loaded-archives')
    </div>
@endsection

@section('overlay')
    @import('includes/overlay')
@endsection
