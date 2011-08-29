<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Reflection
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Code\Reflection;

use ReflectionFunction as PhpReflectionFunction,
    Zend\Code\Reflection;

/**
 * @uses       ReflectionFunction
 * @uses       \Zend\Code\Reflection\ReflectionDocblockTag
 * @uses       \Zend\Code\Reflection\Exception
 * @uses       \Zend\Code\Reflection\ReflectionParameter
 * @category   Zend
 * @package    Zend_Reflection
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class ReflectionFunction extends PhpReflectionFunction implements Reflection
{
    /**
     * Get function docblock
     *
     * @param  string $reflectionClass Name of reflection class to use
     * @return Zend_Reflection_Docblock
     */
    public function getDocblock($reflectionClass = 'Zend\Code\Reflection\ReflectionDocblock')
    {
        if ('' == ($comment = $this->getDocComment())) {
            throw new Exception\InvalidArgumentException($this->getName() . ' does not have a docblock');
        }
        $instance = new $reflectionClass($comment);
        if (!$instance instanceof ReflectionDocblock) {
            throw new Exception\InvalidArgumentException('Invalid reflection class provided; must extend Zend\Code\Reflection\ReflectionDocblock');
        }
        return $instance;
    }

    /**
     * Get start line (position) of function
     *
     * @param  bool $includeDocComment
     * @return int
     */
    public function getStartLine($includeDocComment = false)
    {
        if ($includeDocComment) {
            if ($this->getDocComment() != '') {
                return $this->getDocblock()->getStartLine();
            }
        }

        return parent::getStartLine();
    }

    /**
     * Get contents of function
     *
     * @param  bool $includeDocblock
     * @return string
     */
    public function getContents($includeDocblock = true)
    {
        return implode("\n",
            array_splice(
                file($this->getFileName()),
                $this->getStartLine($includeDocblock),
                ($this->getEndLine() - $this->getStartLine()),
                true
                )
            );
    }

    /**
     * Get function parameters
     *
     * @param  string $reflectionClass Name of reflection class to use
     * @return array Array of \Zend\Code\Reflection\ReflectionParameter
     */
    public function getParameters($reflectionClass = 'Zend\Code\Reflection\ReflectionParameter')
    {
        $phpReflections  = parent::getParameters();
        $zendReflections = array();
        while ($phpReflections && ($phpReflection = array_shift($phpReflections))) {
            $instance = new $reflectionClass($this->getName(), $phpReflection->getName());
            if (!$instance instanceof ReflectionParameter) {
                throw new Exception\InvalidArgumentException('Invalid reflection class provided; must extend Zend_Reflection_Parameter');
            }
            $zendReflections[] = $instance;
            unset($phpReflection);
        }
        unset($phpReflections);
        return $zendReflections;
    }

    /**
     * Get return type tag
     *
     * @return \Zend\Code\Reflection\Docblock\Tag\Return
     */
    public function getReturn()
    {
        $docblock = $this->getDocblock();
        if (!$docblock->hasTag('return')) {
            throw new Exception\InvalidArgumentException('Function does not specify an @return annotation tag; cannot determine return type');
        }
        $tag    = $docblock->getTag('return');
        $return = ReflectionDocblockTag::factory('@return ' . $tag->getDescription());
        return $return;
    }

    /**
     * Required due to bug in php
     * @return void
     */
    public function __toString()
    {
        return parent::__toString();
    }
}
