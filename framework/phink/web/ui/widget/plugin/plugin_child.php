<?php
/*
 * Copyright (C) 2019 David Blanchard
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
 
 namespace Phink\Web\UI\Widget\Plugin;

/**
 * Description of TPluginChild
 *
 * @author david
 */
class TPluginChild extends \Phink\Core\TObject
{
    use \Phink\Web\UI\THtmlControl;
    
    private $_value;

    public function getValue()
    {
        return $this->_value;
    }
    public function setValue($value)
    {
        $this->_value = $value;
    }

    public function data()
    {
        return $this->_value;
    }

}

