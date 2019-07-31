@extend('layer/index')

@section('content')
    <div class="container">
        @import('form/index')
    </div>
@endsection

@section('overlay')
    @import('includes/overlay')
@endsection
