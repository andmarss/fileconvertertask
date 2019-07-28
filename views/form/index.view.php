<div class="panel panel-default">
    <div class="panel-heading">Загрузите архив</div>

    <div class="panel-body">
        <form enctype="multipart/form-data" action="{{route('upload')}}" method="post" id="store-form">
            <div class="form-group">
                <label for="archive" class="file-label personalArea__file-label--required"><i class="fas fa-download"></i>Выберите файл</label>
                <span id="remove-archive" class="remove-archive"><i class="fas fa-times"></i></span>
                <input type="file" name="archive" id="archive" class="hidden" accept=".zip,.rar" required>
            </div>

            <div class="form-group">
                <label for="archive-password">Если архив защищен паролем, введите его в поле ниже</label>
                <input type="password" id="archive-password" class="form-control" name="archive-password" placeholder="Пароль для архива">
            </div>

            <div class="form-group">
                <div class="text-right">
                    <button class="btn btn-success">Загрузить архив</button>
                </div>
            </div>
        </form>
    </div>
</div>