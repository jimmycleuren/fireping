<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 7/07/2017
 * Time: 15:00
 */

namespace AppBundle\Probe;


class MessageType extends \SplEnum
{
    const __default = self::NULL;

    const NULL = 0;

    /* StatusCodes indicating success */
    const OK = 200;

    /* StatusCodes indicating faulty input */
    const E_CLIENT = 400;

    /* StatusCodes indicating an error on the server */
    const E_SERVER = 500;

    /* Internal status code to handle rejected messages */
    const E_REJECT_GENERIC = 600;
    const E_REJECT_RETRY = 601;
    const E_REJECT_RETRY_PRIORITY = 602;
    const E_REJECT_DISCARD = 603;
    const E_REJECT_ABORT = 604;
}