<?php

namespace App\Controllers;

use App\System\{
    Request,
    UploadedFile,
    Workers\Archive,
    Workers\Directory,
    Workers\File as FileWorker,
    File
};

class IndexController
{
    public function index(Request $request)
    {
        return view('index');
    }

    public function upload(Request $request)
    {
        /**
         * @var UploadedFile $file
         */
        $uploadedFile = $request->file('archive');
        /**
         * @var string $name
         */
        $name = $uploadedFile->getClientOriginalName();

        if($uploadedFile->move('uploaded/archives' , $name)) {

            $archive = new Archive(content_path('uploaded/archives/' . $name));

            $archive->unzip(content_path('uploaded/archives'));
            /**
             * @var string $directory
             */
            $directory = content_path('uploaded/archives/' . $archive->name(true));
            /**
             * @var Directory $directory
             */
            $directory = Directory::open($directory);

            $directory->files([
                    'jpg',
                    'jpeg',
                    'png',
                    'bmp',
                    'gif'
            ])->map(function (FileWorker $file) use ($uploadedFile, $directory) {
                $oldName = $file->name();
                /**
                 * @var Directory $fileDirectory
                 */
                $fileDirectory = $file->directory();
                // если у файлов есть кирилические символы
                if($file->hasCyrillicCharacters()) {
                    $slug = slug($file->name());
                    // если файл с таким именем уже существует
                    if(File::exists($fileDirectory->path() . $slug)) {
                        // фильтруем файлы из директории по регулярному выражению
                        $filtered = $fileDirectory
                            ->files($file->extension())
                            ->filter(function (FileWorker $directory_file) use ($file){
                                return (bool) preg_match('/' . slug($file->name(true) . '\_\d+/'), $directory_file->name());
                            });
                        // если файлы по такому паттерну есть
                        if($filtered->count() > 0) {
                            /**
                             * Получаем последнее изображение
                             * @var FileWorker $last
                             */
                            $last = $filtered->last();
                            // ищем число в его имени, например kartinka_01.jpeg
                            preg_match('/\d+/', $last->name(), $m);
                            /**
                             * преобразуем строку в число
                             * @var int $num
                             */
                            $num = intval($m[0]);
                            // если число меньше 10, то подставляем 0 перед ним
                            if($num < 10) {
                                $num = sprintf("%02d", ++$num);
                            } else { // иначе - просто инкрементируем
                                $num = (string) ++$num;
                            }
                            // получаем новое имя файла
                            $slug = sprintf("%s_%s.%s", slug($file->name(true)), $num, $file->extension());
                        } else { // если файлов с таким именем нет - то добавляем суффикс _01
                            $slug = sprintf("%s_01.%s", slug($file->name(true)), $file->extension());
                        }

                        $file = $file->rename($slug);
                    } else {
                        $file = $file->rename($slug);
                    }
                    // находим все html файлы
                    // проверяем, есть ли там изображение со старым наименованием файла
                    // если да - меняем старое наименование на новое, и исправляем путь на относительный
                    $directory
                        ->files('html')
                        ->each(function (FileWorker $html) use ($file, $oldName) {
                            if($html->contentExist($oldName)) {
                                $html->replace(
                                    'src\=[\'|\"](.*?)(' .$oldName . ')[\'|\"]',
                                    sprintf("src=\"%s\"", $file->relativePath($html->path(true))),
                                    true);
                            }
                        });
                } else { // если кирилических символов нет - просто изменяем путь на правильный (относительный)
                    $directory
                        ->files('html')
                        ->each(function (FileWorker $html) use ($file, $oldName) {
                            if($html->contentExist($oldName)) {
                                $html->replace(
                                    'src\=[\'|\"](.*?)(' .$oldName . ')[\'|\"]',
                                    sprintf("src=\"%s\"", $file->relativePath($html->path(true))),
                                    true);
                            }
                        });
                }

                return $file;
            });

            $archive->zip($directory->files()->all());

            $directory->delete();

            return response()->download($archive->path());
        }
    }

    public function download(Request $request, $path)
    {
        return response()->download(preg_replace('/^\//', '', $path));
    }
}