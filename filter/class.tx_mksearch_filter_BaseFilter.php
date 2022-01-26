<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 das Medienkombinat
 *  All rights reserved
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 ***************************************************************/

use Sys25\RnBase\Frontend\Request\ParametersInterface;

/**
 * Der Filter liest seine Konfiguration passend zum Typ des Solr RequestHandlers. Der Typ
 * ist entweder "default" oder "dismax". Entsprechend baut sich auch die Typoscript-Konfiguration
 * auf:
 * searchsolr.filter.default.
 * searchsolr.filter.dismax.
 *
 * Es gibt Optionen, die für beide Requesttypen identisch sind. Als Beispiel seien die Templates
 * genannt. Um diese Optionen zentral über das Flexform konfigurieren zu können, werden die
 * betroffenen Werte zusätzlich noch über den Pfad
 *
 * searchsolr.filter._overwrite.
 *
 * ermittelt und wenn vorhanden bevorzugt verwendet.
 *
 * @author René Nitzsche
 */
class tx_mksearch_filter_BaseFilter extends \Sys25\RnBase\Frontend\Filter\BaseFilter
{
    /**
     * Method is called in \Sys25\RnBase\Frontend\Marker\ListBuilder::render() and used to trigger the
     * parseTemplate() method of this class.
     *
     * @return $this
     */
    public function getMarker(): \Sys25\RnBase\Frontend\Filter\BaseFilter
    {
        return $this;
    }
}
