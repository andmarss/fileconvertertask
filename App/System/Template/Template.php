<?php

namespace App\System\Template;

use App\System\File;

class Template
{
    protected $shared;

    protected $sectionStack = [];

    protected $sections = [];

    protected $data = [];

    protected $compilers = [
        'layouts',
        'comments',
        'echos',
        'forelse',
        'empty',
        'endforelse',
        'structure_openings',
        'structure_closings',
        'imports',
        'else',
        'yields',
        'yield_sections',
        'section_start',
        'section_end'
    ];

    public function parse ($path = '')
    {
        $file = null;

        if(!is_dir(File::cachePath())) {
            mkdir(File::cachePath(), 0777, true);
        }

        if ( !File::exists( File::getCompiledPath( $path ) ) || File::isExpired( $path ) ) { // если отсутствует скомилированный файл, или исходный файл был изменен
            $parsed =  $this->compile( File::get( $path ) ); // получаем из папки views

            File::put( File::getCompiledPath( $path ), $parsed ); // записываем в файл в папке cache
        }

        $path = File::getCompiledPath( $path );

        return $path;
    }

    /**
     * @param $value
     * @return mixed
     *
     * Получаем шаблонный контент, возвращаем контент, поддерживаемый PHP
     */

    protected function compile( $value )
    {
        foreach ($this->compilers as $compiler) {
            $method = "compile_${compiler}";

            $value = $this->{$method}($value);
        }

        return $value;
    }

    /**
     * @param $value
     * @return string
     *
     * Компилим шаблоны
     */

    protected function compile_layouts($value)
    {
        if (strpos($value, '@extend') !== 0)
        {
            return $value;
        }

        $lines = preg_split("/(\r?\n)/", $value);

        $lines[] = preg_replace( $this->matcher('extend'), '$1@import$2', $lines[0]); // добавляем import в конец,
                                                                                                         // что бы импорт шаблона происходил в конце страницы

        return implode("\r\n", array_slice($lines, 1));
    }

    /**
     * @param $value
     * @return mixed
     *
     * Меняем {{ }} на <?= ;?>
     */

    protected function compile_echos($value)
    {
        return preg_replace('/\{\{(.+?)\}\}/', '<?=$1;?>', $value);
    }

    /**
     * @param $value
     * @return mixed
     *
     * foreach с проверкой, пустой ли массив
     */

    protected function compile_forelse($value)
    {
        preg_match_all('/(\s*)@forelse(\s*\(.*\))(\s*)/', $value, $matches);

        foreach ($matches[0] as $forelse) {
            preg_match('/\@forelse\(([^\s]+)+\s+as\s+([^\)]+)\)/', $forelse, $variable);

            $if = "<?php if (count({$variable[1]}) > 0): ?>";

            $search = '/(\s*)@forelse(\s*\(.*\))/';

            $replace = '$1'.$if.'<?php foreach$2: ?>';

            $template = preg_replace($search, $replace, $forelse);

            $value = str_replace($forelse, $template, $value);
        }

        return $value;
    }

    /**
     * @param $value
     * @return mixed
     *
     * Продолжаем компилить forelse
     * Если массив пустой, то отрабатывает то, что идет после @empty
     */

    protected function compile_empty($value)
    {
        return str_replace('@empty', '<?php endforeach; ?><?php else: ?>', $value);
    }

    /**
     * @param $value
     * @return mixed
     *
     * Меняем @endforelse на <?php endif; ?>
     */

    protected function compile_endforelse($value)
    {
        return str_replace('@endforelse', '<?php endif; ?>', $value);
    }

    /**
     * @param $value
     * @return mixed
     *
     * Меняем шаблонные управляющие конструкции на оригинальные
     */

    protected function compile_structure_openings($value)
    {
        return preg_replace('/(\s*)@(if|elseif|foreach|for|while)(\s*\(.*\))/', '$1<?php $2$3: ?>', $value);
    }

    /**
     * @param $value
     * @return mixed
     *
     * Меняем закрытие шаблонных управляющих конструкций на оригинальные
     */

    protected function compile_structure_closings ($value)
    {
        return preg_replace('/(\s*)@(endif|endforeach|endfor|endwhile)(\s*)/', '$1<?php $2; ?>$3', $value);
    }

    /**
     * @param $value
     * @return mixed
     *
     * Меняем @else на <?php else : ?>
     */

    protected function compile_else($value)
    {
        return preg_replace('/(\s*)@(else)(\s*)/', '$1<?php $2: ?>$3', $value);
    }

    /**
     * @param $value
     * @return mixed
     *
     * Меняем @import на view
     */

    protected function compile_imports($value)
    {
        return preg_replace($this->matcher('import'), '$1<?=view$2->with(get_defined_vars())->render(); ?>', $value);
    }

    /**
     * @param $value
     * @return mixed
     *
     * Меняем @yield на App\Section::yield
     */

    protected function compile_yields($value)
    {
        return preg_replace( $this->matcher('yield') , '$1<?=\\App\\System\\Template\\Section::yield$2; ?>', $value);
    }

    /**
     * @param $value
     * @return mixed
     *
     * Меняем @yield_section на App\Section::yield_section
     */

    protected function compile_yield_sections($value)
    {
        return str_replace('@yield_section',
            '<?php echo \\App\\System\\Template\\Section::yield_section(); ?>',
            $value);
    }

    /**
     * @param $value
     * @return mixed
     *
     * Меняем @section на App\Section::start
     */

    protected function compile_section_start($value)
    {
        return preg_replace( $this->matcher('section'), '$1<?php \\App\\System\\Template\\Section::start$2; ?>', $value );
    }

    /**
     * @param $value
     * @return mixed
     *
     * Меняем @endsection на App\Section::end
     */

    protected function compile_section_end($value)
    {
        return preg_replace( '/@endsection/', '$1<?php \\App\\System\\Template\\Section::stop(); ?>', $value );
    }

    /**
     * @param $value
     * @return mixed
     *
     * Меняем шаблонные коментарии на оригинальные
     */

    protected static function compile_comments($value)
    {
/*        $value = preg_replace('/\{\{--(.+?)(--\}\})?\n/', "<?php // $1 ?>", $value);*/

            $value = preg_replace('/\{\{\-\-/', "<?php /* ", $value);
            $value = preg_replace('/\-\-\}\}/', " */ ;?>", $value);

        return $value;
    }

    /**
     * @param $method
     * @return string
     *
     * Шаблон
     */

    protected function matcher($method)
    {
        return "/(\s*)@{$method}(\s*\(.*\))/";
    }
}