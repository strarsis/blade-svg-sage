<?php

namespace BladeSvg;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;

class SvgFactory
{
    private $files;
    private $svgCache;
    private $config;

    public function __construct($config = [], $filesystem = null)
    {
        $this->config = Collection::make($config);
        $this->svgCache = Collection::make();
        $this->files = $filesystem ?: new Filesystem;
    }

    private function svgPath()
    {
        return $this->config->get('svg_path', function () {
            throw new Exception('No svg_path set!');
        });
    }

    private function spritesheetPath()
    {
        return $this->config->get('spritesheet_path', function () {
            throw new Exception('No spritesheet_path set!');
        });
    }

    public function spritesheetUrl()
    {
        return $this->config->get('spritesheet_url', '');
    }

    public function spritesheet()
    {
        return new HtmlString(
            sprintf(
                '<div style="height: 0; width: 0; position: absolute; visibility: hidden;">%s</div>',
                $this->files->exists($this->spritesheetPath()) ? $this->files->get($this->spritesheetPath()) : ''
            )
        );
    }

    public function svg($name, $class = '', $attrs = [])
    {
        if (is_array($class)) {
            $attrs = $class;
            $class = '';
        }

        $attrs = array_merge([
            'class' => $this->buildClass($class),
        ], $attrs);

        return new Svg($name, $this->renderMode(), $this, $attrs);
    }

    public function spriteId($svg)
    {
        return "{$this->spritePrefix()}{$svg}";
    }

    private function spritePrefix()
    {
        return $this->config->get('sprite_prefix');
    }

    private function renderMode()
    {
        return $this->config['inline'] ? 'inline' : 'sprite';
    }

    private function buildClass($class)
    {
        return trim(sprintf('%s %s', $this->config['class'], $class));
    }

    public function getSvg($name)
    {
        return $this->svgCache->get($name, function () use ($name) {
            $path_raw = sprintf('%s/%s.svg', rtrim($this->svgPath()), $name);

            $path_without_dist = str_replace('/dist/', '', $path_raw);
            $path_sage = \App\sage('assets')->get($path_without_dist);

            $svgpath_without_dist = str_replace('/dist/', '', $this->svgPath());
            $name_sage = str_replace('.svg', '', 
              str_replace($svgpath_without_dist . '/', '', $path_sage)
            );

            $path = get_template_directory() . '/..' . sprintf('%s/%s.svg', rtrim($this->svgPath()), $name_sage);

            return $this->svgCache[$name] = $this->files->exists($path) ? $this->files->get($path) : $this->svgPath().' not found.';
        });
    }
}
