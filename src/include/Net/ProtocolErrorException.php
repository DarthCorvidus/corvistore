<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Net;

/**
 * A ProtocolErrorException is thrown if some Protocol type is expected, but
 * Protocol::ERROR is received.
 * It is for the receiving part to decide if the error can be recovered or not.
 */
class ProtocolErrorException extends \RuntimeException {

}
