<?php

namespace Spatie\BladeX;

use SimpleXMLElement;

class BladeXCompiler
{
    /** @var \Spatie\BladeX\BladeX */
    protected $bladeX;

    public function __construct(BladeX $bladeX)
    {
        return $this->bladeX = $bladeX;
    }

    public function compile(string $viewContents): string
    {
        foreach ($this->bladeX->getRegisteredComponents() as $componentName => $viewPath) {
            $viewContents = $this->replaceBladeXComponentWithRegularBladeComponent($viewContents, $componentName, $viewPath);
        }

        return $viewContents;
    }

    protected function replaceBladeXComponentWithRegularBladeComponent(string $viewContents, $bladeXComponentName, string $bladeViewPath)
    {
        $pattern = '/<\s*' . $bladeXComponentName . '[^>]*>((.|\n)*?)<\s*\/\s*' . $bladeXComponentName . '>/m';

        $viewContents = preg_replace_callback($pattern, function (array $regexResult) use ($bladeViewPath) {
            [$componentHtml, $componentInnerHtml] = $regexResult;

            return "@component('{$bladeViewPath}', [{$this->getComponentAttributes($componentHtml)}])"
                . $this->parseComponentInnerHtml($componentInnerHtml)
                . '@endcomponent';
        }, $viewContents);
        return $viewContents;
    }

    protected function getComponentAttributes(string $componentHtml): string
    {
        $componentXml = new SimpleXMLElement($componentHtml);

        return collect($componentXml->attributes())
            ->map(function ($value, $attribute) {
                $value = str_replace("'", "\\'", $value);

                return "'{$attribute}' => '{$value}',";
            })->implode('');
    }

    protected function parseComponentInnerHtml(string $componentInnerHtml): string
    {
        $pattern = '/<\s*slot[^>]*name=[\'"](.*)[\'"][^>]*>((.|\n)*?)<\s*\/\s*slot>/m';

        return preg_replace_callback($pattern, function ($result) {
            [$slot, $name, $contents] = $result;

            return "@slot('{$name}'){$contents}@endslot";
        }, $componentInnerHtml);
    }
}