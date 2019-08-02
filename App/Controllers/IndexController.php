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

            Directory::open($directory)->files([
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

                    if(File::exists($fileDirectory->path() . $slug)) {
                        $slug = time() . $slug;

                        $file = $file->rename($slug);
                    } else {
                        $file = $file->rename($slug);
                    }

                    Directory::open($directory)
                        ->files('html')
                        ->each(function (FileWorker $html) use ($file, $oldName) {
                            if($html->contentExist($oldName)) {
                                $html->replace(
                                    'src\=[\'|\"](.*?)(' .$oldName . ')[\'|\"]',
                                    'src="' .$file->relativePath($html->path(true)) . '"',
                                    true);
                            }
                        });
                } else {
                    Directory::open($directory)
                        ->files('html')
                        ->each(function (FileWorker $html) use ($file, $oldName) {
                            if($html->contentExist($oldName)) {
                                $html->replace(
                                    'src\=[\'|\"](.*?)(' .$oldName . ')[\'|\"]',
                                    'src="' .$file->relativePath($html->path(true)) . '"',
                                    true);
                            }
                        });
                }

                return $file;
            });

            $archive->zip(Directory::open($directory)->files()->all());

            Directory::open($directory)->delete();

            return response()->download($archive->path());
        }
    }

    public function download(Request $request, $path)
    {
        return response()->download(preg_replace('/^\//', '', $path));
    }
}