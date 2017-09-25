<?php

/*
 * Copyright (C) 2017 petr
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Positronium;

/**
 * Description of FileIterator
 *
 * @author petr
 */
class FileLineIterator implements \SeekableIterator
{
    /**
     *
     * @var resource
     */
    private $handle;

    /**
     *
     * @var int
     */
    private $idx;

    /**
     *
     * @var int[]
     */
    private $linesPositions = [];

    /**
     *
     * @param string $fname      Имя файла
     * @throws \RuntimeException Если файл не удалось открыть для чтения
     */
    public function __construct($fname)
    {
        if (!is_readable($fname)) {
            throw new \RuntimeException("$fname is not readable");
        }
        $mode = 'r';
        $this->handle = fopen($fname, $mode);
        if (!$this->handle) {
            throw new \RuntimeException("$fname cannot be opened for $mode");
        }
        $this->loadPositions(); // Можно делать лениво, по пути с next и/или при первом seek
    }

    /**
     * Инициализирует массив позиций строк.
     */
    private function loadPositions()
    {
        $pos = 0;
        $i = 0;
        $this->linesPositions[$i] = 0;
        while (!feof($this->handle) && ($line = fgets($this->handle))) {
            ++$i;
            $len = strlen($line);
            $this->linesPositions[$i] = $len + $pos;
            $pos += $len;
        }
    }

    /**
     * Деструктор. Закрывает handle файла.
     */
    public function __destruct()
    {
        $this->handle && fclose($this->handle);
    }

    /**
     * @inheritDoc
     * @return string|null
     */
    public function current()
    {
        if (!isset($this->linesPositions[$this->idx])) {
            return null;
        }
        fseek($this->handle, $this->linesPositions[$this->idx]);
        return fgets($this->handle);
    }

    /**
     * @inheritDoc
     * @return int
     */
    public function key()
    {
        return $this->idx;
    }

    /**
     * @inheritDoc
     */
    public function next()
    {
        return ++$this->idx;
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        $this->idx  = 0;
    }

    /**
     * @inheritDoc
     */
    public function seek($position)
    {
        if (!is_numeric($position) || $position < 0) {
            throw new \InvalidArgumentException("\$position should be non-negative integer, $position given");
        }
        if (empty($this->linesPositions[$position])) {
            throw new \RuntimeException("No such line $position");
        }
        $this->idx = $position;
    }

    /**
     * @inheritDoc
     */
    public function valid()
    {
        return isset($this->linesPositions[$this->idx]);
    }

}
