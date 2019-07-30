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
            Directory::open($directory)->files([
                    '*.jpg',
                    '*.jpeg',
                    '*.png',
                    '*.bmp',
                    '*.gif'
            ])->each(function (FileWorker $file, Directory $fileDirectory) use ($uploadedFile, $directory) {
                $oldName = $file->name();

                if($file->hasCyrillicCharacters()) {
                    $slug = slug($file->name());

                    if(File::exists($fileDirectory->path() . $slug)) {
                        $slug = time() . $slug;

                        $file = $file->rename($slug);
                    } else {
                        $file = $file->rename($slug);
                    }

                    Directory::open($directory)
                        ->files('*.html')
                        ->each(function (FileWorker $html) use ($file, $oldName) {
                            if($html->contentExist($oldName)) {
                                $html->replace($oldName, $file->name());
                            }
                        });
                }
            });
        }
    }

    public function download(Request $request)
    {

    }
}