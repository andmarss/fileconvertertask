<?php

namespace App\Controllers;

use App\System\{
    Router,
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
        $name = time() . $uploadedFile->getClientOriginalName();

        if($uploadedFile->move('uploaded/archives' , $name)) {

            $archive = new Archive(content_path('uploaded/archives/' . $name));

            $archive->unzip(content_path('uploaded/archives/' . $archive->name(true)));
            /**
             * @var string $directory
             */
            $directory = content_path('uploaded/archives/' . $archive->name(true));
            /**
             * @var array $files
             */
            $files = Directory::open($directory)->files([
                    '*.[jJ][pP][gG]',
                    '*.[jJ][pP][eE][gG]',
                    '*.[pP][nN][gG]',
                    '*.[bB][mM][pP]',
                    '*.[gG][iI][fF]'
            ])->map(function (FileWorker $file) use ($uploadedFile, $directory) {
                $oldName = $file->name();
                /**
                 * @var Directory $fileDirectory
                 */
                $fileDirectory = $file->directory();
                // если у файлов есть кирилические символы
                if($file->hasCyrillicCharacters()) {
                    $slug = slug($file->name());

                    if(File::exists($fileDirectory->path() . $slug)) {
                        $slug = time() . $slug;

                        $file = $file->rename($slug);
                    } else {
                        $file = $file->rename($slug);
                    }

                    Directory::open($directory)
                        ->files('*.[hH][tT][mM][lL]')
                        ->each(function (FileWorker $html) use ($file, $oldName) {
                            if($html->contentExist($oldName)) {
                                $html->replace($oldName, $file->name());
                            }
                        });
                }

                return $file;
            })->all();

            $archive->zip($files);


        }
    }

    public function download(Request $request)
    {

    }
}