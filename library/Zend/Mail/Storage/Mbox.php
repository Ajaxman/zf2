<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Mail
 */

namespace Zend\Mail\Storage;

/**
 * @category   Zend
 * @package    Zend_Mail
 * @subpackage Storage
 */
class Mbox extends AbstractStorage
{
    /**
     * file handle to mbox file
     * @var null|resource
     */
    protected $_fh;

    /**
     * filename of mbox file for __wakeup
     * @var string
     */
    protected $_filename;

    /**
     * modification date of mbox file for __wakeup
     * @var int
     */
    protected $_filemtime;

    /**
     * start and end position of messages as array('start' => start, 'separator' => headersep, 'end' => end)
     * @var array
     */
    protected $_positions;

    /**
     * used message class, change it in an extended class to extend the returned message class
     * @var string
     */
    protected $_messageClass = '\Zend\Mail\Storage\Message\File';

    /**
     * Count messages all messages in current box
     *
     * @return int number of messages
     * @throws \Zend\Mail\Storage\Exception\ExceptionInterface
     */
    public function countMessages()
    {
        return count($this->_positions);
    }


    /**
     * Get a list of messages with number and size
     *
     * @param  int|null $id  number of message or null for all messages
     * @return int|array size of given message of list with all messages as array(num => size)
     */
    public function getSize($id = 0)
    {
        if ($id) {
            $pos = $this->_positions[$id - 1];
            return $pos['end'] - $pos['start'];
        }

        $result = array();
        foreach ($this->_positions as $num => $pos) {
            $result[$num + 1] = $pos['end'] - $pos['start'];
        }

        return $result;
    }


    /**
     * Get positions for mail message or throw exception if id is invalid
     *
     * @param int $id number of message
     * @throws Exception\InvalidArgumentException
     * @return array positions as in _positions
     */
    protected function _getPos($id)
    {
        if (!isset($this->_positions[$id - 1])) {
            throw new Exception\InvalidArgumentException('id does not exist');
        }

        return $this->_positions[$id - 1];
    }


    /**
     * Fetch a message
     *
     * @param  int $id number of message
     * @return \Zend\Mail\Storage\Message\File
     * @throws \Zend\Mail\Storage\Exception\ExceptionInterface
     */
    public function getMessage($id)
    {
        // TODO that's ugly, would be better to let the message class decide
        if (strtolower($this->_messageClass) == '\zend\mail\storage\message\file'
            || is_subclass_of($this->_messageClass, '\Zend\Mail\Storage\Message\File')) {
            // TODO top/body lines
            $messagePos = $this->_getPos($id);
            return new $this->_messageClass(array('file' => $this->_fh, 'startPos' => $messagePos['start'],
                                                  'endPos' => $messagePos['end']));
        }

        $bodyLines = 0; // TODO: need a way to change that

        $message = $this->getRawHeader($id);
        // file pointer is after headers now
        if ($bodyLines) {
            $message .= "\n";
            while ($bodyLines-- && ftell($this->_fh) < $this->_positions[$id - 1]['end']) {
                $message .= fgets($this->_fh);
            }
        }

        return new $this->_messageClass(array('handler' => $this, 'id' => $id, 'headers' => $message));
    }

    /*
     * Get raw header of message or part
     *
     * @param  int               $id       number of message
     * @param  null|array|string $part     path to part or null for message header
     * @param  int               $topLines include this many lines with header (after an empty line)
     * @return string raw header
     * @throws \Zend\Mail\Protocol\Exception\ExceptionInterface
     * @throws \Zend\Mail\Storage\Exception\ExceptionInterface
     */
    public function getRawHeader($id, $part = null, $topLines = 0)
    {
        if ($part !== null) {
            // TODO: implement
            throw new Exception\RuntimeException('not implemented');
        }
        $messagePos = $this->_getPos($id);
        // TODO: toplines
        return stream_get_contents($this->_fh, $messagePos['separator'] - $messagePos['start'], $messagePos['start']);
    }

    /*
     * Get raw content of message or part
     *
     * @param  int               $id   number of message
     * @param  null|array|string $part path to part or null for message content
     * @return string raw content
     * @throws \Zend\Mail\Protocol\Exception\ExceptionInterface
     * @throws \Zend\Mail\Storage\Exception\ExceptionInterface
     */
    public function getRawContent($id, $part = null)
    {
        if ($part !== null) {
            // TODO: implement
            throw new Exception\RuntimeException('not implemented');
        }
        $messagePos = $this->_getPos($id);
        return stream_get_contents($this->_fh, $messagePos['end'] - $messagePos['separator'], $messagePos['separator']);
    }

    /**
     * Create instance with parameters
     * Supported parameters are:
     *   - filename filename of mbox file
     *
     * @param  $params array mail reader specific parameters
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($params)
    {
        if (is_array($params)) {
            $params = (object)$params;
        }

        if (!isset($params->filename)) {
            throw new Exception\InvalidArgumentException('no valid filename given in params');
        }

        $this->_openMboxFile($params->filename);
        $this->_has['top']      = true;
        $this->_has['uniqueid'] = false;
    }

    /**
     * check if given file is a mbox file
     *
     * if $file is a resource its file pointer is moved after the first line
     *
     * @param  resource|string $file stream resource of name of file
     * @param  bool $fileIsString file is string or resource
     * @return bool file is mbox file
     */
    protected function _isMboxFile($file, $fileIsString = true)
    {
        if ($fileIsString) {
            $file = @fopen($file, 'r');
            if (!$file) {
                return false;
            }
        } else {
            fseek($file, 0);
        }

        $result = false;

        $line = fgets($file);
        if (strpos($line, 'From ') === 0) {
            $result = true;
        }

        if ($fileIsString) {
            @fclose($file);
        }

        return $result;
    }

    /**
     * open given file as current mbox file
     *
     * @param  string $filename filename of mbox file
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     */
    protected function _openMboxFile($filename)
    {
        if ($this->_fh) {
            $this->close();
        }

        $this->_fh = @fopen($filename, 'r');
        if (!$this->_fh) {
            throw new Exception\RuntimeException('cannot open mbox file');
        }
        $this->_filename = $filename;
        $this->_filemtime = filemtime($this->_filename);

        if (!$this->_isMboxFile($this->_fh, false)) {
            @fclose($this->_fh);
            throw new Exception\InvalidArgumentException('file is not a valid mbox format');
        }

        $messagePos = array('start' => ftell($this->_fh), 'separator' => 0, 'end' => 0);
        while (($line = fgets($this->_fh)) !== false) {
            if (strpos($line, 'From ') === 0) {
                $messagePos['end'] = ftell($this->_fh) - strlen($line) - 2; // + newline
                if (!$messagePos['separator']) {
                    $messagePos['separator'] = $messagePos['end'];
                }
                $this->_positions[] = $messagePos;
                $messagePos = array('start' => ftell($this->_fh), 'separator' => 0, 'end' => 0);
            }
            if (!$messagePos['separator'] && !trim($line)) {
                $messagePos['separator'] = ftell($this->_fh);
            }
        }

        $messagePos['end'] = ftell($this->_fh);
        if (!$messagePos['separator']) {
            $messagePos['separator'] = $messagePos['end'];
        }
        $this->_positions[] = $messagePos;
    }

    /**
     * Close resource for mail lib. If you need to control, when the resource
     * is closed. Otherwise the destructor would call this.
     *
     */
    public function close()
    {
        @fclose($this->_fh);
        $this->_positions = array();
    }


    /**
     * Waste some CPU cycles doing nothing.
     *
     * @return boolean always return true
     */
    public function noop()
    {
        return true;
    }


    /**
     * stub for not supported message deletion
     *
     * @param $id
     * @throws Exception\RuntimeException
     */
    public function removeMessage($id)
    {
        throw new Exception\RuntimeException('mbox is read-only');
    }

    /**
     * get unique id for one or all messages
     *
     * Mbox does not support unique ids (yet) - it's always the same as the message number.
     * That shouldn't be a problem, because we can't change mbox files. Therefor the message
     * number is save enough.
     *
     * @param int|null $id message number
     * @return array|string message number for given message or all messages as array
     * @throws \Zend\Mail\Storage\Exception\ExceptionInterface
     */
    public function getUniqueId($id = null)
    {
        if ($id) {
            // check if id exists
            $this->_getPos($id);
            return $id;
        }

        $range = range(1, $this->countMessages());
        return array_combine($range, $range);
    }

    /**
     * get a message number from a unique id
     *
     * I.e. if you have a webmailer that supports deleting messages you should use unique ids
     * as parameter and use this method to translate it to message number right before calling removeMessage()
     *
     * @param string $id unique id
     * @return int message number
     * @throws \Zend\Mail\Storage\Exception\ExceptionInterface
     */
    public function getNumberByUniqueId($id)
    {
        // check if id exists
        $this->_getPos($id);
        return $id;
    }

    /**
     * magic method for serialize()
     *
     * with this method you can cache the mbox class
     *
     * @return array name of variables
     */
    public function __sleep()
    {
        return array('_filename', '_positions', '_filemtime');
    }

    /**
     * magic method for unserialize()
     *
     * with this method you can cache the mbox class
     * for cache validation the mtime of the mbox file is used
     *
     * @throws Exception\RuntimeException
     */
    public function __wakeup()
    {
        if ($this->_filemtime != @filemtime($this->_filename)) {
            $this->close();
            $this->_openMboxFile($this->_filename);
        } else {
            $this->_fh = @fopen($this->_filename, 'r');
            if (!$this->_fh) {
                throw new Exception\RuntimeException('cannot open mbox file');
            }
        }
    }

}
