<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
 * @version 2.0.4
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
 */

namespace figdice\exceptions;

/**
 * In general, in FigDice, the Views should be waterproof most of the time,
 * and you should handle properly any execution errors in your Feed
 * implementations (such as database failures, filesystem errors and so on,
 * and of course any logic situations). The consumer of your feeds should
 * never have to worry about errors, and it is recommended that your feeds 
 * simply return an empty result in case of failure.
 * However, in some cases you may have to report a severe runtime error,
 * which should cause the normal rendering workflow to break. Use this
 * exception to indicate the rendering engine to stop. Your controller should
 * catch this exception and propose an alternate output.
 */
class FeedRuntimeException extends \Exception {
}