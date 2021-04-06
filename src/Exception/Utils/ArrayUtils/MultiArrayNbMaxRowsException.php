<?php

namespace Smart\SonataBundle\Exception\Utils\ArrayUtils;

use Symfony\Component\HttpFoundation\Response;

class MultiArrayNbMaxRowsException extends \Exception
{
    /** @var int store the maximum number of lines allowed */
    public $nbMaxRows;

    /** @var int store the number of lines */
    public $nbRows;

    public function __construct(int $nbMaxRows, int $nbRows)
    {
        $this->nbMaxRows = $nbMaxRows;
        $this->nbRows = $nbRows;

        parent::__construct(
            'array_utils.multi_array_nb_max_rows_error',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            null
        );
    }
}
