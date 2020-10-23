<?php
namespace Zoomx;

use modParser;

class Parser extends modParser
{

    public function parse($string, array $data = [])
    {

        $scope = count($data) > 0 ? $this->modx->toPlaceholders($data, '', '.', true) : [];

        $maxIterations = (int)$this->modx->getOption('parser_max_iterations', null, 10);
        $this->modx->parser->processElementTags('', $string, false, false, '[[', ']]', [], $maxIterations);
        $this->modx->parser->processElementTags('', $string, true, true, '[[', ']]', [], $maxIterations);

        if (isset($scope['keys'])) {
            $this->modx->unsetPlaceholders($scope['keys']);
        }
        if (isset($scope['restore'])) {
            $this->modx->toPlaceholders($scope['restore']);
        }

        return $string;
    }
}