<?php

namespace Smart\SonataBundle\Exception\Utils\ArrayUtils;

use Symfony\Component\HttpFoundation\Response;

class MultiArrayNbColumnsException extends \Exception
{
    /** @var array<int, int> List of keys of the MultiArray where the error occurred*/
    public $keys;

    /**
     * MultiArrayNbColumnsException constructor.
     * @param array<int, int> $keys
     */
    public function __construct(array $keys)
    {
        $this->keys = $keys;

        parent::__construct(
            'array_utils.multi_array_nb_columns_error',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            null
        );
    }
}
